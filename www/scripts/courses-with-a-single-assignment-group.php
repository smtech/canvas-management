<?php

require_once(__DIR__ . '/../config.inc.php');
require_once(APP_PATH . '/.ignore.read-only-authentication.inc.php');
require_once(APP_PATH . '/include/canvas-api.inc.php');

function listCoursesWithOnlyOneAssignmentGroup($term) {
	$coursesApi = new CanvasApiProcess(CANVAS_API_URL, CANVAS_API_TOKEN);
	$courses = $coursesApi->get(
		'/accounts/132/courses',
		array(
			'enrollment_term_id' => $term,
			'with_enrollments' => 'true',
			'published' => 'true'
		)
	);
	
	do {
		foreach ($courses as $course) {
			$assignmentGroups = callCanvasApi(
				CANVAS_API_GET,
				"/courses/{$course['id']}/assignment_groups"
			);
			if (count($assignmentGroups) == 1) {
				echo "{$course['id']}\t{$course['name']}\thttps://" . parse_url(CANVAS_API_URL, PHP_URL_HOST) . "/courses/{$course['id']}/assignments" . PHP_EOL;
			}
		}
	} while ($courses = $coursesApi->nextPage());
}

echo "id\tname\turl" . PHP_EOL;

// FIXME bad, bad hard-coding of term IDs
listCoursesWithOnlyOneAssignmentGroup(106);
listCoursesWithOnlyOneAssignmentGroup(107);