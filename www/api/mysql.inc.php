<?php

require_once('debug.inc.php');

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
	
	if (DEBUGGING) {
		displayError(
			array(
				'Query' => $query,
				'Response' => (
					is_array($response) ?
						$response :
						(
							$response ?
								'True' :
								mysqlError()
						)
					)
			), true,
			'MySQL Query'
		);
	}
	
	return $response;
}

function mysqlEscapeString($string) {
	return $GLOBALS['MYSQL']->real_escape_string($string);
}

function mysqlError() {
	return $GLOBALS['MYSQL']->error;
}

?>