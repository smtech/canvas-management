<?php

define ('TOOL_NAME', "Students as Teachers Audit");

require_once(__DIR__ . '/../config.inc.php');
require_once(APP_PATH . '/.ignore.stmarksschool-test-authentication.inc.php');
require_once(APP_PATH . '/include/debug.inc.php');
require_once(APP_PATH . '/include/canvas-api.inc.php');

debugFlag('START');

$faculty = array();

$facultyUsers = callCanvasApiPaginated(
	CANVAS_API_GET,
	'/courses/97/enrollments'
);

do {
	foreach ($facultyUsers as $facultyUser) {
		$faculty[$facultyUser['id']] = true;
	}	
} while ($facultyUsers = callCanvasApiNextPage());

echo count($faculty) . ' faculty users' . PHP_EOL;

$users = callCanvasApiPaginated(
	CANVAS_API_GET,
	'/accounts/1/users'
);

echo "user_id\tsis_user_id\tsortable_name\tisFaculty\tcourse_id\tsis_course_id\tname" . PHP_EOL;

do {
	foreach($users as $user) {
		echo "{$user['id']}\t{$user['sis_user_id']}\t{$user['sortable_name']}";
		if ($user['id'] != '170000003344068' && $user['id'] != '10000001314330') { // avoid Instructure User
			echo "\t{$faculty[$user['id']]}";
			if (!$faculty[$user['id']]) {
				$teacherEnrollments = callCanvasApi(
					CANVAS_API_GET,
					"/users/{$user['id']}/enrollments",
					array(
						'type' => array('TeacherEnrollment', 'TaEnrollment', 'DesignerEnrollment', 'ObserverEnrollment'),
						'per_page' => '50' // FIXME: should be a nested, paginated API call
					)
				);
				foreach ($teacherEnrollments as $teacherEnrollment) {
					echo "\t{$teacherEnrollment['course_id']}\t{$teacherEnrollment['sis_course_id']}\t{$teacherEnrollment['name']}";
				}
			}
		}
		echo PHP_EOL;
	}
} while ($users = callCanvasApiNextPage());

debugFlag('FINISH');

?>