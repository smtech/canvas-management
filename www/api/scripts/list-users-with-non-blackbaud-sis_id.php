<html>
<body>
<pre>
<?php

define ('TOOL_NAME', 'List Users with Non-Blackbaud Import ID SIS ID&rsquo;s');

require_once('../.ignore.read-only-authentication.inc.php');
require_once('../debug.inc.php');
require_once('../canvas-api.inc.php');

debugFlag('START');

$users = callCanvasApiPaginated(
	CANVAS_API_GET,
	'/accounts/1/users'
);
$page = 1;

do {
	$pageProgress = 'processing page ' . getCanvasApiCurrentPageNumber() . ' of ' . getCanvasApiLastPageNumber() . '...';
	echo $pageProgress . PHP_EOL;
	debugFlag($pageProgress);
	
	foreach ($users as $user) {
		if (isset($user['sis_user_id']) && !preg_match('%^(WGLSQLA)?[0-9\-]+$%', $user['sis_user_id'])) {
			echo "    SIS ID '{$user['sis_user_id']}' is not a Blackbaud Import ID for {$user['name']} ({$user['login_id']} / ID={$user['id']})" . PHP_EOL;
		}
	}
	flush();
} while ($users = callCanvasApiNextPage());

debugFlag('FINISH');

?>
</pre>
</body>
</html>