<?php

require_once('../common.inc.php');

$customPrefs = new mysqli(
	(string) $secrets->mysql->customprefs->host,
	(string) $secrets->mysql->customprefs->username,
	(string) $secrets->mysql->customprefs->password,
	(string) $secrets->mysql->customprefs->database
);

$cache = new Battis\HiearchicalSimpleCache($sql, basename(__FILE__));
$cache->setLifetime(60*60);

if (!isset($_REQUEST['users'])) {
	
	$ROLES = array(
		'faculty',
		'staff',
		'student',
		'no-menu'
	);
	
	$assignedUsers = array();
	$unassignedUsers = array();
	
	$users = $cache->getCache('users');
	if ($users === false) {
		$users = $canvasManagement->api->get('/accounts/1/users');
		$cache->setCache('users', $users);
	}
	
	foreach ($users as $user) {
		if (!empty($user['sis_user_id'])) {
			if ($response = $customPrefs->query("
				SELECT *
					FROM `users`
					WHERE
						`id` = '{$user['id']}'
			")) {
				if ($row = $response->fetch_assoc()) {
					$assignedUsers[$user['id']]['custom-prefs'] = $row;
					$assignedUsers[$user['id']]['user'] = $user;
				} else {
					$unassignedUsers[$user['id']]['user'] = $user;
				}
			}
		}
	}
	
	$smarty->assign('formAction', $_SERVER['PHP_SELF']);
	$smarty->assign('instance', $metadata['CANVAS_INSTANCE_URL']);
	$smarty->assign('roles', $ROLES);
	$smarty->assign('assignedUsers', $assignedUsers);
	$smarty->assign('unassignedUsers', $unassignedUsers);
	$smarty->display('custom-prefs-user-roles.tpl');
} else {
	$content = '';
	foreach ($_REQUEST['users'] as $id => $role) {
		if (empty($id) || empty($role)) {
			$content .= "<p>Empty ID ($id) or role ($role)</p>";
		}
		else {
			if ($customPrefs->query("
				INSERT
					INTO `users`
					(`id`, `role`) VALUES ('$id', '$role')
			")) {
				$content .= "<p>User $id is now $role</p>";
			} else {
				$content .= "<p>User $id was <em>not</em> updated to $role. " . $customPrefs->error . '</p>';
			}
		}
	}
	$smarty->assign('content', $content);
	$smarty->display();
}
	
?>