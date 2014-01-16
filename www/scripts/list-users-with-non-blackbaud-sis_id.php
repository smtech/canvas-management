<html>
<body>
<pre>
<?php

define ('TOOL_NAME', 'List Users with Non-Blackbaud Import ID SIS ID&rsquo;s');

require_once(__DIR__ . '/../config.inc.php');
require_once(APP_PATH . '/.ignore.read-only-authentication.inc.php');
require_once(APP_PATH . '/include/debug.inc.php');
require_once(APP_PATH . '/include/canvas-api.inc.php');

debugFlag('START');

$users = callCanvasApiPaginated(
	CANVAS_API_GET,
	'/accounts/1/users'
);
$page = 1;

echo TOOL_NAME . PHP_EOL;
echo "name\tlogin_id\tid\tsis_user_id" .PHP_EOL;

do {
	$pageProgress = 'processing page ' . getCanvasApiCurrentPageNumber() . ' of ' . getCanvasApiLastPageNumber() . '...';
	debugFlag($pageProgress);
	
	foreach ($users as $user) {
		if (isset($user['sis_user_id']) && !preg_match('%^(WGLSQLA)?[0-9\-]+$%', $user['sis_user_id'])) {
			echo "{$user['name']}\t{$user['login_id']}\t{$user['id']}\t{$user['sis_user_id']}" . PHP_EOL;
		}
	}
	flush();
} while ($users = callCanvasApiNextPage());

debugFlag('FINISH');

?>
</pre>
</body>
</html>