<?php

require_once('config.inc.php');

$GRAPH_DATA_COUNT = 0;
function graphWidth($dataCount = false) {
	if ($dataCount) {
		$GLOBALS['GRAPH_DATA_COUNT'] = $dataCount;
	}
	return max(GRAPH_MIN_WIDTH, $GLOBALS['GRAPH_DATA_COUNT'] * GRAPH_BAR_WIDTH);
}

function graphHeight($dataCount) {
	if ($dataCount) {
		$GLOBALS['GRAPH_DATA_COUNT'] = $dataCount;
	}
	return graphWidth() * GRAPH_ASPECT_RATIO;
}