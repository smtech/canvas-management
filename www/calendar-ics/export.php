<?php

require_once(__DIR__ . '/../config.inc.php');
require_once(__DIR__ . '/.ignore.calendar-ics-authentication.inc.php');
require_once(__DIR__ . '/config.inc.php');

require_once(APP_PATH . '/include/page-generator.inc.php');
require_once(APP_PATH . '/include/canvas-api.inc.php');


if (isset($_REQUEST['course_url'])) {
	$courseId = preg_replace('|.*/courses/(\d+)/?.*|', '$1', parse_url($_REQUEST['course_url'], PHP_URL_PATH));
	$course = callCanvasApi('get', "/courses/$courseId");
	if ($course) {
		$webcalFeed = str_replace('https://', 'webcal://', $course['calendar']['ics']);
		displayPage('
		<h3>Course Calendar ICS Feed</h3>
		<p>You can subscribe to the calendar for <a href="https://' .
		parse_url(CANVAS_API_URL, PHP_URL_HOST) . '/courses/' . $courseId .
		'">' . $course['name'] . '</a> at <a href="' .
		$webcalFeed . '">' . $webcalFeed .
		'</a> in any calendar application that supports external ICS feeds.</p>'
		);
		exit;
	} else {
		displayError($json, false, 'Canvas API Error', 'The course you requested could not be accessed.');
		exit;
	}
} else {
	displayPage('
	<form method="post" action="' . $_SERVER['PHP_SELF'] . '">
		<label for="course_url">Course URL <span class="comment">The URL to the course whose calendar you would like to export as an ICS feed</span></label>
		<input id="course_url" name="course_url" type="text" />
		<input type="submit" value="Generate ICS Feed" />
	</form>
	');
}

?>