<?php

$MYSQLi = new mysqli(MYSQL_HOST, MYSQL_USER, MYSQL_PASSWORD, MYSQL_DATABASE);
function mysqlQuery($query) {
	return $GLOBALS['MYSQLi']->query($query);
}

function mysqlEscapeString($string) {
	return $GLOBALS['MYSQLi']->escape_string($string);
}

function mysqlError() {
	return $GLOBALS['MYSQLi']->error;
}

?>