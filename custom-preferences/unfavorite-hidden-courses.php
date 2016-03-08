<?php
	
require_once "common.inc.php";

define('STEP_INSTRUCTIONS', 1);
define('STEP_UNFAVORITE', 2);

$step = (empty($_REQUEST['step']) ? STEP_INSTRUCTIONS : $_REQUEST['step']);

switch ($step) {
	case STEP_UNFAVORITE:
	
		if (empty($_REQUEST['account'])) {
			$smarty->addMessage(
				'No account selected',
				'You must select an account whose users will be affected by this unfavoriting action.',
				NotificationMessage::WARNING
			);
		} else {
			
			/* get the list of hidden courses from custom preferences */
			$response = $customPrefs->query(
				"SELECT *
					FROM `courses`
					WHERE
						`menu-visible` = '0'"
			);
			$hiddenCourses = array();
			while ($row = $response->fetch_assoc()) {
				$hiddenCourses[] = $row['id'];
			}
			
			/* walk through all users in the account */
			try {
				$users = $api->get("accounts/{$_REQUEST['account']}/users");
				foreach ($users as $user) {
					$favorites = $api->get(
						'users/self/favorites/courses',
						array(
							'as_user_id' => $user['id']
						)
					);
					
					/* walk through favorites and set non-hidden and unset hidden courses */
					foreach ($favorites as $favorite) {
						if (in_array($favorite['id'], $hiddenCourses)) {
							$api->delete(
								"users/self/favorites/courses/{$favorite['id']}",
								array(
									'as_user_id' => $user['id']
								)
							);
						} else {
							/* need to explicitly set favorites because if someone has no favorites, they implicitly favorite all active courses. */
							$api->post(
								"users/self/favorites/courses/{$favorite['id']}",
								array(
									'as_user_id' => $user['id']
								)
							);
						}
					}
				}
				
				$smarty->addMessage(
					'Hidden courses unfavorited',
					$users->count() . ' users were affected by this action.'
				);
 			} catch (Exception $e) {
				exceptionErrorMessage($e);
			}
		}
	
		/* flows into STEP_INSTRUCTIONS */
		
	case STEP_INSTRUCTIONS:
	
		if (!empty($_REQUEST['account'])) {
			$smarty->assign('account', $_REQUEST['account']);
		}
		$smarty->assign('accounts', getAccountList());
		$smarty->assign('formHidden', array('step' => STEP_UNFAVORITE));
		$smarty->display(basename(__FILE__, '.php') . '/instructions.tpl');
}