<?php
	
require_once('common.inc.php');

define('STEP_INSTRUCTIONS', 1);
define('STEP_PUBLISH', 2);

$step = (empty($_REQUEST['step']) ? STEP_INSTRUCTIONS : $_REQUEST['step']);

switch($step) {
	case STEP_PUBLISH:
		$courses = $api->get(
			"accounts/{$_REQUEST['account']}/courses",
			array(
				'enrollment_term_id' => $_REQUEST['term'],
				'published' => 'false'
			)
		);
		
		$list = array();
		foreach ($courses as $course) {
			$api->put(
				"courses/{$course['id']}",
				array(
					'offer' => 'true'
				)
			);
			$list[] = "<a target=\"_parent\" href=\"{$_SESSION['canvasInstanceUrl']}/courses/{$course['id']}\">{$course['name']}</a>";
		}
		$smarty->addMessage($courses->count(). ' courses published', implode(', ', $list), NotificationMessage::GOOD);
	
	case STEP_INSTRUCTIONS:
	default:
		$smarty->assign('accounts', getAccountList());
		$smarty->assign('terms', getTermList());
		$smarty->assign('formHidden', array('step' => STEP_PUBLISH));
		$smarty->display(basename(__FILE__, '.php') . '/instructions.tpl');
}
?>