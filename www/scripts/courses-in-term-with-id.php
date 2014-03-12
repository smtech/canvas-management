<html>
<body>
<pre>
<?php

define ('TOOL_NAME', "Courses in Term {$_REQUEST['enrollment_term_id']} with IDs");

require_once('config.inc.php');

$coursesApi = new CanvasApiProcess(CANVAS_API_URL, CANVAS_API_TOKEN);

debugFlag('START');

$courses = $coursesApi->get(
	'/accounts/1/courses',
	array(
		'enrollment_term_id' => $_REQUEST['enrollment_term_id'],
	)
);

echo "course[id]\tcourse[sis_course_id]\tcourse[short_name]\tcourse[long_name]\tcourse[account_id]\tterm[id]" . PHP_EOL;

do {
	$pageProgress = 'processing page ' . $coursesApi->getCurrentPageNumber() . ' of ' . $coursesApi->getLastPageNumber() . '...';
	debugFlag($pageProgress);
		
	foreach ($courses as $course) {
		echo "{$course['id']}\t{$course['sis_course_id']}\t{$course['course_code']}\t{$course['name']}\t{$course['account_id']}\t{$_REQUEST['enrollment_term_id']}" . PHP_EOL;
	}
	flush();
} while ($courses = $coursesApi->nextPage());

debugFlag('FINISH');

?>
</pre>
</body>
</html>