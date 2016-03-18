<?php

use Battis\BootstrapSmarty\NotificationMessage;
use smtech\CanvasPest\CanvasPest;

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
 * Explode a string
 *
 * Explode into trimmed lines
 *
 * @param string $str
 *
 * @return string[]
 **/
function explodeNewLines($str) {
	$list = array();
	$lines = explode("\n", $str);
	foreach($lines as $line) {
		$trimmed = trim($line);
		if (!empty($trimmed)) {
			$list[] = $trimmed;
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
		
	$cache = new \Battis\HierarchicalSimpleCache($sql, basename(__FILE__, '.php'));
	
	$accounts = $cache->getCache('accounts');
	if ($accounts === false) {
		$accountsResponse = $api->get('accounts/1/sub_accounts', array('recursive' => 'true'));
		$accounts = array();
		foreach ($accountsResponse as $account) {
			$accounts[$account['id']] = $account;
		}
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
		
	$cache = new \Battis\HierarchicalSimpleCache($sql, basename(__FILE__, '.php'));
	
	$terms = $cache->getCache('terms');
	if ($terms === false) {
		$_terms = $api->get(
			'accounts/1/terms',
			array(
				'workflow_state' => 'active'
			)
		);
		$termsResponse = $_terms['enrollment_terms'];
		$terms = array();
		foreach ($termsResponse as $term) {
			$terms[$term['id']] = $term;
		}
		$cache->setCache('terms', $terms, 7 * 24 * 60 * 60);
	}
	return $terms;
}

/**
 * A standard format for an error message due to an exception
 *
 * @param \Exception $e
 *
 * @return void
 **/
function exceptionErrorMessage($e) {
	global $smarty; // FIXME grown-ups don't code like this
	global $api; // FIXME grown-ups don't code like this
	$smarty->addMessage(
		'Error ' . $e->getCode(),
		'<p>Last API Request</p><pre>' . print_r($api->last_request, true) . '</pre><p>Last Headers</p><pre>' . print_r($api->last_headers, true) . '</pre><p>Error Message</p><pre>' . $e->getMessage() .'</pre>',
		NotificationMessage::ERROR
	);
}

$_SESSION['canvasInstanceUrl'] = 'https://' . $_SESSION['toolProvider']->user->getResourceLink()->settings['custom_canvas_api_domain'];
$api = new CanvasPest($_SESSION['apiUrl'], $_SESSION['apiToken']);

$smarty->assign('navbarActive', basename(dirname($_SERVER['REQUEST_URI'])));

?>