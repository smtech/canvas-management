<?php

require_once('.ignore.calendar-ics-authentication.inc.php');
require_once('config.inc.php');

require_once('../canvas-api.inc.php');
require_once('../mysql.inc.php');
require_once('../Pest.php');

require_once('common.inc.php');

$schedulesResponse = mysqlQuery("
	SELECT *
		FROM `schedules`
		WHERE
			`schedule` = '" . mysqlEscapeString($argv[INDEX_SCHEDULE]) . "'
		ORDER BY
			`synced` ASC
");

$import = new Pest($argv[INDEX_WEB_PATH]);
while($schedule = $schedulesResponse->fetch_assoc()) {
	$calendarResponse = mysqlQuery("
		SELECT *
			FROM `calendars`
			WHERE
				`id` = '{$schedule['calendar']}'
	");
	if ($calendar = $calendarResponse->fetch_assoc()) {
		$import->get(
			'import', // assumes ../.htaccess with RewriteCond
			array(
				'cal' => $calendar['ics_url'],
				'canvas_url' => $calendar['canvas_url'],
				'schedule' => $schedule['id']
			)
		);
	}
}

?>