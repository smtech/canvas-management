<?php

define('TOOL_NAME','List Teachers of Courses');

require_once('config.inc.php');

debugFlag('START');

$coursesApi = new CanvasApiProcess(CANVAS_API_URL, CANVAS_API_TOKEN);
$teachersApi = new CanvasApiProcess(CANVAS_API_URL, CANVAS_API_TOKEN);
$courses = $coursesApi->get(
	'/accounts/1/courses',
	array(
		'enrollment_term' => $_REQUEST['enrollment_term_id']
	)
);
echo "course[id]\tcourse[name]\tteacher[id]\tteacher[name]" . PHP_EOL;
do {
	foreach ($courses as $course) {
		$teachers = $teachersApi->get(
			"/courses/{$course['id']}/enrollments",
			array(
				'type[]' => 'TeacherEnrollment'
			)
		);
		do {
			foreach ($teachers as $teacher) {
				echo "{$course['id']}\t{$course['name']}\t{$teacher['user']['id']}\t{$teacher['user']['name']}" . PHP_EOL;
			}
		} while ($teachers = $teachersApi->nextPage());
	}
} while ($courses = $coursesApi->nextPage());

debugFlag('FINISH');

?>