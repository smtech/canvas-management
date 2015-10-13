<?php

require_once('common.inc.php');

function blank($row, $key) {
	if (empty($row[$key])) {
		return '';
	} else {
		return $row[$key];
	}
}

$cache = new \Battis\HierarchicalSimpleCache($sql, basename(__FILE__, '.php'));

define('STEP_INSTRUCTIONS', 1);
define('STEP_CSV', 2);

$step = (empty($_REQUEST['step']) ? STEP_INSTRUCTIONS : $_REQUEST['step']);

switch ($step) {
	
	case STEP_CSV:
		try {
			$account = (empty($_REQUEST['account']) ? 1 : $_REQUEST['account']);
			if (empty($_REQUEST['account'])) {
				$smarty->addMessage(
					'No Account',
					'No account specified, all users included in CSV file.',
					NotificationMessage::WARNING
				);
			}
			
			$data = $cache->getCache("$account/users");
			if ($data === false) {
				$users = $api->get("accounts/$account/users");
				$data[] = array(
					'id', 'user_id', 'login_id', 'full_name', 'sortable_name', 'short_name',
					'email', 'status'
				);
				foreach ($users as $user) {
					$data[] = array(
						blank($user, 'id'), blank($user, 'sis_user_id'), blank($user, 'login_id'), blank($user, 'name'),
						blank($user, 'sortable_name'), blank($user, 'short_name'), blank($user, 'email'), 'active'
					);
				}			
				$cache->setCache("$account/users", $data, 15 * 60);
			}
			$smarty->assign('csv', basename(__FILE__, '.php') . "/$account/users");
			$smarty->assign('filename', date('Y-m-d_H-i-s') . "_account-{$account}_users");
			$smarty->addMessage(
				'Ready for Download',
				'<code>users.csv</code> is ready and download should start automatically in a few seconds. Click the link below if the download does not start automatically.',
				NotificationMessage::GOOD
			);
		} catch (Exception $e) {
			exceptionErrorMessage($e);
		}
		
		/* flows into STEP_INSTRUCTIONS */
	
	case STEP_INSTRUCTIONS:
	default:
		$smarty->assign('accounts', getAccountList());
		$smarty->assign('formHidden', array('step' => STEP_CSV));
		$smarty->display(basename(__FILE__, '.php') . '/instructions.tpl');
}
	
?>