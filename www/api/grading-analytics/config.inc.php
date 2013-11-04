<?php

if (!defined('TOOL_NAME')) {
	define('TOOL_NAME', 'Grading Analytics'); // what the tool calls itself
}

if (!defined('TOOL_START_PAGE')) {
	define('TOOL_START_PAGE', 'index.php');
}

define('DATA_COLLECTION_CRONTAB', '0 0 * * *'); // collect data every night at midnight

?>