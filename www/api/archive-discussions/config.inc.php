<?php

define('TOOL_NAME', 'Archive Discussions'); // what the tool calls itself
define('TOOL_START_PAGE', $_SERVER['PHP_SELF']); // the start page (and where Start Over links back to

define('NAME_SEPARATOR' , ' - ');
define('TIMESTAMP_FORMAT', 'Y-m-d H:i:s ');
define('INCLUDE_ORGANIZATIONAL_UNIT_ID', true);
define('FILE_NAME', 'Discussions');

define('WORKING_DIR', '/var/www-data/canvas/archive-discussions'); // where we'll be dumping temp files (and cleaning up, too!)

define('API_CLIENT_ERROR_RETRIES', 2); // how many times to retry requests for which we got client errors that we don't entirely believe

?>