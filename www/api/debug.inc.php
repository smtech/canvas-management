<?php

if (!defined('DEBUGGING')) {
	define('DEBUGGING', false);
	error_log('Using default DEBUGGING = ' . (DEBUGGING ? 'True' : 'False'));
}

/**
 * Helper function to conditionally fill the log file with notes!
 **/
function debug_log($message) {
	if (DEBUGGING) {
		error_log($message);
	}
}

?>