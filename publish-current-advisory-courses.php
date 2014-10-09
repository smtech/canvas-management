<?php

require_once('.ignore.live-authentication.inc.php');
define ('TOOL_NAME', "Turn Off Advisor Notifications");
require_once('config.inc.php');
require_once(SMCANVASLIB_PATH . '/include/page-generator.inc.php');

debugFlag('START');

$api = new CanvasApiProcess(CANVAS_API_URL, CANVAS_API_TOKEN);
$coursesApi = new CanvasApiProcess(CANVAS_API_URL, CANVAS_API_TOKEN);
$courses = $coursesApi->get('accounts/74/courses', array('enrollment_term_id' => '261'));
do {
	foreach ($courses as $course) {
		$api->put("courses/{$course['id']}", array('offer' => 'true'));
	}
} while ($courses = $coursesApi->nextPage());

debugFlag('FINISH');


?>