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

if (isset($_REQUEST['cal']) && isset($_REQUEST['course_url'])) {
	define('BASE', './phpicalendar/');
	require_once(BASE . 'functions/date_functions.php');
	require_once(BASE . 'functions/init.inc.php');
	require_once(BASE . 'functions/ical_parser.php');

	$courseId = preg_replace('|.*/courses/(\d+)/?.*|', '$1', $_REQUEST['course_url']);
	$json = callCanvasApi('get', "/courses/$courseId");
	$course = json_decode($json, true);
	
	displayError($master_array);
	exit;
} else {
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
				<label for="course_url">Course URL<span class="comment">URL for the course whose calendar will be updated</span></label>
				<input id="course_url" name="course_url" type="text" />
			</td>
		</tr>
		<tr>
			<td colspan="3">
				<label for="schedule">Schedule automatic updates from this feed to this course <span class="comment"><em>Warning:</em> If you schedule recurring updates to the course calendar from an ICS feed, <em>do not</em> edit that course calendar in Canvas &mdash; your edits will potentially corrupt the synchronization process</span></label>
				<select id="schedule" name="schedule">
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
</form>');
	exit;
}

?>