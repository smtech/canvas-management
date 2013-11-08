<?php

require_once('.ignore.grading-analytics-authentication.inc.php');
define('TOOL_START_PAGE', 'https://' . parse_url(CANVAS_API_URL, PHP_URL_HOST) . "/courses/{$_REQUEST['course_id']}");
define('TOOL_START_LINK', 'Return to Canvas');

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
			border-bottom: 1px ' . GRAPH_AVERAGE_COLOR . ' ' . GRAPH_AVERAGE_STYLE . ';
		}
		
		.one-week-underline {
			border-bottom: 1px ' . GRAPH_1_WEEK_COLOR . ' ' . GRAPH_1_WEEK_STYLE . ';
		}
		
		.two-week-underline {
			border-bottom: 1px ' . GRAPH_2_WEEK_COLOR . ' ' . GRAPH_2_WEEK_STYLE . ';
		}
		
		.highlight-column:after {
			content: " \258C";
			color: ' . GRAPH_HIGHLIGHT_COLOR . ';
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
			<p class="caption">&ldquo;' . $course['name'] . '&rdquo; compared to all active courses in the school.</p>
		</a>
	</div>
	
	<div class="image-placement" style="float: right; width: ' . GRAPH_INSET_WIDTH . ';">
		<h4>' . $department['name'] . ' Courses</h4>
		<a
			data-lightbox="turn-around-comparison"
			title="The average grading turn-around time in &ldquo;' . $course['name'] . '&rdquo; compared to all active courses in the ' . $department['name'] . ' department."
			href="graph/turn-around-comparison.php?course_id=' . $_REQUEST['course_id'] . '&department_id=' . $course['account_id'] . '"
		>
			<img src="graph/turn-around-comparison.php?course_id=' . $_REQUEST['course_id'] . '&department_id=' . $course['account_id'] . '"  style="width: 100%;" />
			<p class="caption">&ldquo;' . $course['name'] . '&rdquo; compared to all active courses in the ' . $department['name'] . ' department.</p>
		</a>
	</div>	

	<p class="caption">What is the average turn-around time (in days) in this course for an assignment, from due date to posted grade in Canvas? The expectation articulated in the faculty handbook is that daily assignments will be returned within a week (<span class="one-week-underline">' . GRAPH_1_WEEK_STYLE . ' ' . GRAPH_1_WEEK_COLOR . ' line</span>) and that major assignments will be returned within two weeks (<span class="two-week-underline">' . GRAPH_2_WEEK_STYLE . ' ' . GRAPH_2_WEEK_COLOR . ' line</span>). The expectation is that grades will be posted to Canvas at approximately the same time as they are returned to students.</p>
	
	<p class="caption">The average turn-around time across all courses (<span class="average-underline">' . GRAPH_AVERAGE_STYLE . ' ' . GRAPH_AVERAGE_COLOR . ' line</span> above), weighted by number of graded assignments and students, is ' . round(averageTurnAround(), 1) . ' days. The average turn-around time across ' . $department['name'] . ' courses (<span class="average-underline">' . GRAPH_AVERAGE_STYLE . ' ' . GRAPH_AVERAGE_COLOR . ' line</span> at right) is ' . round(averageTurnAround($department['id']), 1) . ' days. In &ldquo;' . $course['name'] . '&rdquo; (<span class="highlight-column"></span>' . GRAPH_HIGHLIGHT_COLOR . ' column), the current average is ' . round($statistic['average_grading_turn_around'], 1) . ' days.</p>
	
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
			<p class="caption">The average grading turn-around time in &ldquo;' . $course['name'] . '&rdquo; over time.</p>
		</a>
	</div>
	
	<p class="caption">How is the average grading turn-around time changing in this course over time? Both the daily assignment expectation (<span class="one-week-underline">' . GRAPH_1_WEEK_STYLE . ' ' . GRAPH_1_WEEK_COLOR . ' line</span>) and the major assignment expectation (<span class="two-week-underline">' . GRAPH_2_WEEK_STYLE . ' ' . GRAPH_2_WEEK_COLOR . ' line</span>) are shown for reference.</p>
	
	<h4>What can be learned from this information?</h4>
	
	<ul>
		<li>How is this average changing over time? Be aware that it will change more gradually as the year progresses and more assignments are incorporated into the average.</li>
		
		<li>How does the average relate to the expectations for faculty grading? Again, be aware that the average grading turn-around time is a measure not of the actual turn-in to student time, but of the time measured between the due date and when the grade is actually entered in Canvas.</li>
	</ul>',
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
			<p class="caption">&ldquo;' . $course['name'] . '&rdquo; compared to all active courses in the school.</p>
		</a>
	</div>

	<div class="image-placement" style="width: ' . GRAPH_INSET_WIDTH . '; float: right;">
		<h4>' . $department['name'] . ' Courses</h4>
		<a
			data-lightbox="assignment-count"
			title="The number of assignments posted in Canvas for &ldquo;' . $course['name'] . '&rdquo; compared to all active courses in the ' . $department['name'] . ' department."
			href="graph/assignment-count-comparison.php?course_id=' . $_REQUEST['course_id'] . '&department_id=' . $course['account_id'] . '"
		>
			<img src="graph/assignment-count-comparison.php?course_id=' . $_REQUEST['course_id'] . '&department_id=' . $course['account_id'] . '" width="100%" />
			<p class="caption">&ldquo;' . $course['name'] . '&rdquo; compared to all active courses in the ' . $department['name'] . ' department.</p>
		</a>
	</div>
	
	<p class="caption">How many assignments have been posted to Canvas for &ldquo;' . $course['name'] . '&rdquo;?</p>
	
	<p class="caption">The average number of assignments posted to Canvas (<span class="average-underline">' . GRAPH_AVERAGE_STYLE . ' ' . GRAPH_AVERAGE_COLOR .' line</span> above) across all courses is ' . round(averageAssignmentCount(), 1) . '. The average number of assignments posted to Canvas (<span class="average-underline">' . GRAPH_AVERAGE_STYLE . ' ' . GRAPH_AVERAGE_COLOR . ' line</span> at right) in the ' . $department['name'] . ' department is ' . round(averageAssignmentCount($department['id']), 1) . '. In &ldquo;' . $course['name'] . '&rdquo; (<span class="highlight-column"></span>' . GRAPH_HIGHLIGHT_COLOR . ' column), there are ' . ($statistic['assignments_due_count'] + $statistic['dateless_assignment_count']) . ' assignments posted to Canvas.</p>
	
	<h4>What can be learned from this information?</h4>
	
	<ul>
		<li>How do the number of assignments posted to Canvas in this course compare to other courses? This is probably more illuminating in the departmental comparison than in the overall comparison.</li>
		
		<li>Is this number higher than most? This may reflect either a higher homework load (at least, measured by quantity of assignments), or it may reflect a greater granularity in posted assignments. That is, rather than combining all of one night&rsquo;s homework into a single assignment, homework may be posted as a number of individual assignments.</li>
		
		<li>Is this number lower than most? This may reflect a lighter homework load (at least, measured by quantity of assignments -- each individual assignment may take longer). This may also reflect incomplete Canvas updates in this course, with not all assignments posted to Canvas.</li>
		
		<li>How does the number of assignments posted compare to the number of class meetings?</li>
	</ul>
	
	<br clear="all" />',
	'Number of Assignments',
	'assignment-count');

$numbers = false;

if (strlen($statistic['oldest_ungraded_assignment_url'])) {
	preg_match('@.*\/assignments\/(\d+)@', $statistic['oldest_ungraded_assignment_url'], $matches);
	$assignmentInfo = callCanvasApi(
		CANVAS_API_GET,
		"/courses/{$_REQUEST['course_id']}/assignments/{$matches[1]}"
	);
	
	$oldestDueDate = new DateTime($statistic['oldest_ungraded_assignment_due_date']);
	$now = new DateTime();
	$numbers .= '
		<h3>Oldest ungraded assignment is ' . $now->diff($oldestDueDate)->format('%a') . ' days old</h3>
		
		<div class="image-placement">
			<p class="caption"><a style="border-bottom: dotted 1px black;" target="_blank" href="' . $statistic['oldest_ungraded_assignment_url'] . '">' . $assignmentInfo['name'] . '</a>, due ' . $oldestDueDate->format('l, F j, Y') . ' was due ' . $now->diff($oldestDueDate)->format('%a') . ' days ago and no submissions have been graded.</p>
		</div>
				
		<h4>What could this mean?</h4>
		
		<p>This is the oldest assignment (sorted by due date) for which no student submissions have received a grade. This could mean exactly what it appears to mean. However, it may also mean that this was a zero-point or ungraded assignment &mdash; that is, an assignment that was <a href="http://stmarks-tech-tips.blogspot.com/2013/10/how-do-i-create-ungraded-assignment.html" target="_blank">never <i>meant</i> to receive a grade</a> &mdash; that was mismarked.</p>';
} else {
	$numbers .= '<h3>No assignment is ungraded</h3>';
}

if (round($statistic['average_submissions_graded']*100, 0) < 100) {
	$numbers .= '
		<h3>' . round($statistic['average_submissions_graded']*100, 0) . '% of submissions graded</h3>
		
		<div class="image-placement">
			<p class="caption">For each assignment in &ldquo;' . $course['name'] . '&rdquo;, on average ' . round($statistic['average_submissions_graded']*100, 0) . '% of the student submissions have been graded.</p>
		</div>
		
		<h4>What could this mean?</h4>
		
		<p>The quick interpretation of this statistic is that ' . round((1-$statistic['average_submissions_graded'])*100, 0) . '% of assignments still need to be graded in this class. However, this number may also be influenced by extra credit assignments (for which not all students submitted work) or by the presence of the <a target="_blank" href="https://stmarksschool.instructure.com/courses/489/wiki/who-is-this-test-student">Test Student</a> in the class (who may or may not have turned in work or been graded... and it doesn&rsquo;t particularly matter). This percentage could also be dragged down by zero-point assignments that were <a href="http://stmarks-tech-tips.blogspot.com/2013/10/how-do-i-create-ungraded-assignment.html" target="_blank">never meant to be graded</a>, but are not formally marked as ungraded (and therefore appear in the gradebook).</p>';
} else {
	$numbers .= '<h3>100% of submissions graded</h3>';
}

if ($statistic['zero_point_assignment_count'] > 0) {
	$numbers .= '
		<h3>' . $statistic['zero_point_assignment_count'] . ' zero-point assignments</h3>
		
		<div class="image-placement">
			<p class="caption">There are ' . $statistic['zero_point_assignment_count'] . ' assignments worth zero points in &ldquo;' . $course['name'] . '&rdquo;.</p>
		</div>
		
		<h4>What could this mean?</h4>
		
		<p>Zero-point assignments are often (but not <i>always</i>) assignments that were never meant to receive a grade: reading, practice, etc. In this case, the teacher could <a href="http://stmarks-tech-tips.blogspot.com/2013/10/how-do-i-create-ungraded-assignment.html" target="_blank">remove this unnecessary column from the gradebook</a> while still preserving it as a to-do item for their students. However, these may also be assignments for which a value has not yet been determined. It may be worth considering if this could be useful information to support student planning.</p>';
} else {
	$numbers .= '<h3>No zero-point assignments</h3>';
}

	
if ($statistic['dateless_assignment_count'] > 0) {
	$numbers .= '
		<h3>' . $statistic['dateless_assignment_count'] . ' assignments without due dates</h3>
		
		<div class="image-placement">
			<p class="caption">There are ' . $statistic['dateless_assignment_count'] . ' assignments without due dates in &ldquo;' . $course['name'] . '&rdquo;.</p>
		</div>
		
		<h4>What could this mean?</h4>
		
		<p>This pretty much means what it sounds like it means: these assignments lack due dates. In terms of supporting student planning, this is not a terribly supportive practice.</p>';
} else {
	$numbers .= '<h3>All assignments have due dates</h3>';
}
	
$numbers = buildPageSection($numbers, '(Potentially) Interesting Numbers', 'numbers');

$questions = buildPageSection('
	<p>Do you have any questions about the information shown here? Do you want to see different data that is not presented here? There is lots more information about Canvas, how to use it and how we are trying to use it.</p>
	
	<ul>
		<li><a target="_blank" href="https://stmarksschool.instructure.com/courses/489">Canvas Training</a> course online</li>
		<li><a target="_blank" href="http://guides.instructure.com/">Canvas Guides</a> to just about everything</li>
		<li><a target="_blank" href="http://stmarks-tech-tips.blogspot.com/search/label/Canvas">Tech Tips</a> specific to St. Mark&rsquo;s</li>
		<li><a target="_blank" href="https://stmarksschool.instructure.com/courses/97/wiki/academic-technology">Academic Technology wiki</a> links to lots of other germane information</li>
	</ul>
	
	<p>Please contact Brian or Seth with specific questions, and we will endeavor to explicate, disentangle and otherwise address your concerns.</p>',
	'Questions?',
	'questions'
);

displayPage($introduction . $turnAroundComparison . $turnAroundHistory . $assignmentCount . $numbers . $questions);

?>