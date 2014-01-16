<?php

require_once('.ignore.grading-analytics-authentication.inc.php');
define('TOOL_START_PAGE', 'https://' . parse_url(CANVAS_API_URL, PHP_URL_HOST) . "/accounts/{$_REQUEST['department_id']}");

require_once('config.inc.php');
require_once('common.inc.php');
require_once('../canvas-api.inc.php');
require_once('../mysql.inc.php');
require_once('../page-generator.inc.php');

$departments = callCanvasApi(
	CANVAS_API_GET,
	"/accounts/{$_REQUEST['department_id']}"
);

displayPage("<h1>{$departments['name']} Summary Page</h1><p>Patience&hellip;</p>")

?>