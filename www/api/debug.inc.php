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

?>