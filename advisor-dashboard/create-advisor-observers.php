<?php

require_once('common.inc.php');

$cache = new \Battis\SimpleCache($advisorDashboard);

/* some configuration */
define('ADVISORY_SUBACCOUNT', 74); // the Canvas sub-account containing our advisory groups

define('PASSWORD_LENGTH', 10); // be reasonable
define('PASSWORD_SECURE', false); // we actually want things people can remember
define('PASSWORD_NUMERALS', false); // no need for numbers
define('PASSWORD_CAPITALS', false); // let's not have confusing capital letters
define('PASSWORD_AMBIGUOUS', false); // since we have no numbers, ambigous characters are fine 
define('PASSWORD_NO_VOWELS', false); // we'll risk generating dirty words
define('PASSWORD_SYMBOLS', false); // no confusing symbols

$pwgen = new PWGen(
	PASSWORD_LENGTH,
	PASSWORD_SECURE,
	PASSWORD_NUMERALS,
	PASSWORD_CAPITALS,
	PASSWORD_AMBIGUOUS,
	PASSWORD_NO_VOWELS,
	PASSWORD_SYMBOLS
);

define('STEP_INSTRUCTIONS', 1);
define('STEP_GENERATE', 2);

$step = (empty($_REQUEST['step']) ? STEP_INSTRUCTIONS : $_REQUEST['step']);

switch ($step) {
	case STEP_GENERATE:
	
		// TODO test for account and term
	
		//try {
			/* walk through all of our advisory courses.. */
			$advisories = $api->get(
				"accounts/{$_REQUEST['account']}/courses",
				array(
					'with_enrollments' => 'true',
					'enrollment_term_id' => $_REQUEST['term']
				)
			);
			
			$courses = 0;
			$updated = 0;
			$created = 0;
			$reset = 0;
			
			foreach($advisories as $advisory) {
				/* cache the teacher */
				$advisors = $api->get("courses/{$advisory['id']}/users",
					array(
						'enrollment_role' => 'TeacherEnrollment'
					)
				);
				if ($advisors->count()) {
					$advisor = $advisors[0];
					$courses++;
				} else {
					$smarty->addMessage(
						"{$advisory['name']}",
						"No teacher was found in <a target=\"_parent\" href=\"{$_SESSION['canvasInstanceUrl']}/courses/{$advisory['id']}\">this advisory</a> and it was skipped.",
						NotificationMessage::ERROR
					);
					break;
				}
			
				/* look at all the student enrollments... */
				$advisees = $api->get("courses/{$advisory['id']}/users",
					array(
						'enrollment_role' => 'StudentEnrollment'
					)
				);
				
				foreach($advisees as $advisee) {
					
					$observer = array();
										
					/* generate what the advisor account info should be */
					$observer['sis_user_id'] = "{$advisee['sis_user_id']}-advisor";
					$observer['last_name'] = substr($advisor['sortable_name'], 0, strpos($advisor['sortable_name'], ','));
					$observer['login'] = strtolower('advisor' . substr($advisee['login_id'], 0, strpos($advisee['login_id'], '@')));
					$observer['password'] = $pwgen->generate();
					$observer['name'] = "{$advisee['name']} ({$observer['last_name']} Advisor)";
					$observer['sortable_name'] = "{$advisee['sortable_name']} ({$observer['last_name']} Advisor)";
					$observer['short_name'] = "{$advisee['short_name']} ({$observer['last_name']} Advisor)";
					
					
					/* this email format works for Google Apps domains -- it's the advisor's email address with a tag that identifies the email as relating to the advisee */
					$observer['email'] = strtolower(substr($advisor['sis_login_id'], 0, strpos($advisor['sis_login_id'], '@')) . '+' . substr($advisee['sis_login_id'], 0, strpos($advisee['sis_login_id'], '@')) . substr($advisor['sis_login_id'], strpos($advisor['sis_login_id'], '@')));
					
					/* check for an existing advisor account */
					$existing = true;
					try {
						$existing = $api->get("users/sis_user_id:{$observer['sis_user_id']}");
					} catch (Exception $e) {
						/* if the request generates an error... the observer does not exist */
						$existing = false;				
					}
					
					/* if there is already an advisor account, update it */
					if ($existing) {
	
						/* update name */
						$advisor = $api->put("users/{$existing['id']}",
							array(
								'user[name]' => $observer['name'],
								'user[short_name]' => $observer['short_name'],
								'user[sortable_name]' => $observer['sortable_name'],
							)
						);
						
						/* update email */
						$communicationChannels = $api->get("users/{$existing['id']}/communication_channels");
						$emailExists = false;
						$channelsToDelete = array();
						foreach($communicationChannels as $communicationChannel) {
							if ($communicationChannel['address'] != $observer['email']) {
								$channelsToDelete[] = $communicationChannel['id'];
							} else {
								$emailExists = true;
							}
						}
						if (!$emailExists) {
							$api->post("users/{$existing['id']}/communication_channels",
								array(
									'communication_channel[type]' => 'email',
									'communication_channel[address]' => $observer['email'],
									'skip_confirmation' => true,
									'position' => 1
								)
							);
						}
						foreach($channelsToDelete as $channelToDelete) {
							$api->delete("users/{$existing['id']}/communication_channels/{$channelToDelete}");
						}
						
						/* turn off notifications */
						$communicationChannels = $api->get("users/{$existing['id']}/communication_channels");
						$notificationPreferences = $api->get("users/{$existing['id']}/communication_channels/{$communicationChannels[0]['id']}/notification_preferences");
						$newPrefs = array();
						foreach ($notificationPreferences['notification_preferences'] as $pref) {
							if (($pref['frequency'] != 'never') && ($pref['notification'] != 'confirm_sms_communication_channel')) {
								$newPrefs["notification_preferences[{$pref['notification']}][frequency]"] = 'never';
							}
						}
						if (count($newPrefs)) {
							$newPrefs['as_user_id'] = $existing['id'];
							$api->put("users/self/communication_channels/{$communicationChannels[0]['id']}/notification_preferences", $newPrefs);
						}
											
						/* reset password */
						if (isset($_REQUEST['reset_passwords'])) {
							$logins = $api->get("users/{$existing['id']}/logins");
							
							// FIXME I'm totally just assuming that a user account has a login (and only one)
							$api->put("accounts/1/logins/{$logins[0]['id']}",
								array(
									'login[password]' => $observer['password']
								)
							);
							$cache->setCache($existing['id'], $observer['password']);
							$reset++;
						}
						$updated++;
						
					/* otherwise, create one! */
					} else {
						$existing = $api->post('accounts/1/users',
							array(
								'user[name]' => $observer['name'],
								'user[short_name]' => $observer['short_name'],
								'user[sortable_name]' => $observer['sortable_name'],
								'pseudonym[unique_id]' => $observer['login'],
								'psuedonym[password]' => $observer['password'],
								'pseudonym[sis_user_id]' => $observer['sis_user_id'],
								'communication_channel[type]' => 'email',
								'communication_channel[address]' => $observer['email'],
								'communication_channel[skip_confirmation]' => true
							)
						);
						$cache->setCache($existing['id'], $observer['password']);
						$created++;
					}
					
					/* set up observation pairing */
					$api->put("users/{$existing['id']}/observees/{$advisee['id']}");
				}

				// TODO rename advisory courses
			}
			
			$smarty->addMessage(
				'Advisor-Observers',
				"$created new observers created, $updated observers updated ($reset passwords reset) in $courses advisory groups.",
				NotificationMessage::GOOD
			);
			
		//} catch (Exception $e) {
		//	exceptionErrorMessage($e);
		//}
	
		/* flows into STEP_INSTRUCTIONS */
	
	case STEP_INSTRUCTIONS:
	default:
		$smarty->assign('accounts', getAccountList());
		$smarty->assign('terms', getTermList());
		$smarty->assign('formHidden', array('step' => STEP_GENERATE));
		$smarty->display(basename(__FILE__, '.php') . '/instructions.tpl');
}


?>