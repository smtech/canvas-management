<?php

require_once('common.inc.php');

use Battis\BootstrapSmarty\NotificationMessage;
use smtech\StMarksSmarty\StMarksSmarty;

define('STEP_INSTRUCTIONS', 1);
define('STEP_APPLY', 2);

$step = (empty($_REQUEST['step']) ? STEP_INSTRUCTIONS : $_REQUEST['step']);

switch ($step) {
	case STEP_APPLY:
		if (empty($_REQUEST['account'])) {
			$smarty->addMessage(
				'Account required',
				'You must choose an account or sub-account in which to apply course-level grading periods.',
				NotificationMessage::ERROR
			);
			$step = STEP_INSTRUCTIONS;
		}

		if (empty($_REQUEST['term'])) {
			$smarty->addMessage(
				'Term required',
				'You must choose a term in which to apply course-level grading periods.',
				NotificationMessage::ERROR
			);
			$step = STEP_INSTRUCTIONS;
		}

		if (empty($_REQUEST['period']['title'])) {
			$smarty->addMessage(
				'Grading period title required',
				'You must give the grading period a title.',
				NotificationMessage::ERROR
			);
			$step = STEP_INSTRUCTIONS;
		}

		if (empty($_REQUEST['period']['start_date'])) {
			$smarty->addMessage(
				'Start date required',
				'You must set a start date for the grading period.',
				NotificationMessage::ERROR
			);
			$step = STEP_INSTRUCTIONS;
		}

		if (empty($_REQUEST['period']['end_date'])) {
			$smarty->addMessage(
				'End date required',
				'You must set an end date for the grading period.',
				NotificationMessage::ERROR
			);
			$step = STEP_INSTRUCTIONS;
		}

		if ($step == STEP_APPLY) {
			$start = new DateTime($_REQUEST['period']['start_date']);
			$end = new DateTime($_REQUEST['period']['end_date']);

			$applied = array();
			$failed = array();
			try {
				$courses = $api->get(
					"accounts/{$_REQUEST['account']}/courses",
					array(
						'enrollment_term_id' => $_REQUEST['term']
					)
				);
			} catch (Exception $e) {
				$smarty->addMessage(
					'Courses',
					'There was an error getting the course list to update.',
					NotificationMessage::ERROR
				);
				$step = STEP_INSTRUCTIONS;
			}

			if ($step == STEP_APPLY) {
				foreach ($courses as $course) {
					try {
						$result = $api->post(
							"courses/{$course['id']}/grading_periods",
							array(
								'grading_periods[]' => array(
									'title' => $_REQUEST['period']['title'],
									'start_date' => $start->format(DATE_W3C),
									'end_date' => $end->format(DATE_W3C)
								)
							)
						);
						$applied[] = "<a target=\"_parent\" href=\"{$_SESSION['canvasInstanceUrl']}/courses/{$course['id']}/grading_standards\">{$course['name']}</a>";
					} catch (Exception $e) {
						$failed[] = "<a target=\"_parent\" href=\"{$_SESSION['canvasInstanceUrl']}/courses/{$course['id']}/grading_standards\">{$course['name']}</a>";

					}
				}

				if (count($applied) > 0) {
					$smarty->addMessage(
						'Applied grading period to ' . count($applied) . ' courses',
						implode(', ', $applied),
						NotificationMessage::GOOD
					);
				}

				if (count($failed) > 0) {
					$smarty->addMessage(
						'Could not apply grading period to ' . count($failed) . ' courses',
						implode(', ', $failed),
						NotificationMessage::WARNING
					);
				}
			}
		}

		/* flows into STEP_INSTRUCTIONS */

	case STEP_INSTRUCTIONS:
	default:
		if (!empty($_REQUEST['period'])) {
			$smarty->assign('period', $_REQUEST['period']);
		}
		$smarty->enable(StMarksSmarty::MODULE_DATEPICKER);
		$smarty->assign('accounts', getAccountList());
		$smarty->assign('terms', getTermList());
		$smarty->assign('formHidden', array('step' => STEP_APPLY));
		$smarty->display(basename(__FILE__, '.php') . '/instructions.tpl');
}

?>
