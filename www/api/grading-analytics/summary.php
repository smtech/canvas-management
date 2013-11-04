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
		ORDER BY
			`timestamp` DESC
		LIMIT 1
");
$statistic = $statistics->fetch_assoc();

$introduction = '
	<h3>' . $course['name'] . '</h3>
	<p>Data collection for these analytics is done at midnight every night, so statistics do not yet reflect grading done today.</p>
';

$stats = mysqlQuery("
	SELECT * FROM `course_statistics`
		WHERE
			`average_grading_turn_around` > 0
		GROUP BY
			`course[id]`
		ORDER BY
			`timestamp` DESC
");
$total = 0;
$divisor = 0;
while ($row = $stats->fetch_assoc()) {
	$total += $row['average_grading_turn_around'] * $row['student_count'] * $row['graded_assignment_count'];
	$divisor += $row['student_count'] * $row['graded_assignment_count'];
}
$averageTurnAround = $total / $divisor;

$turnAroundComparison = '
	<h3>Grading Turn-Around Time</h3>
	<p>What is the average turn-around time (in days) in this course for an assignment, from due date to posted grade in Canvas? The expectation articulated in the faculty handbook is that daily assignments will be returned within a week (green line) and that major assignments will be returned within two weeks (red line). The expectation is that grades will be posted to Canvas at approximately the same time as they are returned to students.</p>
	<p>The average turn-around time across all courses (dashed line), weighted by number of graded assignments and students, is ' . round($averageTurnAround, 1) . ' days. In &ldquo;' . $course['name'] . '&rdquo; (red column), the current average is ' . round($statistic['average_grading_turn_around'], 1) . ' days.</p>
	<img src="graph/turn-around-comparison.php?course_id=' . $_REQUEST['course_id'] . '"  width="100%" />
';

$turnAroundHistory = '
	<h3>Grading Turn-Around History</h3d>
	<img src="graph/turn-around-history.php?course_id=' . $_REQUEST['course_id'] . '" width="100%" />
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

displayPage($introduction . $turnAroundComparison . $turnAroundHistory . $oldestUngradedAssignment);

?>