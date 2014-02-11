<?php

// TODO UI!

define ('TOOL_NAME', 'Assignments due on a day');

require_once('config.inc.php');
	
$sisIdFilter = '/.*((orange)|(plum)).*/i';
$dateFilter = '/2014-01-13T.*/';

$coursesApi = new CanvasApiProcess(CANVAS_API_URL, CANVAS_API_TOKEN);
$assignmentsApi = new CanvasApiProcess(CANVAS_API_URL, CANVAS_API_TOKEN);
$enrollmentsApi = new CanvasApiProcess(CANVAS_API_URL, CANVAS_API_TOKEN);

echo "course[id]\tcourse[sis_course_id]\tcourse[name]\tassignment[name]\tassignment[description]\tenrollments" . PHP_EOL;

debugFlag('START');

$courses = $coursesApi->get(
	'/accounts/132/courses',
	array(
		'published' => 'true',
	)
);

do {
	foreach($courses as $course) {
		if (preg_match($sisIdFilter, $course['sis_course_id'])) {
			$enrollments = $enrollmentsApi->get("/courses/{$course['id']}/enrollments");
			$enrollmentList = "";
			do {
				foreach ($enrollments as $enrollment) {
					if ($enrollment['type'] != 'StudentEnrollment') {
						$enrollmentList .= "{$enrollment['user']['name']} (" . str_replace('Enrollment', '', $enrollment['type']) . "), ";
					} else {
						$enrollmentList .= "{$enrollment['user']['name']}, ";
					}
				}
			} while ($enrollments = $enrollmentsApi->nextPage());
		
			$assignments = $assignmentsApi->get("/courses/{$course['id']}/assignments");
			do {
				foreach ($assignments as $assignment) {
					if (preg_match($dateFilter, $assignment['due_at'])) {
						// FIXME: need to strip out line breaks, tabs so as not to break the output
						echo "{$course['id']}\t{$course['sis_course_id']}\t{$course['name']}\t{$assignment['name']}\t" . str_replace("\t", " ", str_replace("\n", " ", $assignment['description'])) . "\t{$enrollmentList}" . PHP_EOL;
					}
				}
			} while ($assignments = $assignmentsApi->nextPage());
		}
	}	
} while ($courses = $coursesApi->nextPage());

debugFlag('FINISH');

?>