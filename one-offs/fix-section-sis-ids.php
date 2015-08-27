<?php

require_once('common.inc.php');

define('STEP_INSTRUCTIONS', 1);
define('STEP_GO', 2);

$step = (empty($_REQUEST['step']) ? STEP_INSTRUCTIONS : $_REQUEST['step']);

switch ($step) {
	case STEP_GO:
		$terms = array(
			'2015-2016-full-year',
			'2015-2016-semester-fall',
			'2015-2016-semester-spring'
		);
		
		$fixed = 0;
		$checked = 0;
		$affected = array();
		$ignored = array();
	
		try {
			foreach ($terms as $term) {
				$courses = $api->get(
					'accounts/1/courses',
					array(
						'enrollment_term_id' => "sis_term_id:$term"
					)
				);
				foreach ($courses as $course) {
					$sections = $api->get("/courses/{$course['id']}/sections");
					foreach ($sections as $section) {
						$checked++;
						if (preg_match('/wglsqla/', $section['sis_section_id'])) {
							$new = $api->put(
								"sections/{$section['id']}",
								array(
									'course_section[sis_section_id]' => strtoupper(str_replace('-temp', '', $section['sis_section_id']))
								)
							);

							$fixed++;
							$affected[] = "<a target=\"_parent\" href=\"{$_SESSION['canvasInstanceUrl']}/courses/{$course['id']}/sections/{$section['id']}\">{$section['name']}</a> ({$section['sis_section_id']} &rarr; {$new['sis_section_id']})";
						} else {
							$ignored[] = "<a target=\"_parent\" href=\"{$_SESSION['canvasInstanceUrl']}/courses/{$course['id']}/sections{$section['id']}\">{$section['name']}</a> ({$section['sis_section_id']})";
						}
					}
				}
			
			}
		} catch (Exception $e) {
			exceptionErrorMessage($e);
		}
		
		$smarty->addMessage(
			"Fixed $fixed of $checked Sections",
			'<p>All the section SIS IDs that seemed to be incorrectly capitalized have been capitalized.</p><dl><dt>Ignored</dt><dd><ol><li>' . implode('</li><li>', $ignored) . '</li><ol></dd><dt>Affected</dt><dd><ol><li>' . implode('</li><li>', $affected) . '</li></ol></dd></dl>',
			NotificationMessage::GOOD
		);
		
		/* flows into STEP_INSTRUCTIONS */
	
	case STEP_INSTRUCTIONS:
	default:
		$smarty->assign('formHidden', array('step' => STEP_GO));
		$smarty->display(basename(__FILE__, '.php') . '/instructions.tpl');
}

$terms = array(
	'2014-2015-full-year',
	'2014-2015-semester-fall',
	'2014-2015-seemster-spring'
);
	
?>