<?php

require_once('common.inc.php');

$smarty->assign('name', 'Assign User Roles');

$cache = new Battis\HiearchicalSimpleCache($sql, basename(__DIR__) . '/' . basename(__FILE__, '.php'));
$cache->setLifetime(60*60);

define('STEP_INSTRUCTIONS', 1);
define('STEP_LISTING', 2);
define('STEP_RESULT', 3);

$step = (empty($_REQUEST['step']) ? STEP_INSTRUCTIONS : $_REQUEST['step']);

switch ($step) {
	case STEP_LISTING:
	case STEP_RESULT:

		$users = $cache->getCache('users');
		if ($users === false) {
			$users = array();
			$response = $canvasManagement->api->get('/accounts/1/users');
			foreach ($response as $user) {
				$users[$user['id']] = $user;
			}
			$cache->setCache('users', $users);
		}

		$ROLES = array(
			'faculty',
			'staff',
			'student',
			'no-menu'
		);
		
		if ($step == STEP_RESULT) {
			foreach ($_REQUEST['users'] as $id => $data) {
				if ($data['dirty']) {
					if (empty($id) || empty($data['role'])) {
						$smarty->addMessage(
							'Error',
							"Empty ID ($id) or role ({$data['role']})",
							NotificationMessage::ERROR
						);
					}
					else {
						$updated = false;
						if ($response = $customPrefs->query("
							SELECT *
								FROM `users`
								WHERE
									`id` = '$id'
						")) {
							if ($response->num_rows > 0) {
								$updated = $customPrefs->query("
									UPDATE `users`
										SET `role` = '{$data['role']}'
										WHERE `id` = '$id'
								");
							} else {
								$updated = $customPrefs->query("
									INSERT
										INTO `users`
										(`id`, `role`) VALUES ('$id', '{$data['role']}')
								");
							}
						}
						if ($updated) {
							$smarty->addMessage(
								$users[$id]['name'],
								"is now {$data['role']}.",
								NotificationMessage::GOOD
							);
						} else {
							$smarty->addMessage(
								$users[$id]['name'],
								"was not updated to {$data['role']}. " . $customPrefs->error,
								NotificationMessage::WARNING
							);
						}
					}
				}
			}
		}
		
		$assignedUsers = array();
		$unassignedUsers = array();
		
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
		
		$smarty->assign('roles', $ROLES);
		$smarty->assign('assignedUsers', $assignedUsers);
		$smarty->assign('unassignedUsers', $unassignedUsers);
		$smarty->assign('formHidden', array('step' => STEP_RESULT));
		$smarty->display(basename(__FILE__, '.php') . '/listing.tpl');
		break;
	
	case STEP_INSTRUCTIONS:
	default:
		$smarty->assign('formHidden', array('step' => STEP_LISTING));
		$smarty->display(basename(__FILE__, '.php') . '/instructions.tpl');
		exit;
}
	
?>