<?php
	
require_once(__DIR__ . '/../common.inc.php');

$smarty->addTemplateDir(__DIR__ . '/templates', basename(__DIR__));
$smarty->assign('formButton', 'Punch it, Chewie! <span class="fa fa-rocket"></span>')

?>