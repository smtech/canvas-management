<html>
<body>
<pre>
<?php

define ('TOOL_NAME', 'Generate Missing Course &amp; Section SIS ID&rsquo;s');

require_once(__DIR__ . '/../config.inc.php');
require_once(APP_PATH . '/.ignore.stmarksschool-test-authentication.inc.php');
require_once(APP_PATH . '/include/debug.inc.php');
require_once(APP_PATH . '/include/canvas-api.inc.php');

debugFlag('START');

$courses = callCanvasApiPaginated(
	CANVAS_API_GET,
	'/accounts/1/courses'
);
$page = 1;

do {
	$pageProgress = 'processing page ' . getCanvasApiCurrentPageNumber() . ' of ' . getCanvasApiLastPageNumber() . '...';
	echo $pageProgress . PHP_EOL;
	debugFlag($pageProgress);
	
	foreach ($courses as $course) {
		$courseLabel = false;
		$sisCourseId = $course['sis_course_id'];
		if (!strlen($sisCourseId)) {
			$sisCourseId = preg_replace('%[^a-zA-Z0-9]+%', '-', strtolower($course['name'])) . '-' . md5(serialize($course));
			$course = callCanvasApi(
				CANVAS_API_PUT,
				"/courses/{$course['id']}",
				array(
					'course[sis_course_id]' => $sisCourseId
				)
			);
			echo "    {$course['name']}: generated new SIS ID ({$course['sis_course_id']})" . PHP_EOL;
			$courseLabel = true;
		}
		$sections = callCanvasApi(
			CANVAS_API_GET,
			"/courses/{$course['id']}/sections"
		);
		foreach ($sections as $section) {
			if (!strlen($section['sis_section_id'])) {
				$sisSectionId = $course['sis_course_id'] . '-' . md5(serialize($section));
				$section = callCanvasApi(
					CANVAS_API_PUT,
					"/sections/{$section['id']}",
					array(
						'course_section[sis_section_id]' => $sisSectionId
					)
				);
				if (!$courseLabel) {
					echo '    ' . $course['name'] . PHP_EOL;
					$courseLabel = true;
				}
				echo "        {$section['name']}: generated new SIS ID ({$section['sis_section_id']})" . PHP_EOL;
			}
		}
	}
	flush();
} while ($courses = callCanvasApiNextPage());

debugFlag('FINISH');

?>
</pre>
</body>
</html>