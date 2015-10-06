<?php

require_once('common.inc.php');

define('STEP_INSTRUCTIONS', 1);
define('STEP_RENAME', 2);

$step = (empty($_REQUEST['step']) ? STEP_INSTRUCTIONS : $_REQUEST['step']);

switch ($step) {
	
	case STEP_RENAME:
		try {
			$updated = 0;
			$unchanged = 0;
			$courses = $api->get(
				"accounts/{$_REQUEST['account']}/courses",
				array(
					'enrollment_term_id' => $_REQUEST['term'],
					'with_enrollments' => 'true'
				)
			);
			foreach ($courses as $course) {
				$teachers = $api->get(
					"/courses/{$course['id']}/enrollments",
					array(
						'type' => 'TeacherEnrollment'
					)
				);
				if ($teacher = $teachers[0]['user']) {
					$nameParts = explode(',', $teacher['sortable_name']);
					$courseName = trim($nameParts[0]) . ' Advisory Group';
					$api->put(
						"courses/{$course['id']}",
						array(
							'course[name]' => $courseName,
							'course[course_code]' => $courseName
						)
					);
					$sections = $api->get("courses/{$course['id']}/sections");
					foreach($sections as $section) {
						if ($section['name'] == $course['name']) {
							$api->put(
								"sections/{$sections[0]['id']}",
								array(
									'course_section[name]' => $courseName
								)
							);
						}
					}
					$updated++;
				} else {
					$unchanged++;
				}
			}
		} catch (Exception $e) {
			exceptionErrorMessage($e);
		}
		$courses = $api->get(
			"accounts/{$_REQUEST['account']}/courses",
			array(
				'enrollment_term_id' => $_REQUEST['term'],
				'with_enrollments' => 'true',
				'published' => 'true'
			)
		);
		
		$smarty->addMessage(
			'Renamed advisory courses',
			"$updated courses were renamed, and $unchanged were left unchanged.",
			NotificationMessage::GOOD
		);
	
	case STEP_INSTRUCTIONS:
	default:
		$smarty->assign('accounts', getAccountList());
		$smarty->assign('terms', getTermList());
		$smarty->assign('formHidden', array('step' => STEP_RENAME));
		$smarty->display(basename(__FILE__, '.php') . '/instructions.tpl');
}
	
?>