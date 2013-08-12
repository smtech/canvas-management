<?php

define('DEBUGGING_NONE', 0);

define('DEBUGGING_GENERAL', 1);
define('DEBUGGING_LOG', 2);
define('DEBUGGING_CANVAS_API', 4);
define('DEBUGGING_MYSQL', 8);

define('DEBUGGING_ALL', DEBUGGING_GENERAL |
						DEBUGGING_LOG |
						DEBUGGING_CANVAS_API |
						DEBUGGING_MYSQL);


/**
 * Helper function to conditionally fill the log file with notes!
 **/
function debug_log($message) {
	if (DEBUGGING & DEBUGGING_LOG) {
		error_log($message);
	}
}

/**
 * Render a console-friendly version of the toolname
 **/
function getToolNameForConsole() {
	return str_replace(
		array(
			'&larr;',
			'&rarr;',
			'&harr;'
		),
		array(
			'<--',
			'-->',
			'<-->'
		),
		TOOL_NAME);
}

/**
 * Log script start
 **/
$DEBUG_FLAG_TAG = null;
function debugFlag($message, $tag = null) {
	if (!isset($GLOBALS['DEBUG_FLAG_TAG'])) {
		$GLOBALS['DEBUG_FLAG_TAG'] = time();
		if (isset($tag)) {
			if (function_exists($tag)) $GLOBALS['DEBUG_TAG_FLAG'] = $tag();
			else $GLOBALS['DEBUG_TAG_FLAG'] = $tag;
		}
	}
	debug_log(getToolNameForConsole(). ' ' . $GLOBALS['DEBUG_TAG_FLAG'] . ' ' . $message);
}


?>