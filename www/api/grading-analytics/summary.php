<?php

require_once('.ignore.grading-analytics-authentication.inc.php');
define('TOOL_START_PAGE', 'https://' . parse_url(CANVAS_API_URL, PHP_URL_HOST) . "/courses/{$_REQUEST['course_id']}");

require_once('config.inc.php');
require_once('../canvas-api.inc.php');
require_once('../mysql.inc.php');
require_once('../page-generator.inc.php');

$course = callCanvasApi(
	CANVAS_API_GET,
	"/courses/{$_REQUEST['course_id']}"
);

$statistics = mysqlQuery("
	SELECT * FROM `course_statistics`
		WHERE
			`course[id]` = '{$_REQUEST['course_id']}'
		GROUP BY
			`course[id]`
		ORDER BY
			`timestamp` DESC
");
$statistic = $statistics->fetch_assoc();

$introduction = '
	<h3>' . $course['name'] . '</h3>
	<p>Data collection for these analytics is done at midnight every night, so statistics may not reflect grading done today.</p>
';

$turnAroundComparison = '
	<h3>Grading Turn-Around Time</h3>
	<p>What is the average turn-around time (in days) in this course for an assignment, from due date to posted grade in Canvas? The expectation articulated in the faculty handbook is that daily assignments will be returned within a week (green line) and that major assignments will be returned within two weeks (red line). The average across all courses is the dashed line. This course is the red column. The expectation is that grades will be posted to Canvas at approximately the same time as they are returned to students.</p>
	<img src="graph/turn-around-comparison.php?course_id=' . $_REQUEST['course_id'] . '"  width="100%" />
';

$turnAroundTrend = '
	<h3>Grading Turn-Around Trend</h3d>
	<img src="graph/turn-around-trend.php?course_id=' . $_REQUEST['course_id'] . '" width="100%" />
';

if (isset($statistic['oldest_ungraded_assignment_url'])) {
	preg_match('@.*\/assignments\/(\d+)@', $statistic['oldest_ungraded_assignment_url'], $matches);
	$assignmentInfo = callCanvasApi(
		CANVAS_API_GET,
		"/courses/{$_REQUEST['course_id']}/assignments/{$matches[1]}"
	);
	
	$oldestDueDate = new DateTime($statistic['oldest_ungraded_assignment_due_date']);
	$now = new DateTime();
	$oldestUngradedAssignment = '
		<h3>Oldest Ungraded Assignment</h3>
		<p>The oldest assignment (whose due date is past) for which there is no grade (for which a grade is anticipated: i.e. not a zero-point assignment or an ungraded assignment).</p>
		<p><a target="_blank" href="' . $statistic['oldest_ungraded_assignment_url'] . '">' . $assignmentInfo['name'] . '</a>, due ' . $oldestDueDate->format('l, F j, Y') . ' (' . $now->diff($oldestDueDate)->format('%a') . ' days old)</p>
	';
}

displayPage($introduction . $turnAroundComparison . $turnAroundTrend . $oldestUngradedAssignment);

?>