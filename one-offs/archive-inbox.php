<?php
	
require_once('common.inc.php');

$smarty->enable(StMarksSmarty::MODULE_DATEPICKER);

define('STEP_INSTRUCTIONS', 1);
define('STEP_SELECT', 2);
define('STEP_ARCHIVE', 3);

$step = (empty($_REQUEST['step']) ? STEP_INSTRUCTIONS : $_REQUEST['step']);
switch ($step) {
	case STEP_SELECT:
		try {
			$users = $api->get('accounts/1/users', array('search_term' => $_REQUEST['user']));
		} catch (Exception $e) {
			exceptionErrorMessage($e);
			$smarty->assign('user', $_REQUEST['user']);
			$step = STEP_INSTRUCTIONS;
		}
		
		if ($step == STEP_SELECT) {
			$smarty->assign('users', $users);
			$smarty->assign('formHidden', array('step' => STEP_ARCHIVE));
			$smarty->display(basename(__FILE__, '.php') . '/select.tpl');
			break;
		}
		
		/* flow into STEP_ARCHIVE */
		
	case STEP_ARCHIVE:
		if ($step == STEP_ARCHIVE) {
			try {
				$cutoff = strtotime((empty($_REQUEST['cutoff']) ? 'now' : $_REQUEST['cutoff']));
				$conversations = $api->get(
					'conversations',
					array(
						'as_user_id' => $_REQUEST['user']
					)
				);
				
				$archived = array();
				$unarchived = array();
				foreach($conversations as $conversation) {
					if (strtotime($conversation['last_message_at']) < $cutoff) {
						try {
							$archived[] = $conversation['id'];
							$api->put(
								"conversations/{$conversation['id']}",
								array(
									'conversation[workflow_state]' => 'archived',
									'as_user_id' => $_REQUEST['user']
								)
							);
						} catch (Exception $e) {
							exceptionErrorMessage($e);
						}
					} else {
						$unarchived[] = $conversation['id'];
					}
				}
			} catch (Exception $e) {
				exceptionErrorMessage($e);
			}
			
			$smarty->addMessage(
				'Inbox Archived',
				count($archived) . ' conversations archived (IDs ' . implode(', ', $archived) . ') and ' . count($unarchived) . ' conversations left unarchived (IDs ' . implode(', ', $unarchived) . ').',
				NotificationMessage::GOOD
			);
		}
		
		/* flow into STEP_INSTRUCTIONS */
	
	case STEP_INSTRUCTIONS:
	default:
		$smarty->assign('formHidden', array('step' => STEP_SELECT));
		$smarty->display(basename(__FILE__, '.php') . '/instructions.tpl');
}