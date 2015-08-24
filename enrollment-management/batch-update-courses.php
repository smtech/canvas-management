<?php
	
require_once('common.inc.php');

define('STEP_INSTRUCTIONS', 1);
define('STEP_CONFIRM',2);
define('STEP_BATCH', 3);

$step = (empty($_REQUEST['step']) ? STEP_INSTRUCTIONS : $_REQUEST['step']);

switch ($step) {
	case STEP_CONFIRM:
		$courses = array();
		if(!empty($_FILES['csv']['tmp_name'])) {
			$csv = fopen($_FILES['csv']['tmp_name'], 'r');
			$fields = fgetcsv($csv);
			$smarty->assign('fields', $fields);
			while($row = fgetcsv($csv)) {
				$course = array();
				foreach ($fields as $i => $field) {
					if (isset($row[$i])) {
						$course[$field] = $row[$i];
					}
				}
				$courses[] = $course;
			}
		} else {
			$step = STEP_INSTRUCTIONS;
			$smarty->addMessage(
				'File upload',
				'is required for batch updating to work.',
				NotificationMessage::ERROR
			);
		}
		
		if (empty($courses)) {
			$step = STEP_INSTRUCTIONS;
			$smarty->addMessage(
				'Empty course list',
				'The uploaded CSV file contained no courses.',
				NotificationMessage::WARNING
			);
		}
		if ($step == STEP_CONFIRM) {
			$smarty->assign('courses', $courses);
			$smarty->assign('formHidden', array('step' => STEP_BATCH));
			$smarty->display(basename(__FILE__, '.php') . '/confirm.tpl');
			break;
		}
		
		/* flows into STEP_BATCH */
	
	case STEP_BATCH:
		if ($step == STEP_BATCH) {
			$links = "";
			foreach($_REQUEST['courses'] as $course) {
				if (isset($course['batch-include']) && $course['batch-include'] == 'include') {
					
					/* build parameter list */
					$params = array();
					if (!empty($course['account_id'])) {
						$params['account_id'] = "sis_account_id:{$course['account_id']}";
					}
					
					if (!empty($course['long_name'])) {
						$params['name'] = $course['long_name'];
					}
					
					if (!empty($course['short_name'])) {
						$params['course_code'] = $course['short_name'];
					} elseif (!empty($params['name'])) {
						$params['course_code'] = $params['name'];
					}
					
					if (!empty($course['term_id'])) {
						$params['term_id'] = "sis_term_id:{$course['term_id']}";
					}
					
					if (!empty($course['course_id'])) {
						$params['sis_course_id'] = $course['course_id'];
					}
					
					try {
						$response = $canvasManagement->api->put(
							"courses/sis_course_id%3A{$course['old_course_id']}",
							array(
								'course' => $params
							)
						);
						if (!empty($links)) {
							$links .= ", ";
						}
						$links .= "<a target=\"_parent\" href=\"{$metadata['CANVAS_INSTANCE_URL']}/courses/{$response['id']}/settings\">{$response['name']}</a>";
					} catch (Exception $e) {
						$smarty->addMessage(
							'Error',
							"Problem at <code>old_course_id</code> = {$course['old_course_id']}. [Error " . $e->getcode() . '] '. $e->getMessage(),
							NotificationMessage::ERROR
						);
					}
					
				}
			}
			
			$smarty->addMessage(
				'Update completed',
				'The following courses have been updated: ' . $links,
				NotificationMessage::GOOD
			);			
		} else {
			$smarty->addMessage(
				'Empty course list',
				'The uploaded CSV file contained no courses.',
				NotificationMessage::WARNING
			);
		}
		
		/* flows into STEP_INSTRUCTIONS */
	
	case STEP_INSTRUCTIONS:
	default:
		$smarty->assign('formHidden', array('step' => STEP_CONFIRM));
		$smarty->display(basename(__FILE__, '.php') . '/instructions.tpl');
}
	
?>