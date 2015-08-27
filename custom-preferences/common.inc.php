<?php

require_once(__DIR__ . '/../common.inc.php');

$customPrefs = new mysqli(
	(string) $secrets->mysql->customprefs->host,
	(string) $secrets->mysql->customprefs->username,
	(string) $secrets->mysql->customprefs->password,
	(string) $secrets->mysql->customprefs->database
);

$smarty->addTemplateDir(__DIR__ . '/templates', basename(__DIR__));

?>