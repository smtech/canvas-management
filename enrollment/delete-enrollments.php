<?php
	
require_once 'common.inc.php';

use Battis\BootstrapSmarty\NotificationMessage;

define('STEP_INSTRUCTIONS', 1);
define('STEP_CONFIRM', 2);
define('STEP_DELETE', 3);

$step = (empty($_REQUEST['step']) ? STEP_INSTRUCTIONS : $_REQUEST['step']);

switch ($step) {
	case STEP_DELETE:
		try {
			$enrollments = $api->get(
				"courses/{$_REQUEST['course']}/enrollments",
				['role' => [$_REQUEST['role']]]
			);
			foreach ($enrollments as $enrollment) {
				$api->delete(
					"courses/{$_REQUEST['course']}/enrollments/{$enrollment['id']}",
					['task' => 'delete']
				);
			}
			$smarty->addMessage(
				$enrollments->count() . ' enrollments deleted',
				'Check <a target="_top" href="' . $_SESSION['canvasInstanceUrl'] . '/courses/' . $_REQUEST['course'] . '/users">course roster</a>.',
				NotificationMessage::SUCCESS
			);
		} catch (Exception $e) {
			exceptionErrorMessage($e);
		}
		
		/* flow into STEP_CONFIRM */
	
	case STEP_CONFIRM:
		if ($step == STEP_CONFIRM) {
			try {
				$courses = $api->get(
					'accounts/1/courses',
					[
						'search_term' => $_REQUEST['course'],
						'hide_enrollmentless_courses' => true
					]
				);
				$roles = $api->get('/accounts/1/roles');
				$smarty->assign('courses', $courses);
				$smarty->assign('roles', $roles);
				$smarty->assign('formHidden', ['step' => STEP_DELETE]);
				$smarty->display(basename(__FILE__, '.php') . '/confirm.tpl');
				break;
			} catch (Exception $e) {
				exceptionErrorMessage($e);
			}
		}
		
		/* flow into STEP_INSTRUCTIONS */
	
	case STEP_INSTRUCTIONS:
		$smarty->assign('formHidden', ['step' => STEP_CONFIRM]);
		$smarty->display(basename(__FILE__, '.php') . '/instructions.tpl');
}