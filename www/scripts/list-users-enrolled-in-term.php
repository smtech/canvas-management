<html>
<body>
<pre>
<?php

define ('TOOL_NAME', "List Users Enrolled in Term {$_REQUEST['term_id']}");

require_once(__DIR__ . '/../config.inc.php');
require_once(APP_PATH . '/.ignore.read-only-authentication.inc.php');
require_once(APP_PATH . '/include/debug.inc.php');
require_once(APP_PATH . '/include/canvas-api.inc.php');

debugFlag('START');

$users = callCanvasApiPaginated(
	CANVAS_API_GET,
	'/accounts/1/users'
);
$page = 1;

echo TOOL_NAME . PHP_EOL;
echo "id\tsis_user_id\tlogin_id\tname" . PHP_EOL;

$allCourses = array();
do {
	$pageProgress = 'processing page ' . getCanvasApiCurrentPageNumber() . ' of ' . getCanvasApiLastPageNumber() . '...';
	debugFlag($pageProgress);
	
	foreach ($users as $user) {
		// exclude Travis Cornes' wacko account!
		if ($user['id'] < 10000001314330) {
			$enrollments = callCanvasApi(
				CANVAS_API_GET,
				"/users/{$user['id']}/enrollments",
				array(
					'per_page' => '50' // soon, this won't be enough
				)
			);
			foreach ($enrollments as $enrollment) {
				if (!isset($allCourses[$enrollment['course_id']])) {
					$course = callCanvasApi(
						CANVAS_API_GET,
						"/courses/{$enrollment['course_id']}",
						array (
							'include[]' => 'term'
						)
					);
					$allCourses[$course['id']] = $course['term']['id'];
				}
				if ($allCourses[$enrollment['course_id']] == $_REQUEST['term_id']) {
					echo "{$user['id']}\t{$user['sis_user_id']}\t{$user['login_id']}\t{$user['name']}" . PHP_EOL;
					break;
				}
			}
		}
	}
	flush();

} while ($users = callCanvasApiNextPage());

debugFlag('FINISH');

?>
</pre>
</body>
</html>