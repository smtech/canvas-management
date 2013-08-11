<?php

/***********************************************************************
 *                                                                     *
 * Requirments & includes                                              *
 *                                                                     *
 ***********************************************************************/
 
/* REQUIRES crontab
   http://en.wikipedia.org/wiki/Cron */

require_once('../debug.inc.php');
define('DEBUGGING', DEBUGGING_CANVAS_API | DEBUGGING_LOG);

require_once('.ignore.calendar-ics-authentication.inc.php');
require_once('config.inc.php');

require_once('../page-generator.inc.php');
require_once('../canvas-api.inc.php');
require_once('../mysql.inc.php');

define('VALUE_OVERWRITE_CANVAS_CALENDAR', 'overwrite');

define('SYNC_WARNING', '<em>Warning:</em> if you have set this synchronization to occur automatically, <em>do not</em> make any changes to your calendar in Canvas. Any changes you make in Canvas may be overwritten, deleted or even corrupt the synchronization process!');

require_once('common.inc.php');

function isValidCrontab($crontab) {
	preg_match('%((\d+)|(\*)) ((\d+)|(\*)) ((\d+)|(\*)) ((\d+)|(\*)) ((\d+)|(\*))%', $crontab, $matches);
	return
		($matches[1] == '*' || ($matches[1] >= 0 && $matches[1] <= 59)) &&
		($matches[4] == '*' || ($matches[4] >= 0 && $matches[4] <= 23)) &&
		($matches[7] == '*' || ($matches[7] >= 1 && $matches[7] <= 31)) &&
		($matches[10] == '*' || ($matches[10] >= 1 && $matches[10] <= 12)) &&
		($matches[13] == '*' || ($matches[13] >= 0 && $matches[13] <= 6)) &&
		($matches[1] != '*' || $matches[7] != '*' || $matches[10] != '*' || $matches[13] != '*');
}

function getCanvasContext($canvasUrl) {
	// TODO: accept calendar2?contexts links too (they would be an intuitively obvious link to use, after all)
	/* get the context (user, course or group) for the canvas URL */
	$canvasContext = array();
	if (preg_match('%(https?://)?(' . parse_url(CANVAS_API_URL, PHP_URL_HOST) . '/(((course)|(accounts/\d+/((group)|(user))))s)/(\d+)).*%', $_REQUEST['canvas_url'], $matches)) {
		$canvasContext['canonical_url'] = "https://{$matches[2]}";
		$canvasContext['context'] = (strlen($matches[7]) ? $matches[7] : $matches[5]);
		$canvasContext['context_url'] = $matches[3];
		$canvasContext['id'] = $matches[10];
		return $canvasContext;
	}
	return false;
}

/* do we have the vital information (an ICS feed and a URL to a canvas
   object)? */
if (isset($_REQUEST['cal']) && isset($_REQUEST['canvas_url'])) {

	// FIXME: need to do OAuth here, so that users are forced to authenticate to verify that they have permission to update these calendars!
	
	if ($canvasContext = getCanvasContext($_REQUEST['canvas_url'])) {
		/* look up the canvas object -- mostly to make sure that it exists! */
		if ($canvasObject = callCanvasApi(CANVAS_API_GET, "/{$canvasContext['context_url']}/{$canvasContext['id']}")) {
		
			/* calculate the unique pairing ID of this ICS feed and canvas object */
			$pairingHash = getPairingHash($_REQUEST['cal'], $canvasContext['canonical_url']);
		
			debug_log(TOOL_NAME . ' START ' . getSyncTimestamp());
		
			/* tell users that it's started and to cool their jets */
			displayPage('
				<h3>Calendar Import Started</h3>
				<p>The calendar import that you requested has begun. You may leave this page at anytime. You can see the progress of the import by visiting <a target="_blank" href="https://' . parse_url(CANVAS_API_URL, PHP_URL_HOST) . "/calendar?include_contexts={$canvasContext['context']}_{$canvasObject['id']}\">this calendar</a> in Canvas.</p>"
			);
			
			/* use phpicalendar to parse the ICS feed into $master_array */
			define('BASE', './phpicalendar/');
			require_once(BASE . 'functions/date_functions.php');
			require_once(BASE . 'functions/init.inc.php');
			require_once(BASE . 'functions/ical_parser.php');
			displayError($master_array, false, null, null, DEBUGGING_GENERAL);
		
			/* log this pairing in the database cache, if it doesn't already exist */
			$calendarCacheResponse = mysqlQuery("
				SELECT *
					FROM `calendars`
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
							`canvas_url`,
							`synced`
						)
						VALUES (
							'$pairingHash',
							'{$_REQUEST['cal']}',
							'{$canvasContext['canonical_url']}',
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
								SELECT *
									FROM `events`
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
			
								$calendarEvent = callCanvasApi(
									CANVAS_API_POST,
									"/calendar_events",
									array(
										'calendar_event[context_code]' => "{$canvasContext['context']}_{$canvasObject['id']}",
										'calendar_event[title]' => preg_replace( // strip HTML -- Canvas does not accept it
											'%<[^>]*>%',
											'',
											$event['event_text']
										),
										'calendar_event[description]' => str_replace( // replace newlines with <br /> to maintain formatting
											"\n",
											"<br />\n",
											$event['description']
										),
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
					"/calendar_events/{$deletedEventCache['calendar_event[id]']}",
					array(
						'cancel_reason' => getSyncTimestamp()
					)
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
			
			/* if this was a scheduled import (i.e. a sync), update that schedule */
			if (isset($_REQUEST['schedule'])) {
				mysqlQuery("
					UPDATE `schedules`
						SET
							`synced` = '" . getSyncTimestamp() . "'
						WHERE
							`id` = '{$_REQUEST['schedule']}'
				");
			}
			
			/* are we setting up a regular synchronization? */
			if (isset($_REQUEST['sync']) && $_REQUEST['sync'] != SCHEDULE_ONCE) {
				$shellArguments[INDEX_COMMAND] = dirname(__FILE__) . '/sync.php';
				$shellArguments[INDEX_SCHEDULE] = $_REQUEST['sync'];
				$shellArguments[INDEX_WEB_PATH] = 'http://localhost' . dirname($_SERVER['PHP_SELF']);
				$crontab = null;
				switch ($_REQUEST['sync']) {
					case SCHEDULE_WEEKLY: {
						$crontab = '0 0 * * 0';
						break;
					}
					case SCHEDULE_DAILY: {
						$crontab = '0 0 * * *';
						break;
					}
					case SCHEDULE_HOURLY: {
						$crontab = '0 * * * *';
						break;
					}
					default: {
						$shellArguments[INDEX_SCHEDULE] = md5($_REQUEST['sync'] . getSyncTimestamp());
						$crontab = $_REQUEST['sync'];
					}
				}
				
				/* add to the cache database schedule, replacing any schedules for this
				   calendar that are already there */
				$schedulesResponse = mysqlQuery("
					SELECT *
						FROM `schedules`
						WHERE
							`calendar` = '{$calendarCache['id']}'
				");
				if ($schedule = $schedulesResponse->fetch_assoc()) {
					mysqlQuery("
						UPDATE `schedules`
							SET
								`schedule` = '" . $shellArguments[INDEX_SCHEDULE] . "',
								`synced` = '" . getSyncTimestamp() . "'
							WHERE
								`calendar` = '{$calendarCache['id']}'
					");
				} else {
					mysqlQuery("
						INSERT INTO `schedules`
							(
								`calendar`,
								`schedule`,
								`synced`
							)
							VALUES (
								'{$calendarCache['id']}',
								'" . $shellArguments[INDEX_SCHEDULE] . "',
								'" . getSyncTimestamp() . "'
							)
					");
				}
				
				/* schedule crontab trigger, if it doesn't already exist */
				if (isValidCrontab($crontab)) {
					$crontab .= ' php ' . implode(' ', $shellArguments);
					
					/* thank you http://stackoverflow.com/a/4421284 ! */
					$crontabs = shell_exec('crontab -l');
					/* check to see if this sync is already scheduled */
					if (strpos($crontabs, $crontab) === false) {
						$filename = md5(getSyncTimestamp()) . '.txt';
						file_put_contents("/tmp/$filename", $crontabs . $crontab . PHP_EOL);
						shell_exec("crontab /tmp/$filename");
						debug_log(TOOL_NAME . ' scheduled crontab ' . $crontab);
					}					
				} else {
					displayError(
						$_REQUEST['sync'],
						false,
						'Invalid crontab',
						'The crontab you submitted is invalid and will not be scheduled for recurring syncs.'
					);
				}
			}
			
			/* if we're ovewriting data (for example, if this is a recurring sync, we
			   need to remove the events that were _not_ synced this in this round */
			if ($_REQUEST['overwrite'] == VALUE_OVERWRITE_CANVAS_CALENDAR) {
			}
			
			// FIXME: deal with messaging based on context
		
			debug_log(TOOL_NAME . ' FINISH ' . getSyncTimestamp());
			exit;
		} else {
			displayError(
				array(
					'Canvas URL' => $_REQUEST['canvas_url'],
					'Canvas Context' => $canvasContext,
					'Canvas Object' => $canvasObject
				),
				true,
				'Canvas Object  Not Found',
				'The object whose URL you submitted could not be found.'
			);
		}
	} else {
		displayError(
			$_REQUEST['canvas_url'],
			false,
			'Invalid Canvas URL',
			'The Canvas URL you submitted could not be parsed.'
		);
		exit;
	}	
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
		</tr>-->
		<tr>
			<td colspan="3">
				<label for="schedule">Schedule automatic updates from this feed to this course <span class="comment">' . SYNC_WARNING . '</span></label>
				<select id="schedule" name="sync">
					<option value="' . SCHEDULE_ONCE . '">One-time import only</option>
					<optgroup label="Recurring">
						<option value="' . SCHEDULE_WEEKLY . '">Weekly (Saturday at midnight)</option>
						<option value="' . SCHEDULE_DAILY . '">Daily (midnight)</option>
						<option value="' . SCHEDULE_HOURLY . '">Hourly</option>
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