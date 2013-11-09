<?php

require_once(__DIR__ . '/../config.inc.php');

define('DEBUGGING_NONE', 0);

define('DEBUGGING_GENERAL', 1);
define('DEBUGGING_LOG', 2);
define('DEBUGGING_CANVAS_API', 4);
define('DEBUGGING_MYSQL', 8);

define('DEBUGGING_ALL', DEBUGGING_GENERAL |
						DEBUGGING_LOG |
						DEBUGGING_CANVAS_API |
						DEBUGGING_MYSQL);
						
define('DEBUGGING_DEFAULT', DEBUGGING_GENERAL);

/**
 * Helper function to conditionally fill the log file with notes!
 **/
function debug_log($message) {
	if (!defined('DEBUGGING')) {
		define('DEBUGGING', DEBUGGING_DEFAULT);
	}
	if (DEBUGGING & DEBUGGING_LOG) {
		error_log($message);
	}
}

/**
 * Render a console-friendly version of the toolname
 **/
function getToolNameForConsole() {
	$toolNameForConsole = (defined('TOOL_NAME_ABBREVIATION') ? TOOL_NAME_ABBREVIATION :TOOL_NAME);

	return str_replace(
		array(
			'&larr;',
			'&rarr;',
			'&harr;',
			'&amp;',
			'&ldquo;',
			'&rdquo;',
			'&lsquo;',
			'&rsquo;'
		),
		array(
			'<--',
			'-->',
			'<-->',
			'&',
			'"',
			'"',
			"'",
			"'"
		),
		$toolNameForConsole);
}

/**
 * Log script start
 **/
$DEBUG_FLAG_TAG = null;
function debugFlag($message, $tag = null) {
	global $DEBUG_FLAG_TAG;
	if (!isset($DEBUG_FLAG_TAG)) {
		$DEBUG_FLAG_TAG = (isset($tag) ? $tag : time());
	}
	debug_log(getToolNameForConsole() . " $DEBUG_FLAG_TAG $message");
}


?>