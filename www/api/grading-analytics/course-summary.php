<?php

require_once('.ignore.grading-analytics-authentication.inc.php');
define('TOOL_START_PAGE', 'https://' . parse_url(CANVAS_API_URL, PHP_URL_HOST) . "/courses/{$_REQUEST['course_id']}");

require_once('config.inc.php');
require_once('common.inc.php');
require_once('../canvas-api.inc.php');
require_once('../mysql.inc.php');
require_once('../page-generator.inc.php');

$course = callCanvasApi(
	CANVAS_API_GET,
	"/courses/{$_REQUEST['course_id']}"
);

$department = callCanvasApi(
	CANVAS_API_GET,
	"/accounts/{$course['account_id']}"
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

$introduction = buildPageSection('
	<style>
		.dashed-underline {
			border-bottom: 1px dashed black;
		}
		
		.green-underline {
			border-bottom: 1px solid lime;
		}
		
		.red-underline {
			border-bottom: 1px solid red;
		}
		
		.red-bar {
			color: white;
			background-color: red;
			padding: 0 2px;
		}
	</style>
	<h1>' . $course['name'] . '</h1>
	<p>Data collection for these analytics is done at midnight every night, so statistics do not yet reflect grading done today.</p>', false, 'introduction');

$turnAroundComparison = buildPageSection('
	<div class="image-placement">
		<h4>Overall</h4>
		<a
			data-lightbox="turn-around-comparison"
			title="The average grading turn-around time in &ldquo;' . $course['name'] . '&rdquo; compared to all active courses in the school."
			href="graph/turn-around-comparison.php?course_id=' . $_REQUEST['course_id'] . '">
				<img src="graph/turn-around-comparison.php?course_id=' . $_REQUEST['course_id'] . '"  style="width: 100%;" />
			</a>
		<p class="caption">&ldquo;' . $course['name'] . '&rdquo; compared to all active courses in the school.</p>
	</div>
	
	<div class="image-placement" style="float: right; width: 40%;">
		<h4>' . $department['name'] . ' Department</h4>
		<a
			data-lightbox="turn-around-comparison"
			title="The average grading turn-around time in &ldquo;' . $course['name'] . '&rdquo; compared to all active courses in the ' . $department['name'] . ' department."
			href="graph/turn-around-comparison.php?course_id=' . $_REQUEST['course_id'] . '&department_id=' . $course['account_id'] . '">
				<img src="graph/turn-around-comparison.php?course_id=' . $_REQUEST['course_id'] . '&department_id=' . $course['account_id'] . '"  style="width: 100%;" />
			</a>
		<p class="caption">&ldquo;' . $course['name'] . '&rdquo; compared to all active courses in the ' . $department['name'] . ' department.</p>
	</div>	

	<p class="caption">What is the average turn-around time (in days) in this course for an assignment, from due date to posted grade in Canvas? The expectation articulated in the faculty handbook is that daily assignments will be returned within a week (<span class="green-underline">green line</span>) and that major assignments will be returned within two weeks (<span class="red-underline">red line</span>). The expectation is that grades will be posted to Canvas at approximately the same time as they are returned to students.</p>
	
	<p class="caption">The average turn-around time across all courses (<span class="dashed-underline">dashed line</span>), weighted by number of graded assignments and students, is ' . round(averageTurnAround(), 1) . ' days. In &ldquo;' . $course['name'] . '&rdquo; (<span class="red-bar">red column</span>), the current average is ' . round($statistic['average_grading_turn_around'], 1) . ' days.</p>
	
	<h4>What can be learned from this information?</h4>
	
	<ul>
		<li>How does this course&rsquo;s grading turn-around time compare to other active courses?</li>
		
		<li>Is the turn-around time shorter? This may reflect either more prompt feedback to students or fewer complex assignments. It may also reflect due dates being added to assignments after they have already been posted.</li>
		
		<li>Is the turn-around time longer? This may reflect either slower feedback to students, more complex assignments, or a disconnect between when grades are shared with students physically and when they are posted to Canvas.</li>
	</ul>
	<br clear="all" />',
	'Grading Turn-Around Time Comparison',
	'turn-around-comparison'
);

$turnAroundHistory = buildPageSection('<img src="graph/turn-around-history.php?course_id=' . $_REQUEST['course_id'] . '" width="100%" />', 'Grading Turn-Around History', 'turn-around-history');

$assignmentCount = buildPageSection('
	<div class="image-placement">
		<h4>Overall</h4>
		<a
			data-lightbox="assignment-count"
			title="The number of assignments posted in Canvas for &ldquo;' . $course['name'] . '&rdquo; compared to all active courses in the school."
			href="graph/assignment-count-comparison.php?course_id=' . $_REQUEST['course_id'] . '">
				<img src="graph/assignment-count-comparison.php?course_id=' . $_REQUEST['course_id'] . '" width="100%" />
			</a>
		<p class="caption">&ldquo;' . $course['name'] . '&rdquo; compared to all active courses in the school.</p>
	</div>

	<div class="image-placement" style="width: 50%; float: right;">
		<h4>' . $department['name'] . ' Department</h4>
		<a
			data-lightbox="assignment-count"
			title="The number of assignments posted in Canvas for &ldquo;' . $course['name'] . '&rdquo; compared to all active courses in the ' . $department['name'] . ' department."
			href="graph/assignment-count-comparison.php?course_id=' . $_REQUEST['course_id'] . '&department_id=' . $course['account_id'] . '">
				<img src="graph/assignment-count-comparison.php?course_id=' . $_REQUEST['course_id'] . '&department_id=' . $course['account_id'] . '" width="100%" />
			</a>
		<p class="caption">&ldquo;' . $course['name'] . '&rdquo; compared to all active courses in the ' . $department['name'] . ' department.</p>
	</div>
	
	<p class="caption">How many assignments have been posted to Canvas for &ldquo;' . $course['name'] . '&rdquo;?
	
	<br clear="all" />',
	'Number of Assignments',
	'assignment-count');

if (strlen($statistic['oldest_ungraded_assignment_url'])) {
	preg_match('@.*\/assignments\/(\d+)@', $statistic['oldest_ungraded_assignment_url'], $matches);
	$assignmentInfo = callCanvasApi(
		CANVAS_API_GET,
		"/courses/{$_REQUEST['course_id']}/assignments/{$matches[1]}"
	);
	
	$oldestDueDate = new DateTime($statistic['oldest_ungraded_assignment_due_date']);
	$now = new DateTime();
	$oldestUngradedAssignment = buildPageSection('<p><a target="_blank" href="' . $statistic['oldest_ungraded_assignment_url'] . '">' . $assignmentInfo['name'] . '</a>, due ' . $oldestDueDate->format('l, F j, Y') . ' (' . $now->diff($oldestDueDate)->format('%a') . ' days old)</p>

				<p>The oldest assignment (whose due date is past) for which there is no grade (for which a grade is anticipated: i.e. not a zero-point assignment or an ungraded assignment).</p>', 'Oldest Ungraded Assignment', 'oldest-ungraded-assignment');
}

displayPage($introduction . $turnAroundComparison . $turnAroundHistory . (isset($oldestUngradedAssignment) ? $oldestUngradedAssignment : '') . $assignmentCount);

?>