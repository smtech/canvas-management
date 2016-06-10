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
			$params = [];
			if (!empty($_REQUEST['role'])) {
				$params['role'] = [$_REQUEST['role']];
			}
			if (!empty($_REQUEST['state'])) {
				$params['state'] = [$_REQUEST['state']];
			}
			
			$enrollments = $api->get(
				"courses/{$_REQUEST['course']}/enrollments",
				$params
				
			);
			$users = [];
			foreach ($enrollments as $enrollment) {
				$response = $api->delete(
					"courses/{$_REQUEST['course']}/enrollments/{$enrollment['id']}",
					[
						'task' => 'delete'
					]
				);
				$users[] = "<a target=\"_top\" href=\"{$_SESSION['canvasInstanceUrl']}/accounts/1/users/{$enrollment['user']['id']}\">{$enrollment['user']['name']}</a>";
			}
			$course = $api->get("accounts/1/courses/{$_REQUEST['course']}");
			$smarty->assign('course', $course['sis_course_id']);
			$smarty->addMessage(
				$enrollments->count() . ' enrollments deleted',
				(empty($_REQUEST['role']) ? '' : "{$_REQUEST['role']}s ") . implode(', ', $users) . " deleted from <a target=\"_top\" href=\"{$_SESSION['canvasInstanceUrl']}/courses/{$course['id']}/users\">{$course['name']}</a>.",
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
				$smarty->assign('roles', $roles);
				$smarty->assign('states', ['active', 'invited', 'creation_pending', 'deleted', 'rejected', 'completed', 'inactive']);
				$smarty->assign('courses', $courses);
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