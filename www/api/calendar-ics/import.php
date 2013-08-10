<?php

require_once('../debug.inc.php');
define('DEBUGGING', DEBUGGING_CANVAS_API | DEBUGGING_LOG);

require_once('.ignore.calendar-ics-authentication.inc.php');
require_once('config.inc.php');

require_once('../page-generator.inc.php');
require_once('../canvas-api.inc.php');
require_once('../mysql.inc.php');

define('VALUE_OVERWRITE_CANVAS_CALENDAR', 'overwrite');

define('SCHEDULE_ONCE', 'once');
define('SCHEDULE_WEEKLY', 'weekly');
define('SCHEDULE_DAILY', 'daily');
define('SCHEDULE_HOURLY', 'hourly');

/* cache database tables */

/* calendars
	id				hash of ICS and Canvas pairing, generated by getPairingHash()
	ics_url			URL of ICS feed
	canvas_context	enumerated context string form canvas (user, course or group)
	canvas_id		id of canvas context
	synced			sync identification, generated by getSyncTimestamp()
	modified		timestamp of last modificiation of the record
*/

/* events
	id					auto-incremented cache record id
	calendar			pair hash for cached calendar, generated by getPairingHash()
	calendar_event[id]	Canvas ID of calendar event
	event_hash			hash of cached event data from previous sync
	synced				sync identification, generated by getSyncTimestamp()
	modified			timestamp of last modification of the record
*/

/* schedules
	id			auto-incremented cache record id
	calendar	pair hash for cached calendar, generated by getPairingHash()
	crontab		crontab data for scheduled synchronization
	synced		sync identification, generated by getSyncTimestamp()
	modified	timestamp of last modification of the record
*/

define('CANVAS_TIMESTAMP_FORMAT', 'Y-m-d\TH:iP');

define('SYNC_WARNING', '<em>Warning:</em> if you have set this synchronization to occur automatically, <em>do not</em> make any changes to your calendar in Canvas. Any changes you make in Canvas may be overwritten, deleted or even corrupt the synchronization process!');

/**
 * Generate a unique ID to identify this particular pairing of ICS feed and
 * Canvas calendar
 **/
function getPairingHash($icsUrl, $canvasContext) {
	return md5($icsUrl . $canvasContext . CANVAS_API_URL);
}

/**
 * Generate a hash of this version of an event to cache in the database
 **/
function getEventHash($date, $time, $uid, $event) {
	return md5($date . $time . $uid . serialize($event));
}

/**
 * Generate a unique identifier for this synchronization pass
 **/
$SYNC_TIMESTAMP = null;
function getSyncTimestamp() {
	global $SYNC_TIMESTAMP;
	if ($SYNC_TIMESTAMP) {
		return $SYNC_TIMESTAMP;
	} else {
		$timestamp = new DateTime();
		$SYNC_TIMESTAMP = $timestamp->format(SYNC_TIMESTAMP_FORMAT) . SEPARATOR . md5($_SERVER['REMOTE_ADDR']);
		return $SYNC_TIMESTAMP;
	}
}

/* do we have the vital information (an ICS feed and a URL to a canvas
   object)? */
if (isset($_REQUEST['cal']) && isset($_REQUEST['canvas_url'])) {

	// FIXME: need to do OAuth here, so that users are forced to authenticate to verify that they have permission to update these calendars!

	debug_log('START ' . getSyncTimestamp());

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
	if(DEBUGGING & DEBUGGING_GENERAL) displayError($master_array);

	/* get the context (user, course or group) for the canvas URL */
	// FIXME: actually get the context
	// TODO: accept calendar2?contexts links too (they would be an intuitively obvious link to use, after all)
	$canvasContext = 'course'; // FIXME: actually get the context from the URL...

	/* look up the canvas object -- mostly to make sure that it exists! */
	// FIXME: test to be sure that this is a URL on the API instance
	$canvasId = preg_replace("|.*/{$canvasContext}s/(\d+)/?.*|", '$1', $_REQUEST['canvas_url']); // FIXME: this is only "pretend" context-sensitive!
	$course = callCanvasApi(CANVAS_API_GET, "/{$canvasContext}s/$canvasId");
	
	/* calculate the unique pairing ID of this ICS feed and canvas object */
	$pairingHash = getPairingHash($_REQUEST['cal'], $canvasContext . $course['id']);

	/* log this pairing in the database cache, if it doesn't already exist */
	$calendarCacheResponse = mysqlQuery("
		SELECT * FROM `calendars`
			WHERE
				`id` = '$pairingHash'
	");
	$calendarCache = $calendarCacheResponse->fetch_assoc();
	
	/* if the calendar is already cached, just update the sync timestamp */
	if ($calendarCache) {
		mysqlQuery("
			UPDATE `calendars`
				SET
					`synced` = '" . getSyncTimestamp() . "'
				WHERE
					`id` = '$pairingHash'
		");
	} else {
		mysqlQuery("
			INSERT INTO `calendars`
				(
					`id`,
					`ics_url`,
					`canvas_context`,
					`canvas_id`,
					`synced`
				)
				VALUES (
					'$pairingHash',
					'{$_REQUEST['cal']}',
					'course',
					'{$course['id']}',
					'" . getSyncTimestamp() . "'
				)
		");
	}
	
	/* refresh calendar information from cache database */
	$calendarCacheResponse = mysqlQuery("
		SELECT *
			FROM `calendars`
			WHERE
				`id` = '$pairingHash'
	");
	$calendarCache = $calendarCacheResponse->fetch_assoc();

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
					
					/* does this event already exist in Canvas? */
					$eventHash = getEventHash($date, $time, $uid, $event);
					$eventCacheResponse = mysqlQuery("
						SELECT * FROM `events`
							WHERE
								`calendar` = '{$calendarCache['id']}' AND
								`event_hash` = '$eventHash'
					");
					
					/* if we already have the event cached in its current form, just update
					   the timestamp */
					$eventCache = $eventCacheResponse->fetch_assoc();
					if (DEBUGGING & DEBUGGING_MYSQL) displayError($eventCache);
					if ($eventCache) {
						mysqlQuery("
							UPDATE `events`
								SET
									`synced` = '" . getSyncTimestamp() . "'
								WHERE
									`id` = '{$eventCache['id']}'
						");
						
					/* otherwise, cache the new version of the event */
					} else {
						/* multi-day event instance start times need to be changed to _this_ date */
						$start = new DateTime("@{$event['start_unixtime']}");
						$start->setTimeZone(new DateTimeZone(LOCAL_TIMEZONE));
						$start->setDate(substr($date, 0, 4), substr($date, 4, 2), substr($date, 6, 2));
						
						$end = new DateTime("@{$event['end_unixtime']}");
						$end->setTimeZone(new DateTimeZone(LOCAL_TIMEZONE));
						$end->setDate(substr($date, 0, 4), substr($date, 4, 2), substr($date, 6, 2));
	
						// FIXME: strip HTML out of title
						// FIXME: replace newlines with <br/> or <p> in description
						$calendarEvent = callCanvasApi(
							CANVAS_API_POST,
							"/calendar_events",
							array(
								'calendar_event[context_code]' => "course_{$course['id']}",
								'calendar_event[title]' => $event['event_text'],
								'calendar_event[description]' => $event['description'],
								'calendar_event[start_at]' => $start->format(CANVAS_TIMESTAMP_FORMAT),
								'calendar_event[end_at]' => $end->format(CANVAS_TIMESTAMP_FORMAT),
								'calendar_event[location_name]' => urldecode($event['location'])
							)
						);
						// FIXME: ics_data and canvas_data don't seem to be being entered!
						$icalEventJson = json_encode($event);
						$calendarEventJson = json_encode($calendarEvent);
						mysqlQuery("
							INSERT INTO `events`
								(
									`calendar`,
									`calendar_event[id]`,
									`event_hash`,
									`synced`
								)
								VALUES (
									'{$calendarCache['id']}',
									'{$calendarEvent['id']}',
									'$eventHash',
									'" . getSyncTimestamp() . "'
								)
						");
					}
				}
			}
		}
	}

	/* clean out previously synced events that are no longer correct */
	$deletedEventsResponse = mysqlQuery("
		SELECT * FROM `events`
			WHERE
				`calendar` = '{$calendarCache['id']}' AND
				`synced` != '" . getSyncTimestamp() . "'
	");
	while ($deletedEventCache = $deletedEventsResponse->fetch_assoc()) {
		$deletedEvent = callCanvasApi(
			CANVAS_API_DELETE,
			"/calendar_events/{$deletedEventCache['calendar_event[id]']}"/*,
			array(
				'cancel_reason' => getSyncTimestamp()
			)*/
		);
		mysqlQuery("
			DELETE * FROM `events`
				WHERE
					`id` = '{$deletedEventCache['id']}' AND
					`calendar` = '{$calendarCache['id']}'
					`calendar_event[id]` = '{$deletedEvent['id']}' AND
					`synced` != '" . getSyncTimestamp() . "'
		");
	}
	
	/* if we're ovewriting data (for example, if this is a recurring sync, we
	   need to remove the events that were _not_ synced this in this round */
	if ($_REQUEST['overwrite'] == VALUE_OVERWRITE_CANVAS_CALENDAR) {
	}
	
	// FIXME: deal with messaging based on context

	debug_log('FINISH ' . getSyncTimestamp());
	exit;
} else {
	/* display form to collect target and source URLs */
	// FIXME: add javascript to force selection of overwrite if a recurring sync is selected
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
				<label for="cal">ICS Feed URL <span class="comment">If you are using a Google Calendar, we recommend that you use the private ICAL link, which will include full information about each event. Note that all ICS URLs must be publicly accessible, requiring no authentication.</span></label>
				<input id="cal" name="cal" type="text" />
			</td>
			<td id="arrow">
				<input type="submit" value="&rarr;" onsubmit="if (this.getAttribute(\'submitted\')) return false; this.setAttribute(\'submitted\',\'true\'); this.setAttribute(\'enabled\', \'false\');" />
			</td>
			<td class="calendarUrl">
				<label for="canvas_url">Canvas URL<span class="comment">URL for the user/group/course whose calendar will be updated</span></label>
				<input id="canvas_url" name="canvas_url" type="text" />
			</td>
		</tr>
		<!--<tr>
			<td colspan="3">
				<label for="overwrite"><input id="overwrite" name="overwrite" type="checkbox" value="' . VALUE_OVERWRITE_CANVAS_CALENDAR . '" disabled /> Replace existing calendar information <span class="comment">Checking this box will <em>delete</em> all of your current Canvas calendar information for this user/group/course.</span></label>
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
		</tr>-->
	</table>
</form>
	');
	exit;
}

?>