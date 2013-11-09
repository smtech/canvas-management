<?php

require_once(__DIR__ . '/../config.inc.php');
require_once(APP_PATH . '/include/class/CanvasApiProcess.class.php');

$CANVAS_API = new CanvasApiProcess(CANVAS_API_URL, CANVAS_API_TOKEN);

function callCanvasApiNextPage() {
	return $GLOBALS['CANVAS_API']->nextPage();
}

function callCanvasApiPrevPage() {
	return $GLOBALS['CANVAS_API']->prevPage();
}

function callCanvasApiFirstPage() {
	return $GLOBALS['CANVAS_API']->firstPage();
}

function callCanvasApiLastPage() {
	return $GLOBALS['CANVAS_API']->lastPage();
}

function getCanvasApiNextPageNumber() {
	return $GLOBALS['CANVAS_API']->getNextPageNumber();
}

function getCanvasApiPrevPageNumber() {
	return $GLOBALS['CANVAS_API']->getPrevPageNumber();
}

function getCanvasApiFirstPageNumber() {
	return $GLOBALS['CANVAS_API']->getFirstPageNumber();
}

function getCanvasApiLastPageNumber() {
	return $GLOBALS['CANVAS_API']->getLastPageNumber();
}

function getCanvasApiCurrentPageNumber() {
	return $GLOBALS['CANVAS_API']->getCurrentPageNumber();
}

function callCanvasApi($verb, $url, $data = array(), $throwingExceptions = false) {
	return $GLOBALS['CANVAS_API']->call($verb, $url, $data, $throwingExceptions);
}

function callCanvasApiPaginated($verb, $url, $data = array(), $throwingExceptions = false) {
	return $GLOBALS['CANVAS_API']->call($verb, $url, $data, $throwingExceptions);
}

?>