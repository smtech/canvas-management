<?php
	
require_once(__DIR__ . '/../common.inc.php');

$smarty->assign('category', 'Enrollment Management');
$smarty->addTemplateDir(__DIR__ . '/templates', basename(__DIR__));

?>