<?php

require_once(__DIR__ . '/../config.inc.php');
require_once(APP_PATH . '/include/config.inc.php');
require_once(APP_PATH . '/include/debug.inc.php');

if (!defined('TOOL_NAME')) {
	define('TOOL_NAME', 'Canvas API Tool');
	debug_log('Using default TOOL_NAME = "' . TOOL_NAME . '"');
}
if (!defined('TOOL_START_PAGE')) {
	define('TOOL_START_PAGE', $_SERVER['PHP_SELF']);
	debug_log('Using default TOOL_START_PAGE = "' . TOOL_START_PAGE . '"');
}
if (!defined('TOOL_START_LINK')) {
	define('TOOL_START_LINK', 'Start Over');
}

function rootPath() {
	$scriptPath = $_SERVER['PATH_TRANSLATED'] 
}

/**
 * Echo a page of HTML content to the browser, wrapped in some CSS niceities
 **/
function displayPage($content) {
	echo '<html>
<head>
	<title>' . TOOL_NAME . '</title>
	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
	<script src="' . APP_URL . '/javascript/lightbox-2.6.min.js"></script>
	<link rel="stylesheet" href="' . APP_URL . '/css/lightbox.php" />
	<link rel="stylesheet" href="' . APP_URL . '/css/script-ui.php" />
</head>
<body>' .
buildPageSection('<h1>' . TOOL_NAME . '</h1>
<h2>St. Mark&rsquo;s School</h2>', false, 'masthead') .
buildPageSection('<a href="' . TOOL_START_PAGE . '">' . TOOL_START_LINK . '</a>', false, 'header') .
'<div id="content-wrapper">' .
	buildPageSection($content, false, 'content') .
'</div>' .
buildPageSection('<a href="' . SCHOOL_URL .'">' . SCHOOL_NAME . '</a> &bull; <a href="' . SCHOOL_DEPT_URL . '">' . SCHOOL_DEPT . '</a> &bull; ' . SCHOOL_ADDRESS, false, 'footer') . '
</body>
</html>';
	flush();
}

$PAGE_SECTIONS = array();
function buildPageSection($content, $label = false, $id = false, $overwrite = false) {
	if ($id) {
		if (array_key_exists($id, $GLOBALS['PAGE_SECTIONS']) && !$overwrite) {
			$i = 0;
			do {
				$i++;
			} while (array_key_exists("$id-$i", $GLOBALS['PAGE_SECTIONS']));
			$id = "$id-$i";
		}
	} else {
		$id = md5($content);
	}
	$GLOBALS['PAGE_SECTIONS'][$id] = "
	<div id=\"$id\" class=\"page-section\">" .
		($label ? "<h2>$label</h2>" : '') . "
		$content
	</div>
	";
	return $GLOBALS['PAGE_SECTIONS'][$id];
}

/**
 * Because not every script works the right way the first time, and it's handy
 * to get well-formatted error messages
 **/
function displayError($object, $isList = false, $title = null, $message = null, $debugLevel = null) {
	if (!defined('DEBUGGING')) {
		define('DEBUGGING', DEBUGGING_DEFAULT);
	}
	if (!isset($debugLevel) || (isset($debugLevel) && (DEBUGGING & $debugLevel))) {
		$content = '<div class="error">' . ($title ? "<h3>$title</h3>" : '') . ($message ? "<p>$message</p>" : '');
		if ($isList) {
			$content .= '<dl>';
			if (array_keys($object) !== range(0, count($object) - 1)) {
				foreach($object as $term => $definition) {
					$content .= "<dt>$term</dt><dd><pre>" . print_r($definition, true) . '</pre></dd>';
				}
			} else {
				foreach($object as $element) {
					$content .= '<dd><pre>' . print_r($element, true) . '</pre></dd>';
				}
			}
			$content .= '</dl>';
		} else {
			$content .= '<pre>' . print_r($object, true) . '</pre>';
		}
		$content .= '</div>';
		displayPage($content);
	}
}

?>