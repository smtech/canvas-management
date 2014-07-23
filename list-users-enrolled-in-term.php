<html>
<body>
<pre>
<?php

define ('TOOL_NAME', "List Users Enrolled in Term {$_REQUEST['enrollment_term_id']}");

require_once('config.inc.php');

debugFlag('START');

$usersApi = new CanvasApiProcess(CANVAS_API_URL, CANVAS_API_TOKEN);
$enrollmentsApi = new CanvasApiProcess(CANVAS_API_URL, CANVAS_API_TOKEN);

$users = $usersApi->get('/accounts/1/users');

echo "user[id]\tuser[sis_user_id]\tuser[login_id]\tuser[name]" . PHP_EOL;

$allCourses = array();
do {
	$pageProgress = 'processing page ' . $usersApi->getCurrentPageNumber() . ' of ' . $usersApi->getLastPageNumber() . '...';
	debugFlag($pageProgress);
	
	foreach ($users as $user) {
		$userPosted = false;
		// exclude Travis Cornes' wacko account!
		if ($user['id'] < 10000001314330) {
			$enrollments = $enrollmentsApi->get("/users/{$user['id']}/enrollments");
			do {
				foreach ($enrollments as $enrollment) {
					if (!isset($allCourses[$enrollment['course_id']])) {
						try {
							$course = callCanvasApi(
								CANVAS_API_GET,
								"/courses/{$enrollment['course_id']}",
								array (
									'include[]' => 'term'
								),
								CANVAS_API_EXCEPTION_CLIENT
							);
							$allCourses[$course['id']] = $course['term']['id'];
						} catch (Pest_ClientError $e) {
							debugFlag("Course {$course['id']} was previously deleted.");
						}
					}
					if ($allCourses[$enrollment['course_id']] == $_REQUEST['enrollment_term_id']) {
						echo "{$user['id']}\t{$user['sis_user_id']}\t{$user['login_id']}\t{$user['name']}" . PHP_EOL;
						$userPosted = true;
						break;
					}
				}
			} while (!$userPosted && $enrollments = $enrollmentsApi->nextPage());
		}
	}
} while ($users = $usersApi->nextPage());

debugFlag('FINISH');

?>
</pre>
</body>
</html>