<?php

try {
	$canvasManagement = CanvasManagement::getInstance();
} catch (CanvasManagement_Exception $e) {
	if ($e->getCode() === CanvasManagement_Exception::INVALID_USER) {
		$smarty->assign('content', '<h1>' . $toolProvider->user->fullname . '</h1><p>Only root-level account administrators may access this panel.</p>');
		$smarty->display();
		exit;
	} else {
		throw $e;
	}
}

function html_var_dump($var) {
	echo '<pre>';
	var_dump($var);
	echo '</pre>';
}

?>