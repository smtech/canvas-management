<?php

/**
 * Explode a string
 *
 * Explode into comma- and newline-delineated parts, and trim those parts.
 *
 * @param string $str
 *
 * @return string[]
 **/
function explodeCommaAndNewlines($str) {
	$list = array();
	$lines = explode("\n", $str);
	foreach ($lines as $line) {
		$items = explode(',', $line);
		foreach ($items as $item) {
			$trimmed = trim($item);
			if (!empty($trimmed)) {
				$list[] = $trimmed;
			}
		}
	}
	return $list;
}

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

$smarty->assign('navbarActive', basename(dirname($_SERVER['REQUEST_URI'])));
$smarty->assign('formAction', $_SERVER['PHP_SELF']);

?>