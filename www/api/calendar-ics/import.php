<?php

require_once('../page-generator.inc.php');

require_once('.ignore.calendar-ics-authentication.inc.php');
require_once('config.inc.php');

require_once('../canvas-api.inc.php');
require_once('../mysql.inc.php');

define('SCHEDULE_ONCE', 'once');
define('SCHEDULE_WEEKLY', 'weekly');
define('SCHEDULE_DAILY', 'daily');
define('SCHEDULE_HOURLY', 'hourly');

define('TABLE_CALENDARS', '`calendars`');
define('TABLE_EVENTS', '`events`');
define('TABLE_SCHEDULES', '`schedules`');

define('CANVAS_TIMESTAMP_FORMAT', 'Y-m-d\TH:iP');

define('SYNC_WARNING', '<em>Warning:</em> if you have set this synchronization to occur automatically, <em>do not</em> make any changes to your calendar in Canvas. Any changes you make in Canvas may be overwritten, deleted or even corrupt the synchronization process!');

/* do we have the vital information (an ICS feed and a URL to a canvas
   object)? */
if (isset($_REQUEST['cal']) && isset($_REQUEST['canvas_url'])) {
	// FIXME: need to do OAuth here, so that users are forced to authenticate to verify that they have permission to update these calendars!

	/* tell users that it's started and to cool their jets */
	displayPage('
		<h3>Calendar Import Started</h3>
		<p>The calendar import that you requested has begun. You may leave this page at anytime. Depending on where this calendar import is going, either the user whose calendar is importing the calendar, the teachers of the course that is importing the calendar, or the members of the group that is importing the calendar will receive a message when the import is complete.</p>'
	);
	
	/* use phpicalendar to parse the ICS feed into $master_array */
	define('BASE', './phpicalendar/');
	require_once(BASE . 'functions/date_functions.php');
	require_once(BASE . 'functions/init.inc.php');
	require_once(BASE . 'functions/ical_parser.php');

	/* get the context (user, course or group) for the canvas URL */
	$canvasContext = 'course'; // FIXME: actually get the context from the URL...

	/* look up the canvas object -- mostly to make sure that it exists! */
	// FIXME: test to be sure that this is a URL on the API instance
	$canvasId = preg_replace("|.*/{$canvasContext}s/(\d+)/?.*|", '$1', $_REQUEST['canvas_url']); // FIXME: this is only "pretend" context-sensitive!
	$course = callCanvasApi('get', "/{$canvasContext}s/$canvasId");
	
	/* calculate the unique pairing ID of this ICS feed and canvas object */
	$pairId = md5($_REQUEST['cal'] . CANVAS_API_URL . $canvasContext . $course['id']); // FIXME: this needs to be actually context-sensitive!

	/* log this pairing in the database cache, if it doesn't already exist */
	// FIXME: actually check to make sure it isn't there before logging it!
	mysqlQuery(
		"INSERT INTO " . TABLE_CALENDARS . "
			(
				`url`,
				`canvas_id`,
				`canvas_context`,
				`pair_id`)
			VALUES (
				'{$_REQUEST['cal']}',
				'{$course['id']}',
				'course',
				'$pairId'
			)"
	);
	
	/* get information about this pairing from the database cache */
	$response = mysqlQuery(
		"SELECT *
			FROM " . TABLE_CALENDARS . "
			WHERE
				`pair_id` = '$pairId'
			ORDER BY
				`id` DESC
			LIMIT 1"
	);
	$calendar = $response->fetch_assoc();

	/* walk through $master_array and update the Canvas calendar to match the
	   ICS feed, caching changes in the database */
	// FIXME: actually check the database for changes
	// FIXME: delete non-matching items from the Canvas calendar
	// FIXME: update changed items in the Canvas calendar (rather than just adding)
	// TODO: would it be worth the performance improvement to just process things from today's date forward? (i.e. ignore old items, even if they've changed...)
	foreach($master_array as $date => $times) {
		if (date_create_from_format('Ymd', $date)) {
			foreach($times as $time => $uids) {
				foreach($uids as $uid => $event) {
					/* urldecode all of the fields of the event, for easier processing! */
					foreach ($event as $key => $value) {
						$event[$key] = urldecode($value);
					}
					$uniqueUid = "{$date}_{$time}_{$uid}";
					$calendarEvent = callCanvasApi(
						'post',
						"/calendar_events",
						array(
							'calendar_event[context_code]' => "course_{$course['id']}",
							'calendar_event[title]' => $event['event_text'],
							'calendar_event[description]' => $event['description'],
							'calendar_event[start_at]' => date(CANVAS_TIMESTAMP_FORMAT, $event['start_unixtime']),
							'calendar_event[end_at]' => date(CANVAS_TIMESTAMP_FORMAT, $event['end_unixtime']),
							'calendar_event[location_name]' => urldecode($event['location'])
							// TODO: is it worth doing some extra parsing to populate the calendar_event[location_address] field (not until Canvas shows it, I would think)
						)
					);
					// FIXME: ics_data and canvas_data don't seem to be being entered!
					mysqlQuery(
						"INSERT INTO " . TABLE_EVENTS . "
							(
								`calendar`,
								`canvas_id`,
								`uid`,
								`ics_data`,
								`canvas_data`
							)
							VALUES (
								'{$calendar['id']}',
								'{$calendarEvent['id']}',
								'$uniqueUid',
								'" . json_encode($event, JSON_PRETTY_PRINT) . "',
								'" . json_encode($calendarEvent, JSON_PRETTY_PRINT) . "'
							)"
					);
				}
			}
		}
	}
	
	/* notify users of script completion */
	// FIXME: deal with messaging based on context
	$teachers = callCanvasApi(
		'get',
		"/courses/{$course['id']}/users",
		array(
			'enrollment_type' => 'teacher'
		)
	);

	$recipients = array();
	foreach($teachers as $teacher) {
		$recipients[] = $teacher['id'];
	}
	callCanvasApi(
		'post',
		'/conversations',
		array(
			'recipients' => $recipients,
			'body' => 'All events from the calendar &ldquo;' . $master_array['calendar_name'] . '&rdquo; (' . $_REQUEST['cal'] . ') have been imported into the ' . $course['name'] . ' course calendar (https://' . parse_url(CANVAS_API_URL, PHP_URL_HOST) . '/calendar2?include_contexts=course_' . $course['id'] .'). ' . SYNC_WARNING,
			'group_conversation' => 'true'
		)		
	);
	exit;
} else {
	/* display form to collect target and source URLs */
	displayPage('
<style><!--
	.calendarUrl {
		background-color: #c3d3df;
		padding: 20px;
		min-width: 200px;
		width: 50%;
		border-radius: 20px;
	}
	
	#arrow {
		padding: 0px 20px;
	}
	
	#arrow input[type=submit] {
		appearance: none;
		font-size: 48pt;
	}
	
	td {
		padding: 20px;
	}
--></style>	
<form enctype="multipart/form-data" action="' . $_SERVER['PHP_SELF'] . '" method="get">
	<table>
		<tr valign="middle">
			<td class="calendarUrl">
				<label for="cal">ICS Feed URL <span class="comment">If you are using a Google Calendar, we recommend that you use the private ICAL link, which will include full information about each event. Note that all ICS URLs must be publicly accessible.</span></label>
				<input id="cal" name="cal" type="text" />
			</td>
			<td id="arrow">
				<input type="submit" value="&rarr;" onsubmit="if (this.getAttribute(\'submitted\')) return false; this.setAttribute(\'submitted\',\'true\'); this.setAttribute(\'enabled\', \'false\');" />
			</td>
			<td class="calendarUrl">
				<label for="canvas_url">Course URL<span class="comment">URL for the course whose calendar will be updated</span></label>
				<input id="canvas_url" name="canvas_url" type="text" />
			</td>
		</tr>
		<tr>
			<td colspan="3">
				<label for="schedule">Schedule automatic updates from this feed to this course <span class="comment">' . SYNC_WARNING . '</span></label>
				<select id="schedule" name="schedule">
					<option value="' . SCHEDULE_ONCE . '">One-time import only</option>
					<optgroup label="Recurring">
						<option value="' . SCHEDULE_WEEKLY . '" disabled>Weekly (Saturday at midnight)</option>
						<option value="' . SCHEDULE_DAILY . '" disabled>Daily (midnight)</option>
						<option value="' . SCHEDULE_HOURLY . '" disabled>Hourly</option>
					</optgroup>
				</select>
			</td>
		</tr>
	</table>
</form>
	');
	exit;
}

?>