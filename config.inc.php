<?php

require_once(__DIR__ . '/smcanvaslib/config.inc.php');
if (!defined('CANVAS_API_URL')) {
	require_once('.ignore.stmarksschool-test-authentication.inc.php');
}
require_once(SMCANVASLIB_PATH . '/include/debug.inc.php');
require_once(SMCANVASLIB_PATH . '/include/canvas-api.inc.php');

define('START_PAGE', APP_URL . '/scripts/index.php');

?>