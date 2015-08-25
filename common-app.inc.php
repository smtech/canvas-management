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
 * Load an uploaded CSV file into an associative array
 *
 * @param string $field Field name holding the file name
 * @param boolean $firstRowLabels (Optional) Default `TRUE`
 *
 * @return string[][]|boolean A two-dimensional array of string values, if the
 *		`$field` contains a CSV file, `FALSE` if there is no file
 **/
function loadCsvToArray($field, $firstRowLabels = true) {
	$result = false;
	if(!empty($_FILES[$field]['tmp_name'])) {

		/* open the file for reading */
		$csv = fopen($_FILES[$field]['tmp_name'], 'r');
		$result = array();
		
		/* treat the first row as column labels */
		if ($firstRowLabels) {
			$fields = fgetcsv($csv);
		}
		
		/* walk through the file, storing each row in the array */
		while($csvRow = fgetcsv($csv)) {
			$row = array();
			
			/* if we have column labels, use them */
			if ($firstRowLabels) {
				foreach ($fields as $i => $field) {
					if (isset($csvRow[$i])) {
						$row[$field] = $csvRow[$i];
					}
				}
			} else {
				$row = $csvRow;
			}
			
			/* append the row to the array */
			$result[] = $row;
		}
		fclose($csv);
	}
	return $result;
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