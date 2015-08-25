<?php

use Battis\HiearchicalSimpleCache as HierarchicalSimpleCache;

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

/**
 * Get a listing of all accounts organized for presentation in a select picker
 *
 * @return array
 **/
function getAccountList() {
	global $sql; // FIXME grown-ups don't code like this
	global $api; // FIXME grown-ups don't code like this
		
	$cache = new HierarchicalSimpleCache($sql, basename(__FILE__, '.php'));
	
	$accounts = $cache->getCache('accounts');
	if ($accounts === false) {
		$accounts = $api->get('accounts/1/sub_accounts', array('recursive' => 'true'));
		$cache->setCache('accounts', $accounts, 7 * 24 * 60 * 60);
	}
	return $accounts;
}

/**
 * Get a listing of all terms organized for presentation in a select picker
 *
 * @return array
 **/
function getTermList() {
	global $sql; // FIXME grown-ups don't code like this
	global $api; // FIXME grown-ups don't code like this
		
	$cache = new HierarchicalSimpleCache($sql, basename(__FILE__, '.php'));
	
	$terms = $cache->getCache('terms');
	if ($terms === false) {
		$_terms = $api->get(
			'accounts/1/terms',
			array(
				'workflow_state' => 'active'
			)
		);
		$terms = $_terms['enrollment_terms'];
		$cache->setCache('terms', $terms, 7 * 24 * 60 * 60);
	}
	return $terms;
}

$api = new CanvasPest($_SESSION['apiUrl'], $_SESSION['apiToken']);

$smarty->assign('navbarActive', basename(dirname($_SERVER['REQUEST_URI'])));
$smarty->assign('formAction', $_SERVER['PHP_SELF']);

?>