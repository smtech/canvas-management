<?php

require_once(__DIR__ . '/../common.inc.php');

$advisorDashboard = new mysqli(
	(string) $secrets->mysql->advisordashboard->host,
	(string) $secrets->mysql->advisordashboard->username,
	(string) $secrets->mysql->advisordashboard->password,
	(string) $secrets->mysql->advisordashboard->database
);

$smarty->addTemplateDir(__DIR__ . '/templates', basename(__DIR__));

?>