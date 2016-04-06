<?php
	
require_once 'common.inc.php';

use smtech\StMarksSmarty\StMarksSmarty;
use Battis\BootstrapSmarty\NotificationMessage;

$smarty->enable(StMarksSmarty::MODULE_COLORPICKER);

define("STEP_INSTRUCTIONS", 1);
define("STEP_CONFIRM", 2);
define("STEP_SET_COLOR", 3);

$step = (empty($_REQUEST['step']) ? STEP_INSTRUCTIONS : $_REQUEST['step']);

switch ($step) {
	case STEP_SET_COLOR:
		if (empty($_REQUEST['course'])) {
			$smarty->addMessage(
				'Course required',
				'Hard to set the color for an unspecified course.',
				NotificationMessage::ERROR
			);
		} else {
			if (empty($_REQUEST['color'])) {
				$smarty->addMessage(
					'Color required',
					'Hard to set a color without the color',
					NotificationMessage::ERROR
				);
			} else {
				$color = preg_replace('/#/', '', $_REQUEST['color']);
				try {
					$enrollments = $api->get("courses/{$_REQUEST['course']}/enrollments");
					foreach ($enrollments as $enrollment) {
						$api->put(
							"users/{$enrollment['user']['id']}/colors/course_{$_REQUEST['course']}",
							array(
								'hexcode' => $color
							)
						);
					}
					$smarty->addMessage(
						'Color updated',
						"Updated the course color to <span style=\"color: #$color; background: white; border-radius: .25em; padding: .1em;\">#$color &#9724;</span> for <a target=\"_top\" href=\"" . $_SESSION['canvasInstanceUrl'] . '/courses/' . $_REQUEST['course'] . '/users">' . $enrollments->count() . ' users</a>.'
					);
				} catch (Exception $e) {
					exceptionErrorMessage($e);
				}
			}
		}
		
		/* TODO should really objectify rather than using gotos, huh? */
		/* flow into STEP_CONFIRM (and thence STEP_INSTRUCTIONS) */

	case STEP_CONFIRM:
	
		if ($step == STEP_CONFIRM) {
			if (empty($_REQUEST['course'])) {
				$smarty->addMessage(
					'Course required',
					'Hard to set the color for an unspecified course.',
					NotificationMessage::ERROR
				);
			} else {
				try {
					$courses = $api->get(
						// FIXME don't hard code account numbers... yeesh
						'accounts/1/courses',
						array(
							'search_term' => $_REQUEST['course'],
							'include[]' => 'term'
						)
					);
					$smarty->assign('courses', $courses);
					$smarty->assign('formHidden', array('step' => STEP_SET_COLOR));
					$smarty->display(basename(__FILE__, '.php') . '/confirm.tpl');
					exit;
				} catch (Exception $e) {
					exceptionErrorMessage($e);
				}
			}
		}
		
		/* flow into STEP_INSTRUCTIONS */
	
	case STEP_INSTRUCTIONS:
	default:
		$smarty->assign('formHidden', array('step' => STEP_CONFIRM));
		$smarty->display(basename(__FILE__, '.php') . '/instructions.tpl');
}

?>