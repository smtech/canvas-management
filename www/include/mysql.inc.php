<?php

require_once(__DIR__ . '/../config.inc.php');
require_once(APP_PATH . '/include/debug.inc.php');
require_once(APP_PATH . '/include/page-generator.inc.php');

$MYSQL = new mysqli(MYSQL_HOST, MYSQL_USER, MYSQL_PASSWORD, MYSQL_DATABASE);
function mysqlQuery($query) {
	try {
		$response = $GLOBALS['MYSQL']->query($query);
	} catch (Exception $e) {
		displayError(
			array(
				'Query' => $query,
				'Exception' => $e->getMessage(),
				'Error' => $GLOBALS['MYSQL']->error
			), true,
			'MySQL Exception'
		);
		exit;
	}
	
	displayError(
		array(
			'Query' => $query,
			'Response' => (
				$response ?
					'True' :
					mysqlError()
			)
		),
		true,
		'MySQL Query',
		null,
		DEBUGGING_MYSQL
	);
	
	return $response;
}

function mysqlEscapeString($string) {
	return $GLOBALS['MYSQL']->real_escape_string($string);
}

function mysqlError() {
	return $GLOBALS['MYSQL']->error;
}

?>