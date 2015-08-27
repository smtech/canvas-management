<?php

require_once('common.inc.php');

$MANUALLY_CREATED_COURSES_ACCOUNT = 96;
$DEFAULT_TERM = 195;
define('CACHE_LIFETIME', 20 * 60); // 20 minutes
define('LONG_CACHE_LIFETIME', 7 * 24 * 60 * 60); // 1 week

$cache = new Battis\HiearchicalSimpleCache($sql, basename(__DIR__) . '/' . basename(__FILE__, '.php'));

define('STEP_INSTRUCTIONS', 1);
define('STEP_CONFIRM', 2);
define('STEP_ENROLL', 3);

$step = (empty($_REQUEST['step']) ? STEP_INSTRUCTIONS : $_REQUEST['step']);

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
			$courses = $cache->getCache("courses/{$_REQUEST['course']}");
			if ($courses === false) {
				$courses = $api->get(
					'accounts/1/courses',
					array(
						'search_term' => $_REQUEST['course']
					)
				);
				$cache->setCache("courses/{$_REQUEST['course']}", $courses, CACHE_LIFETIME);
			}
			
			if ($courses->count() == 0) {
				$smarty->addMessage(
					'No Courses',
					"matched your search term '{$_REQUEST['course']}'.",
					NotificationMessage::WARNING
				);
				$step = STEP_INSTRUCTIONS;
			}
		}
		
		if (!empty($users) && $step == STEP_CONFIRM) {
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
			
			$smarty->assign('courses', $courses);
			$smarty->assign('confirm', $confirm);
			$smarty->assign('roles', array(
				'StudentEnrollment' => 'Student',
				'TeacherEnrollment' => 'Teacher',
				'TaEnrollment' => 'TA',
				'ObserverEnrollment' => 'Observer',
				'DesignerEnrollment' => 'Designer'
			));
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
		
		/* flow into STEP_ENROLL (and STEP_INSTRUCTIONS */
	
	case STEP_ENROLL:
		if ($step == STEP_ENROLL) {
			if (empty($_REQUEST['course'])) {
				$smarty->addMessage(
					'Course',
					'missing from enrollment request.',
					NotificationMessage::ERROR
				);
				$step = STEP_INSTRUCTIONS;
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
						"/courses/{$_REQUEST['course']}/enrollments",
						array(
							'enrollment[user_id]' => $user['id'],
							'enrollment[type]' => $user['role'],
							'enrollment[enrollment_state]' => 'active',
							'enrollment[notify]' => (empty($user['notify']) ? 'false' : $user['notify'])
						)
					);
					if (!empty($enrollment['id'])) {
						$count++;
					}
				}
				
				$smarty->addMessage(
					'Success',
					"<a target=\"_top\" href=\"{$_SESSION['canvasInstanceUrl']}/courses/{$_REQUEST['course']}/users\">$count users enrolled</a>",
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
		$smarty->assign('formHidden', array('step' => STEP_CONFIRM));
		$smarty->display(basename(__FILE__, '.php') . '/instructions.tpl');
}
	
?>