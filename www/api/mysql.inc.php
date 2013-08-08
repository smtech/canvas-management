<?php

$MYSQLi = new mysqli(MYSQL_HOST, MYSQL_USER, MYSQL_PASSWORD, MYSQL_DATABASE);
function mysqlQuery($query) {
	return $GLOBALS['MYSQLi']->query($query);
}

?>