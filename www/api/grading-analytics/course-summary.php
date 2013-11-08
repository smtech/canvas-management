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
		.average-underline {
			border-bottom: 1px ' . GRAPH_AVERAGE_STYLE . ' ' . GRAPH_AVERAGE_COLOR . ';
		}
		
		.one-week-underline {
			border-bottom: 1px ' . GRAPH_1_WEEK_STYLE . ' ' . GRAPH_1_WEEK_COLOR . ';
		}
		
		.two-week-underline {
			border-bottom: 1px ' . GRAPH_2_WEEK_STYLE . ' ' . GRAPH_2_WEEK_COLOR . ';
		}
		
		.highlight-column {
			color: white;
			background-color: ' . GRAPH_HIGHLIGHT_COLOR . ';
			padding: 0 2px;
		}
	</style>
	
	<h1>' . $course['name'] . '</h1>
	
	<p>This summary is provided as a means towards starting a conversation, rather than as a final assessment of work done. As you look at this data, be cognizant of the fact that it reflects specific interactions with Canvas and is <i>not</i> direct evidence of either teaching quality or teacher-student interactions. Where possible, relevant questions have been posed with the data for reflective consideration.</p>
	
	<p>Click on any graph to zoom for fuller detail.</p>
	
	<p>Data collection for these analytics is done at midnight every night, so statistics do not yet reflect grading done today.</p>',
	false,
	'introduction'
);

$turnAroundComparison = buildPageSection('
	<div class="image-placement">
		<h4>All Courses</h4>
		<a
			data-lightbox="turn-around-comparison"
			title="The average grading turn-around time in &ldquo;' . $course['name'] . '&rdquo; compared to all active courses in the school."
			href="graph/turn-around-comparison.php?course_id=' . $_REQUEST['course_id'] . '"
		>
			<img src="graph/turn-around-comparison.php?course_id=' . $_REQUEST['course_id'] . '"  style="width: 100%;" />
		</a>
		<p class="caption">&ldquo;' . $course['name'] . '&rdquo; compared to all active courses in the school.</p>
	</div>
	
	<div class="image-placement" style="float: right; width: ' . GRAPH_INSET_WIDTH . ';">
		<h4>' . $department['name'] . ' Courses</h4>
		<a
			data-lightbox="turn-around-comparison"
			title="The average grading turn-around time in &ldquo;' . $course['name'] . '&rdquo; compared to all active courses in the ' . $department['name'] . ' department."
			href="graph/turn-around-comparison.php?course_id=' . $_REQUEST['course_id'] . '&department_id=' . $course['account_id'] . '"
		>
			<img src="graph/turn-around-comparison.php?course_id=' . $_REQUEST['course_id'] . '&department_id=' . $course['account_id'] . '"  style="width: 100%;" />
		</a>
		<p class="caption">&ldquo;' . $course['name'] . '&rdquo; compared to all active courses in the ' . $department['name'] . ' department.</p>
	</div>	

	<p class="caption">What is the average turn-around time (in days) in this course for an assignment, from due date to posted grade in Canvas? The expectation articulated in the faculty handbook is that daily assignments will be returned within a week (<span class="one-week-underline">' . GRAPH_1_WEEK_COLOR . ' ' . GRAPH_1_WEEK_STYLE . ' line</span>) and that major assignments will be returned within two weeks (<span class="two-week-underline">' . GRAPH_2_WEEK_COLOR . ' ' . GRAPH_2_WEEK_STYLE . ' line</span>). The expectation is that grades will be posted to Canvas at approximately the same time as they are returned to students.</p>
	
	<p class="caption">The average turn-around time across all courses (<span class="average-underline">' . GRAPH_AVERAGE_COLOR . ' ' . GRAPH_AVERAGE_STYLE . ' line</span> above), weighted by number of graded assignments and students, is ' . round(averageTurnAround(), 1) . ' days. The average turn-around time across ' . $department['name'] . ' courses (<span class="average-underline">' . GRAPH_AVERAGE_COLOR . ' ' . GRAPH_AVERAGE_STYLE . ' line</span> at right) is ' . round(averageTurnAround($department['id']), 1) . ' days. In &ldquo;' . $course['name'] . '&rdquo; (<span class="highlight-column">' . GRAPH_HIGHLIGHT_COLOR . ' column</span>), the current average is ' . round($statistic['average_grading_turn_around'], 1) . ' days.</p>
	
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

$turnAroundHistory = buildPageSection('
	<div class="image-placement">
		<a
			data-lightbox="turn-around-history"
			title="The average grading turn-around time in &ldquo;' . $course['name'] . '&rdquo; over time."
			href="graph/turn-around-history.php?course_id=' . $_REQUEST['course_id'] . '"
		>
			<img src="graph/turn-around-history.php?course_id=' . $_REQUEST['course_id'] . '" width="100%" />
		</a
		<p class="caption">The average grading turn-around time in &ldquo;' . $course['name'] . '&rdquo; over time.</p>
	</div>',
	'Grading Turn-Around History',
	'turn-around-history'
);

$assignmentCount = buildPageSection('
	<div class="image-placement">
		<h4>All Courses</h4>
		<a
			data-lightbox="assignment-count"
			title="The number of assignments posted in Canvas for &ldquo;' . $course['name'] . '&rdquo; compared to all active courses in the school."
			href="graph/assignment-count-comparison.php?course_id=' . $_REQUEST['course_id'] . '"
		>
			<img src="graph/assignment-count-comparison.php?course_id=' . $_REQUEST['course_id'] . '" width="100%" />
		</a>
		<p class="caption">&ldquo;' . $course['name'] . '&rdquo; compared to all active courses in the school.</p>
	</div>

	<div class="image-placement" style="width: ' . GRAPH_INSET_WIDTH . '; float: right;">
		<h4>' . $department['name'] . ' Courses</h4>
		<a
			data-lightbox="assignment-count"
			title="The number of assignments posted in Canvas for &ldquo;' . $course['name'] . '&rdquo; compared to all active courses in the ' . $department['name'] . ' department."
			href="graph/assignment-count-comparison.php?course_id=' . $_REQUEST['course_id'] . '&department_id=' . $course['account_id'] . '"
		>
			<img src="graph/assignment-count-comparison.php?course_id=' . $_REQUEST['course_id'] . '&department_id=' . $course['account_id'] . '" width="100%" />
		</a>
		<p class="caption">&ldquo;' . $course['name'] . '&rdquo; compared to all active courses in the ' . $department['name'] . ' department.</p>
	</div>
	
	<p class="caption">How many assignments have been posted to Canvas for &ldquo;' . $course['name'] . '&rdquo;?</p>
	
	<p class="caption">The average number of assignments posted to Canvas (<span class="average-underline">' . GRAPH_AVERAGE_COLOR . ' ' . GRAPH_AVERAGE_STYLE .' line</span> above) across all courses is ' . round(averageAssignmentCount(), 1) . '. The average number of assignments posted to Canvas (<span class="average-underline">' . GRAPH_AVERAGE_COLOR . ' ' . GRAPH_AVERAGE_STYLE . ' line</span> at right) in the ' . $department['name'] . ' department is ' . round(averageAssignmentCount($department['id']), 1) . '. In &ldquo;' . $course['name'] . '&rdquo; (<span class="highlight-column">' . GRAPH_HIGHLIGHT_COLOR . ' column</span>), there are ' . ($statistic['assignments_due_count'] + $statistic['dateless_assignment_count']) . ' assignments posted to Canvas.</p>
	
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