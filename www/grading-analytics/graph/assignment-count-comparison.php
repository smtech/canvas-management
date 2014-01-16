<?php

require_once(__DIR__ . '/../../config.inc.php');
require_once(__DIR__ . '/../config.inc.php');
require_once(__DIR__ . '/../common.inc.php');
require_once(__DIR__ . '/../.ignore.grading-analytics-authentication.inc.php');
require_once(APP_PATH . '/include/mysql.inc.php');
require_once(APP_PATH . '/include/phpgraphlib.php');

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
	while ($row = $stats->fetch_assoc()) {
		$data[$row['course[id]']] = $row['assignments_due_count'] + $row['dateless_assignment_count'];
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
	$graph->setBarColor(GRAPH_DATA_COLOR, GRAPH_HIGHLIGHT_COLOR);
	$graph->setBarOutline(false);
	$graph->setGoalLine(averageAssignmentCount(
		(
			isset($_REQUEST['department_id'])) ?
				$_REQUEST['department_id'] :
				false
		),
		GRAPH_AVERAGE_COLOR,
		GRAPH_AVERAGE_STYLE
	);
	$graph->setGrid(false);
	$graph->setXValues(false);
	$graph->createGraph();
}

?>