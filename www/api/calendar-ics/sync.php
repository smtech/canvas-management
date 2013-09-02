<?php

require_once('.ignore.calendar-ics-authentication.inc.php');
require_once('config.inc.php');

require_once('../canvas-api.inc.php');
require_once('../mysql.inc.php');
require_once('../Pest.php');

require_once('common.inc.php');

// FIXME: should filter so that the syncs for the server we're running against (INDEX_WEB_PATH) are called (or is that already happening?)
$schedulesResponse = mysqlQuery("
	SELECT *
		FROM `schedules`
		WHERE
			`schedule` = '" . mysqlEscapeString($argv[INDEX_SCHEDULE]) . "'
		ORDER BY
			`synced` ASC
");

$import = new Pest(preg_replace('%(https?://)(.*)%', '\\1' . MYSQL_USER . ':' . MYSQL_PASSWORD . '@\\2', $argv[INDEX_WEB_PATH]));
while($schedule = $schedulesResponse->fetch_assoc()) {
	$calendarResponse = mysqlQuery("
		SELECT *
			FROM `calendars`
			WHERE
				`id` = '{$schedule['calendar']}'
	");
	if ($calendar = $calendarResponse->fetch_assoc()) {
		try {
			$import->get(
				'import', // assumes ../.htaccess with RewriteCond
				array(
					'cal' => $calendar['ics_url'],
					'canvas_url' => $calendar['canvas_url'],
					'schedule' => $schedule['id']
				)
			);
		} catch (Exception $e) {
			debugFlag($e->getMessage());
			exit;
		}
	}
}

?>