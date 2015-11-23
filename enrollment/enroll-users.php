<?php

require_once('common.inc.php');

$MANUALLY_CREATED_COURSES_ACCOUNT = 96;
$DEFAULT_TERM = 195;
define('CACHE_LIFETIME', 20 * 60); // 20 minutes
define('LONG_CACHE_LIFETIME', 7 * 24 * 60 * 60); // 1 week

$cache = new Battis\HierarchicalSimpleCache($sql, basename(__DIR__) . '/' . basename(__FILE__, '.php'));

define('STEP_INSTRUCTIONS', 1);
define('STEP_CONFIRM', 2);
define('STEP_ENROLL', 3);

$step = (empty($_REQUEST['step']) ? STEP_INSTRUCTIONS : $_REQUEST['step']);

$smarty->assign('role', (empty($_REQUEST['role']) ? 218 /* Student */ : $_REQUEST['role']));

$roles = $cache->getCache('roles');
if ($roles === false) {
	$roles = $api->get('accounts/1/roles'); // TODO handle specific accounts
	$cache->setCache('roles', $roles);
}

switch ($step) {
	case STEP_CONFIRM:
		
		$users = explodeCommaAndNewlines($_REQUEST['users']);
				
		if (empty($_REQUEST['course'])) {
			$smarty->addMessage(
				'Course',
				'was not selected, so no enrollments can happen',
				NotificationMessage::ERROR
			);
			$step = STEP_INSTRUCTIONS;
		} else {
			$sections = $cache->getCache("courses/{$_REQUEST['course']}");
			if (empty($sections)) {
				$section = array();
				$courses = $api->get(
					'accounts/1/courses',
					array(
						'search_term' => $_REQUEST['course']
					)
				);
				foreach($courses as $course) {
					$courseSections = $api->get("courses/{$course['id']}/sections");
					if ($courseSections->count() == 0) {
						/* we have only the "magic" default section */
						$sections[] = array('course' => $course);
					} else {
						foreach ($courseSections as $section) {
							$sections[] = array(
								'course' => $course,
								'section' => $section
							);
						}
					}
				}
				$cache->setCache("courses/{$_REQUEST['course']}", $sections, CACHE_LIFETIME);
			}
			
			if (empty($sections)) {
				$smarty->addMessage(
					'No Courses',
					"matched your search term '{$_REQUEST['course']}'.",
					NotificationMessage::WARNING
				);
				$step = STEP_INSTRUCTIONS;
			}
		}
		
		if ($step == STEP_CONFIRM) {
			if (!empty($users)) {
				$confirm = array();
				foreach($users as $term) {
					$confirm[$term] = $cache->getCache("users/$term");
					if ($confirm[$term] === false) {
						$confirm[$term] = $api->get(
							'accounts/1/users',
							array(
								'search_term' => $term,
								'include' => array('term')
							)
						);
						$cache->setCache("users/$term", $confirm[$term], CACHE_LIFETIME);
					}
				}	
				
				$smarty->assign('sections', $sections);
				$smarty->assign('terms', getTermList());
				$smarty->assign('confirm', $confirm);
				$smarty->assign('roles', $api->get('accounts/1/roles')); // TODO make this account-specific
				$smarty->assign('formHidden', array('step' => STEP_ENROLL));
				$smarty->display(basename(__FILE__, '.php') . '/confirm.tpl');
				break;		
			} else {
				$smarty->addMessage(
					'Users',
					'were not selected, so no enrollments can happen.',
					NotificationMessage::ERROR
				);
				$step = STEP_INSTRUCTIONS;
			}
		}
		
		/* flow into STEP_ENROLL (and STEP_INSTRUCTIONS) */
	
	case STEP_ENROLL:
		if ($step == STEP_ENROLL) {
			$courseEnrollment = false;
			html_var_dump($_REQUEST);
			if (empty($_REQUEST['section'])) {
				if (!empty($_REQUEST['course'])) {
					$courseEnrollment = true;
				} else {
					$smarty->addMessage(
						'Course or Section',
						'Missing from enrollment request.',
						NotificationMessage::ERROR
					);
					$step = STEP_INSTRUCTIONS;
				}
			}
			
			if (empty($_REQUEST['users'])) {
				$smarty->addMessage(
					'Users',
					'missing from enrollment request.',
					NotificationMessage::ERROR
				);
			} elseif ($step == STEP_ENROLL) {
				$count = 0;
				foreach ($_REQUEST['users'] as $user) {
					$enrollment = $api->post(
						(
							$courseEnrollment ?
							"/courses/{$_REQUEST['course']}/enrollments" :
							"/sections/{$_REQUEST['section']}/enrollments"
						),
						array(
							'enrollment[user_id]' => $user['id'],
							'enrollment[role_id]' => $user['role'],
							'enrollment[enrollment_state]' => 'active',
							'enrollment[notify]' => (empty($user['notify']) ? 'false' : $user['notify'])
						)
					);
					if (!empty($enrollment['id'])) {
						$count++;
					} // FIXME should really list errors, no?
				}
				
				if ($courseEnrollment) {
					$course = $_REQUEST['course'];
				} else {
					$section = $api->get("sections/{$_REQUEST['section']}");
					$course = $section['course_id'];
				}
				
				// FIXME no longer have the course ID… link is broken
				$smarty->addMessage(
					'Success',
					"<a target=\"_top\" href=\"{$_SESSION['canvasInstanceUrl']}/courses/$course/users\">$count users enrolled</a>",
					NotificationMessage::GOOD
				);
				
				$_REQUEST = array();
			}
		}
	
	case STEP_INSTRUCTIONS:
	default:
		if (!empty($_REQUEST['users'])) {
			$smarty->assign('users', $_REQUEST['users']);
		}
		if (!empty($_REQUEST['course'])) {
			$smarty->assign('course', $_REQUEST['course']);
		}
		
		$smarty->assign('roles', $roles);
		$smarty->assign('formHidden', array('step' => STEP_CONFIRM));
		$smarty->display(basename(__FILE__, '.php') . '/instructions.tpl');
}
	
?>