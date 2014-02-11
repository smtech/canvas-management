<?php

define ('TOOL_NAME', "Courses with a Single Assignment Group in term $term");

require_once('config.inc.php');

function listCoursesWithOnlyOneAssignmentGroup($term) {
	debugFlag('START');
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
	debugFlag('FINISH');
}

echo "id\tname\turl" . PHP_EOL;

listCoursesWithOnlyOneAssignmentGroup($_REQUEST['enrollment_term_id']);
