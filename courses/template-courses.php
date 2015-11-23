<?php

require_once('common.inc.php');

use Battis\DataUtilities;

define('STEP_INSTRUCTIONS', 1);
define('STEP_CONFIRM', 2);
define('STEP_TEMPLATE', 3);

$step = (empty($_REQUEST['step']) ? STEP_INSTRUCTIONS : $_REQUEST['step']);

switch ($step) {
	case STEP_CONFIRM:
	case STEP_TEMPLATE:
		try {
			$source = array();
			$template = false;
			if (empty($_REQUEST['template'])) {
				$smarty->addMessage(
					'Template',
					'was not entered, courses are in default configuration.'
				);
				$step = STEP_INSTRUCTIONS;
			} else {
				$templated = true;
				$template = (is_int($_REQUEST['template']) ? $_REQUEST['template'] : "sis_course_id:{$_REQUEST['template']}");
			
				/* pull course settings as completely as possible */		
				$source = $api->get("courses/$template/settings");
				$source = array_merge(
					$source->getArrayCopy(),
					$api->get("courses/$template")->getArrayCopy());
	
				/* save ID and name to create a nice link later */
				$sourceId = $source['id'];
				$sourceName = $source['name'];
	
				/* clear settings that are provided form entry */
				unset($source['id']);
				unset($source['sis_course_id']);
				unset($source['integration_id']);
				unset($source['name']);
				unset($source['course_code']);
				unset($source['account_id']);
				unset($source['enrollment_term_id']);
				unset($source['start_at']);
				unset($source['end_at']);
				unset($source['enrollments']);
				
				/* why nest this, I mean... really? */
				$source = array('course' => $source);
			}
			
			$courses = DataUtilities::loadCsvToArray('csv');
	
			if ($step == STEP_CONFIRM) {
				if (empty($courses)) {
					$smarty->addMessage(
						'Courses',
						'No courses found in uploaded list.',
						NotificationMessage::ERROR
					);
					$step = STEP_INSTRUCTIONS;
				} else {
					foreach ($courses as $course) {
						/* duplicate course settings */
						$course = $api->put("/courses/sis_course_id:{$course['course_id']}", $source);
						
						// TODO  nice to figure out navigation settings copy
						
						/* duplicate course content */
						$migration = $api->post(
							"courses/{$course['id']}/content_migrations",
							array(
								'migration_type' => 'course_copy_importer',
								'settings[source_course_id]' => $template
							)
						);
						
						$smarty->addMessage(
							"<a target=\"_parent\" href=\"{$_SESSION['canvasInstanceUrl']}/courses/{$course['id']}\">{$course['long_name']}</a>",
							"has been templated as a clone of <a target=\"_parent\" href=\"{$_SESSION['canvasInstanceUrl']}/courses/$sourceId\">$sourceName</a>. Course content is being <a target=\"_parent\" href=\"{$_SESSION['canvasInstanceUrl']}/courses/{$course['id']}/content_migrations\">migrated</a> right now.",
							NotificationMessage::GOOD
						);
					}
				}
			}
		} catch (Exception $e) {
			exceptionErrorMessage($e);
		}
		
		/* flows into STEP_INSTRUCTIONS */
	
	case STEP_INSTRUCTIONS:
	default:
		$smarty->assign('formHidden', array('step' => STEP_CONFIRM));
		$smarty->display(basename(__FILE__, '.php') . '/instructions.tpl');
}
	
?>