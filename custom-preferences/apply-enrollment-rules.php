<?php
	
require_once('common.inc.php');

$cache = new \Battis\HierarchicalSimpleCache($sql, basename(__DIR__) . '/' . basename(__FILE__, '.php'));
$cache->setLifetime(60*60);

define('ENROLL', true);
// DELETE is the enrollment ID to be deleted (i.e. not a boolean value)

function getGroupMembers($group) {
	global $customPrefs; // FIXME grown-ups don't code like this
	$members = array();
	$response = $customPrefs->query("
		SELECT *
			FROM `group-memberships`
			WHERE
				`group` = '$group'
	");
	while ($membership = $response->fetch_assoc()) {
		$members[$membership['user']] = ENROLL;
	}
	
	$response = $customPrefs->query("
		SELECT *
			FROM `groups`
			WHERE
				`parent` = '$group';
	");
	while ($child = $response->fetch_assoc()) {
		$members = array_replace($members, getGroupMembers($child['id']));
	}
	
	return $members;
}

define('STEP_INSTRUCTIONS', 1);
define('STEP_ENROLL', 2);

$step = (empty($_REQUEST['step']) ? STEP_INSTRUCTIONS : $_REQUEST['step']);

switch ($step) {

	case STEP_ENROLL:
	
		try {
			$rules = $customPrefs->query("
				SELECT *
					FROM `enrollment-rules` as `rules`
					LEFT JOIN
						`groups` ON `groups`.`id` = `rules`.`group`
			");
			$courses = array();
			
			/* walk through all the enrollment rules */
			while ($rule = $rules->fetch_assoc()) {
				
				/* find users whose role matches this rule... */
				if (!empty($rule['role'])) {
					$response = $customPrefs->query("
						SELECT *
							FROM `users`
							WHERE `role` = '{$rule['role']}'
					");
					while ($row = $response->fetch_assoc()) {
						$courses[$rule['course']][$row['id']] = ENROLL;
					}
				
				/* ...or whose group matches this rule (recursively) */
				} elseif (!empty($rule['group'])) {
					if (empty($courses[$rule['course']])) {
						$courses[$rule['course']] = array();
					}
					$courses[$rule['course']] = array_replace($courses[$rule['course']], getGroupMembers($rule['group']));
					
				/* ...or post an error, because rules expect a role or a group! */
				} else {
					$smarty->addMessage(
						'Missing Role or Group',
						"Rule ID {$rule['id']} is missing both a role and a group. It must have one or the other!",
						NotificationMessage::ERROR
					);
				}
			}
			
			// TODO worry about non-student default enrollment types
			/* process enrollments for each course */
			foreach ($courses as $courseId => $enrollees) {
				/* get the list of current enrollments for the course the rule refers to */
				$enrolled = 0;
				$deleted = 0;
				$current = 0;
				$courseExists = true;
				try {
					$course = $api->get("courses/$courseId");
					$enrollments = $api->get("courses/$courseId/enrollments");
				} catch (Exception $e) {
					$courseExists = false;
					$smarty->addMessage(
						"Course ID $courseId",
						'This course does not exist (in this instance).',
						NotificationMessage::WARNING
					);
				}
				
				if ($courseExists) {
					/* walk through the current enrollments and... */
					$potentialDuplicates = array();
					foreach ($enrollments as $enrollment) {
						
						/* ignore observers -- they should all be assigned by API to track specific users */
						if ($enrollment['type'] != 'ObserverEnrollment') {
							
							/* ...clear users already enrolled... */
							if (isset($enrollees[$enrollment['user']['id']])) {
								unset($enrollees[$enrollment['user']['id']]);
								
								/* make a note of this user in potential duplicates, in case they have
								   multiple enrollments -- leave 'em all! */
								$potentialDuplicates[$enrollment['user']['id']] = true;
								$current++;
								
							/* ...and purge users who should not be enrolled */
							} elseif (!isset($potentialDuplicates[$enrollment['user']['id']])) {
								$api->delete(
									"courses/$courseId/enrollments/{$enrollment['id']}",
									array(
										'task' => 'delete'
									)
								);
								$deleted++;
							}
						}
					}

					foreach ($enrollees as $userId => $status) {
						try {
							$api->post(
								"courses/$courseId/enrollments",
								array(
									'enrollment[user_id]' => $userId,
									'enrollment[type]' => 'StudentEnrollment',
									'enrollment[enrollment_state]' => 'active',
									'enrollment[notify]' => 'false'
								)
							);
							$enrolled++;
						} catch (Exception $e) {
							$smarty->addMessage(
								"User ID $userId",
								"There was an error enrolling User ID $userId in <a target=\"_parent\" href=\"{$_SESSION['canvasInstanceUrl']}/courses/$courseId/users\">{$course['name']}</a>. This user may have a mis-assigned role or no longer exist in this instance.",
								NotificationMessage::ERROR
							);
						}
					}
					$smarty->addMessage(
						$course['name'],
						"After applying enrollment rules, <a target=\"_parent\" href=\"{$_SESSION['canvasInstanceUrl']}/courses/$courseId/users\">$enrolled new users were enrolled in this course</a>, $current users were unchanged, and $deleted users were removed."
					);
				}
			}
		} catch (Exception $e) {
			exceptionErrorMessage($e);
		}
		
		/* flows into STEP_INSTRUCTIONS */
	
	case STEP_INSTRUCTIONS:
		$smarty->assign('formHidden', array('step' => STEP_ENROLL));
		$smarty->display(basename(__FILE__, '.php') . '/instructions.tpl');
	
}
	
?>