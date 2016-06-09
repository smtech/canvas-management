<?php
	
require_once 'common.inc.php';

use Battis\BootstrapSmarty\NotificationMessage;

define('STEP_INSTRUCTIONS', 1);
define('STEP_RESET', 2);

$step = (empty($_REQUEST['step']) ? STEP_INSTRUCTIONS : $_REQUEST['step']);

switch ($step) {
	case STEP_RESET:
		try {
			$accounts = getAccountList();
			$users = $api->get("accounts/{$_REQUEST['account']}/users");
			foreach ($users as $user) {
				$api->delete(
					'users/self/favorites/courses',
					[
						'as_user_id' => $user['id']
					]
				);
			}
			$smarty->addMessage(
				'Favorites Reset',
				'Favorite courses were reset to the default active courses for ' . $users->count() . ' users in ' . $accounts[$_REQUEST['account']]['name'] . '.',
				NotificationMessage::SUCCESS
			);
		} catch (Exception $e) {
			exceptionErrorMessage($e);
		}
		
		$smarty->assign('account', $_REQUEST['account']);
		
		/* flow into STEP_INSTRUCTIONS */
	
	case STEP_INSTRUCTIONS:
	default:
		$smarty->assign('accounts', getAccountList());
		$smarty->assign('formHidden', ['step' => STEP_RESET]);
		$smarty->display(basename(__FILE__, '.php') . '/instructions.tpl');
}