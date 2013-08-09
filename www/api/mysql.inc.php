<?php

require_once('debug.inc.php');

$MYSQLi = new mysqli(MYSQL_HOST, MYSQL_USER, MYSQL_PASSWORD, MYSQL_DATABASE);
function mysqlQuery($query) {
	try {
		$response = $GLOBALS['MYSQLi']->query($query);
	} catch (Exception $e) {
		displayError(
			array(
				'Query' => $query,
				'Exception' => $e->getMessage(),
				'Error' => $GLOBALS['MYSQLi']->error
			), true,
			'MySQL Exception'
		);
		exit;
	}
	
	if (DEBUGGING) {
		displayError(
			array(
				'Query' => $query,
				'Response' => $response
			), true,
			'MySQL Query'
		);
	}
	
	return $response;
}

function mysqlEscapeString($string) {
	return $GLOBALS['MYSQLi']->escape_string($string);
}

function mysqlError() {
	return $GLOBALS['MYSQLi']->error;
}

?>