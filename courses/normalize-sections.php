<?php
	
require_once('common.inc.php');

use Battis\DataUtilities;
use Battis\BootstrapSmarty\NotificationMessage;

define('STEP_INSTRUCTIONS', 1);
define('STEP_NORMALIZE', 2);

$step = (empty($_REQUEST['step']) ? STEP_INSTRUCTIONS : $_REQUEST['step']);

switch ($step) {
	case STEP_NORMALIZE:
	
		$sections = DataUtilities::loadCsvToArray('csv');
		$account = (empty($_REQUEST['account']) ? false : $_REQUEST['account']);
		$term = (empty($_REQUEST['term']) ? false : $_REQUEST['term']);
		
		if ($sections || $account || $term) {
			if ($sections) {
				$links = "";
				foreach ($sections as $section) {
					try {
						$course = $api->get("/courses/sis_course_id:{$section['course_id']}");
						$courseSections = $api->get("/courses/sis_course_id:{$section['course_id']}/sections");
						
						/* do we have a singleton to rename? */
						if ($courseSections->count() <= 1) {
							
							$params = array();
							
							$_section = false;
							if ($courseSections->count() == 1) {
								$_section = $courseSections[0];
							}
							
							if ($_section && $section['section_id'] != $_section['sis_section_id']) {
								$params['sis_section_id'] = $section['section_id'];
							}
							
							if ($_section && $course['name'] != $_section['name']) {
								$params['name'] = $course['name'];
							}
							
							if ($_section) {
								$response = $api->put(
									"sections/{$_section['id']}",
									array(
										'course_section' => $params
									)
								);
							} else {
								$response = $api->post(
									"courses/{$course['id']}/sections",
									array(
										'course_section[name]' => $course['name'],
										'course_section[sis_section_id]' => $section['section_id']
									)
								);
							}
							
							if (!empty($links)) {
								$links .= ', ';
							}
							$links .= "<a target=\"_parent\" href=\"{$_SESSION['canvasInstanceUrl']}/courses/{$course['id']}/sections/{$response['id']}\">{$response['name']}</a>";
							
						/* too many sections to (easily) normalize */
						} else {
							$smarty->addMessage(
								'Multiple Sections',
								"<a target=\"_parent\" href=\"{$_SESSION['canvasInstanceUrl']}/courses/{$course['id']}/settings\">{$course['name']}</a> has more than one section, which means that standard singleton-section normalization does not apply.",
								NotificationMessage::WARNING
							);
						}
					} catch (Exception $e) {
						exceptionErrorMessage($e);
					}
				}
				$smarty->addMessage(
					'Sections Normalized',
					'The following singleton course sections have been normalized to match their parent course title: ' . $links,
					NotificationMessage::GOOD
				);
			} else {
				$smarty->addMessage(
					'Missing Constraint',
					'You must either upload a list of courses and sections, or select an account and/or term within which to normalize sections.',
					NotificationMessage::ERROR
				);
			}
		}
		
		/* flow into STEP_INSTRUCTIONS */
	
	case STEP_INSTRUCTIONS:
	default:
		$smarty->assign('accounts', getAccountList());
		$smarty->assign('terms', getTermList());
		
		$smarty->assign('formHidden', array('step' => STEP_NORMALIZE));
		$smarty->display(basename(__FILE__, '.php') . '/instructions.tpl');
}

?>