<?php

define('TOOL_NAME', 'Canvas Calendar &harr; ICS Tool');
define('TOOL_START_PAGE', dirname($_SERVER['PHP_SELF'])); // FIXME: should be index

define('WORKING_DIR', '/var/www-data/canvas/calendar-ics/');
define('API_CLIENT_ERROR_RETRIES', 2); // how many times to retry requests for which we got client errors that we don't entirely believe

?>