<html>
<body>
<pre>
<?php

require_once('.ignore.stmarksschool-test-authentication.inc.php');
require_once('stable/canvas-api.inc.php');

$apiUrl = '/accounts/1/courses';

do {
	echo $apiUrl . PHP_EOL;

	$courses = callCanvasApi(
		CANVAS_API_GET,
		$apiUrl
	);
	$apiUrl = preg_replace('%.*<' . CANVAS_API_URL . '([^>]+)>; rel="next".*%', '\\1', $PEST->lastHeader('link'));
	
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
} while (preg_match('%/accounts/1/courses.*%', $apiUrl));

?>
</pre>
</body>
</html>