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

$api = new CanvasPest($_SESSION['apiUrl'], $_SESSION['apiToken']);

$smarty->assign('navbarActive', basename(dirname($_SERVER['REQUEST_URI'])));
$smarty->assign('formAction', $_SERVER['PHP_SELF']);

?>