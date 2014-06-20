<?php

require_once(__DIR__ . '/../config.inc.php');
if (!defined(CANVAS_API_URL)) {
	require_once(APP_PATH . '/.ignore.read-only-authentication.inc.php');
}
require_once(APP_PATH . '/include/debug.inc.php');
require_once(APP_PATH . '/include/canvas-api.inc.php');

define('START_PAGE', APP_URL . '/scripts/index.php');
define('DEBUGGING', DEBUGGING_LOG);

?>