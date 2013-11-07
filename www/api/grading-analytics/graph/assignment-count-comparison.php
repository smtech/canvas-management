<?php

require_once('../config.inc.php');
require_once('../common.inc.php');
require_once('../.ignore.grading-analytics-authentication.inc.php');
require_once('../../mysql.inc.php');
require_once('../../phpgraphlib.php');

$query = "
	SELECT * FROM (
		SELECT * FROM `course_statistics`" .
			(
				isset($_REQUEST['department_id']) ? "
					WHERE
						`course[account_id]` = '{$_REQUEST['department_id']}'" :
					''
			) . "
			ORDER BY
				`timestamp` DESC
	) AS `stats`
		GROUP BY
			`course[id]` 
";

if ($stats = mysqlQuery($query)) {
	$data = array();
	$total = 0;
	while ($row = $stats->fetch_assoc()) {
		$data[$row['course[id]']] = $row['assignments_due_count'] + $row['dateless_assignment_count'];
		$total += $data[$row['course[id]']];
	}
	asort($data);
	$highlight = $data;
	$data[$_REQUEST['course_id']] = 0;
	while (list($key, $value) = each ($highlight)) {
		if ($key != $_REQUEST['course_id']) {
			$highlight[$key] = 0;
		}
	}
	
	$graph = new PHPGraphLib(graphWidth(count($data)), graphHeight());
	$graph->addData($data);
	$graph->addData($highlight);
	$graph->setBarColor('gray', 'red');
	$graph->setBarOutline(false);
	$graph->setGoalLine($total / count($data), 'gray', 'dashed');
	$graph->setGrid(false);
	$graph->setXValues(false);
	$graph->createGraph();
}

?>