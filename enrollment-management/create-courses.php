<?php

require_once('common.inc.php');

$smarty->assign('name', 'Create Courses');

$MANUALLY_CREATED_COURSES_ACCOUNT = 96;
$DEFAULT_TERM = 195;
define('CACHE_LIFETIME', 7 * 24 * 60 * 60); // 1 week

/**
 * Generate a unique SIS ID
 *
 * @param string $name
 *
 * @return string
 **/
function generateSisId($name) {
	return strtolower(preg_replace('/[^a-z0-9\-]+/i', '-', $name) . '.' . md5(time()));
}

$cache = new Battis\HiearchicalSimpleCache($sql, basename(__DIR__) . '/' . basename(__FILE__, '.php'));

define('STEP_INSTRUCTIONS', 1);
define('STEP_RESULT', 2);

$step = (empty($_REQUEST['step']) ? STEP_INSTRUCTIONS : $_REQUEST['step']);

switch ($step) {
	case STEP_RESULT:
		if (!empty($_REQUEST['courses'])) {
			$courses = array();
			$lines = explode("\n", $_REQUEST['courses']);
			foreach ($lines as $line) {
				$items = explode(',', $line);
				foreach ($items as $item) {
					$courses[] = trim($item);
				}
			}
		}
		
		if (!empty($courses)) {
			if (empty($_REQUEST['account'])) {
				$smarty->addMessage(
					'Account',
					'was not selected, defaulting to the <a href="' . $metadata['CANVAS_INSTANCE_URL'] . '/accounts/' . $MANUALLY_CREATED_COURSES_ACCOUNT . '">Manually-Created Courses</a> account.',
					NotificationMessage::WARNING
				);
				$account = $MANUALLY_CREATED_COURSES_ACCOUNT;
			} else {
				$account = $_REQUEST['account'];
			}
			
			if (empty($_REQUEST['term'])) {
				$smarty->addMessage(
					'Term',
					'was not selected, defaulting to the Default Term.',
					NotificationMessage::WARNING
				);
				$term = $DEFAULT_TERM;
			} else {
				$term = $_REQUEST['term'];
			}
			
			$templated = false;
			if (empty($_REQUEST['template'])) {
				$smarty->addMessage(
					'Template',
					'was not entered, courses are in default configuration.'
				);
			} else {
				$templated = true;
				$template = (is_int($_REQUEST['template']) ? $_REQUEST['template'] : "sis_course_id:{$_REQUEST['template']}");
			
				/* pull course settings as completely as possible */		
				$source = $canvasManagement->api->get("courses/$template/settings");
				$source = array_merge(
					$source->getArrayCopy(),
					$canvasManagement->api->get("courses/$template")->getArrayCopy());

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
			
			foreach($courses as $name) {
				/* create course */
				$course = $canvasManagement->api->post(
					"accounts/$account/courses",
					array(
						'course[name]' => $name,
						'course[course_code]' => $name,
						'course[sis_course_id]' => generateSisId($name),
						'course[term_id]' => $term
					)
				);
				
				if ($templated) {
					/* duplicate course settings */
					$canvasManagement->api->put("/courses/{$course['id']}", $source);
					
					// TODO  nice to figure out navigation settings copy
					
					/* duplicate course content */
					$migration = $canvasManagement->api->post(
						"courses/{$course['id']}/content_migrations",
						array(
							'migration_type' => 'course_copy_importer',
							'settings[source_course_id]' => $template
						)
					);
					
					$smarty->addMessage(
						"<a href=\"{$metadata['CANVAS_INSTANCE_URL']}/courses/{$course['id']}\">{$course['name']}</a>",
						"has been created as a clone of <a href=\"{$metadata['CANVAS_INSTANCE_URL']}/courses/$sourceId\">$sourceName</a>. Course content is being <a href=\"{$metadata['CANVAS_INSTANCE_URL']}/courses/{$course['id']}/content_migrations\">migrated</a> right now.",
						NotificationMessage::GOOD
					);
				} else {
					$smarty->addMessage(
						"<a href=\"{$metadata['CANVAS_INSTANCE_URL']}/courses/{$course['id']}\">{$course['name']}</a>",
						"has been created.",
						NotificationMessage::GOOD
					);
				}
			}
		} else {
			$smarty->addMessage(
				'Courses',
				'No course names were entered',
				NotificationMessage::ERROR
			);
		}
		
		/* flow into STEP_INSTRUCTIONS */
	
	case STEP_INSTRUCTIONS:
	default:
		$accounts = $cache->getCache('accounts');
		if ($accounts === false) {
			$accounts = $canvasManagement->api->get('accounts/1/sub_accounts', array('recursive' => 'true'));
			$cache->setCache('accounts', $accounts, CACHE_LIFETIME);
		}
		$smarty->assign('accounts', $accounts);
		
		$terms = $cache->getCache('terms');
		if ($terms === false) {
			$_terms = $canvasManagement->api->get(
				'accounts/1/terms',
				array(
					'workflow_state' => 'active'
				)
			);
			$terms = $_terms['enrollment_terms'];
			$cache->setCache('terms', $terms, CACHE_LIFETIME);
		}
		$smarty->assign('terms', $terms);
		
		$smarty->assign('formHidden', array('step' => STEP_RESULT));
		$smarty->display(basename(__FILE__, '.php') . '/instructions.tpl');
}
	
?>