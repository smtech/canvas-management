<?php

if (!defined('TOOL_NAME')) {
	define('TOOL_NAME', 'Canvas API Tool');
}
if (!defined('TOOL_START_PAGE')) {
	define('TOOL_START_PAGE', $_SERVER['PHP_SELF']);
}

/**
 * Echo a page of HTML content to the browser, wrapped in some CSS niceities
 **/
function displayPage($content) {
	echo '<html>
<head>
	<title>' . TOOL_NAME . '</title>
	<link rel="stylesheet" href="../script-ui.css" />
</head>
<body>
<h1>' . TOOL_NAME . '</h1>
<h2>St. Mark&rsquo;s School</h2>
<div id="header">
	<a href="' . TOOL_START_PAGE . '">Start Over</a>
</div>
<div id="content">
'. $content . '
</div>
<div id="footer">
	<a href="http://www.stmarksschool.org">St. Mark&rsquo;s School</a> &bull; <a href="http://area51.stmarksschoo.org">Academic Technology</a> &bull; 25 Marlboro Road, Southborough, MA 01772
</div>
</body>
</html>';
	flushBuffers();
}

/**
 * Because not every script works the right way the first time, and it's handy
 * to get well-formatted error messages
 **/
function displayError($object, $isList = false, $title = null, $message = null) {
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

/**
 * Flush the output buffer for more responsive interface
 * http://www.php.net/manual/en/function.ob-flush.php#90529
 **/
function flushBuffers() { 
    ob_end_flush(); 
    ob_flush(); 
    flush(); 
    ob_start(); 
} 

?>