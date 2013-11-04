<?php

require_once('../config.inc.php');
require_once('../.ignore.grading-analytics-authentication.inc.php');
require_once('../../mysql.inc.php');
require_once('../../phpgraphlib.php');

$stats = mysqlQuery("
	SELECT `course[id]`, `average_grading_turn_around` FROM `course_statistics`
	WHERE
		`average_grading_turn_around` > 0
	GROUP BY
		`course[id]`
	ORDER BY
		`timestamp` DESC
");

$data = array();
$total = 0;
while ($row = $stats->fetch_assoc()) {
	$data[$row['course[id]']] = $row['average_grading_turn_around'];
	$total += $row['average_grading_turn_around'];
}
asort($data);
$highlight = $data;
$data[$_REQUEST['course_id']] = 0;
while (list($key, $value) = each ($highlight)) {
	if ($key != $_REQUEST['course_id']) {
		$highlight[$key] = 0;
	}
}

$graph = new PHPGraphLib(1800, 800);
$graph->addData($data);
$graph->addData($highlight);
$graph->setBarColor('gray', 'red');
$graph->setBarOutline(false);
$graph->setGoalLine($total / count($data), 'gray', 'dashed');
$graph->setGoalLine(7, 'lime', 'solid');
$graph->setGoalLine(14, 'red', 'solid');
$graph->setXValues(false);
$graph->createGraph();

?>