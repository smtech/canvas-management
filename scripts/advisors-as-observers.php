<?php

/* some configuration */
define('ADVISORY_SUBACCOUNT', 74); // the Canvas sub-account containing our advisory groups
define('LOGIN_PAGE_URL', 'advisor-as-observer-logins'); // the url of the page in each advisory course
define('PASSWORD_LENGTH', 10); // be reasonable
define('PASSWORD_SECURE', false); // we actually want things people can remember
define('PASSWORD_NUMERALS', false); // no need for numbers
define('PASSWORD_CAPITALS', false); // let's not have confusing capital letters
define('PASSWORD_AMBIGUOUS', false); // since we have no numbers, ambigous characters are fine 
define('PASSWORD_NO_VOWELS', false); // we'll risk generating dirty words
define('PASSWORD_SYMBOLS', false); // no confusing symbols


require_once('.ignore.live-authentication.inc.php');

define('TOOL_NAME', "Advisors as Observers");

define('DEBUGGING', 2);

require_once('config.inc.php');
require_once(SMCANVASLIB_PATH . '/include/pwgen-php/PWGen.php');

debugFlag('START');

$coursesApi = new CanvasApiProcess(CANVAS_API_URL, CANVAS_API_TOKEN);
$usersApi = new CanvasApiProcess(CANVAS_API_URL, CANVAS_API_TOKEN);
$api = new CanvasApiProcess(CANVAS_API_URL, CANVAS_API_TOKEN);

$pwgen = new PWGen(PASSWORD_LENGTH, PASSWORD_SECURE, PASSWORD_NUMERALS, PASSWORD_CAPITALS, PASSWORD_AMBIGUOUS, PASSWORD_NO_VOWELS, PASSWORD_SYMBOLS);

/* walk through all of our advisory courses.. */
$courses = $coursesApi->get('accounts/' . ADVISORY_SUBACCOUNT . '/courses',
		array(
			'with_enrollments' => 'true',
			'enrollment_term_id' => $_REQUEST['enrollment_term_id']
		)
	);
	
echo "advisee[name]\t" .
	"advisee[sis_user_id]\t" .
	"user_id\t" .
	"login_id\t" .
	"password\t" .
	"full_name\t" .
	"sortable_name\t" .
	"short_name\t" .
	"email\t" .
	"status\t" .
	"page[url]\n";
do {
	foreach($courses as $advisoryCourse) {
		/* cache the teacher */
		$teachers = $usersApi->get("courses/{$advisoryCourse['id']}/users",
			array(
				'enrollment_role' => 'TeacherEnrollment'
			)
		);
		if (count($teachers)) {
			$teacher = $teachers[0];
		} else {
			echo "ERROR No teacher found for {$advisoryCourse['name']} with ID {$advisoryCourse['id']}\n";
			break;
		}
	
		/* start a cache of advisee login information */
		$advisees = array();
	
		/* look at all the student enrollments... */
		$users = $usersApi->get("courses/{$advisoryCourse['id']}/users",
			array(
				'enrollment_role' => 'StudentEnrollment'
			)
		);
		do {
			foreach($users as $student) {
									
				/* check if we already have an advisor account for this student */
				$sisAdvisorId = "{$student['sis_user_id']}-advisor";
				$response = $api->get('accounts/1/users',
					array(
						'search_term' => $sisAdvisorId
					)
				);
				
				/* generate what the advisor account info should be */
				$advisorLogin['sis_user_id'] = "{$student['sis_user_id']}-advisor";
				$advisorLogin['last_name'] = substr($teacher['sortable_name'], 0, strpos($teacher['sortable_name'], ','));
				$advisorLogin['login'] = strtolower('advisor' . substr($student['login_id'], 0, strpos($student['login_id'], '@')));
				$advisorLogin['password'] = $pwgen->generate();
				$advisorLogin['name'] = "{$student['name']} ({$advisorLogin['last_name']} Advisor)";
				$advisorLogin['sortable_name'] = "{$student['sortable_name']} ({$advisorLogin['last_name']} Advisor)";
				$advisorLogin['short_name'] = "{$student['short_name']} ({$advisorLogin['last_name']} Advisor)";
				
				/* this email format works for Google Apps domains -- it's the advisor's email address with a tag that identifies the email as relating to the advisee */
				$advisorLogin['email'] = strtolower(substr($teacher['sis_login_id'], 0, strpos($teacher['sis_login_id'], '@')) . '+' . substr($student['sis_login_id'], 0, strpos($student['sis_login_id'], '@')) . substr($teacher['sis_login_id'], strpos($teacher['sis_login_id'], '@')));
								
				/* check for an existing advisor account */
				$response = $api->get('accounts/1/users',
					array(
						'search_term' => $advisorLogin['sis_user_id']
					)
				);
				
				/* if there is already an advisor account, update it*/
				if (count($response)) {
					$advisor = $response[0];
					
					/* update name */
					$advisor = $api->put("users/{$advisor['id']}",
						array(
							'user[name]' => $advisorLogin['name'],
							'user[short_name]' => $advisorLogin['short_name'],
							'user[sortable_name]' => $advisorLogin['sortable_name'],
						)
					);
					
					/* update email */
					$communicationChannels = $api->get("users/{$advisor['id']}/communication_channels");
					$emailExists = false;
					$channelsToDelete = array();
					foreach($communicationChannels as $communicationChannel) {
						if ($communicationChannel['address'] != $advisorLogin['email']) {
							$channelsToDelete[] = $communicationChannel['id'];
						} else {
							$emailExists = true;
						}
					}
					if (!$emailExists) {
						$api->post("users/{$advisor['id']}/communication_channels",
							array(
								'communication_channel[address]' => $advisorLogin['email'],
								'communication_channel[type]' => 'email',
								'skip_confirmation' => true,
								'position' => 1
							)
						);
					}
					foreach($channelsToDelete as $channelToDelete) {
						$api->delete("users/{$advisor['id']}/communication_channels/{$channelToDelete}");
					}
					
					/* turn off notifications */
					$communicationChannels = $api->get("users/{$advisor['id']}/communication_channels");
					$notificationPreferences = $api->get("users/{$advisor['id']}/communication_channels/{$communicationChannels[0]['id']}/notification_preferences");
					$newPrefs = array();
					foreach ($notificationPreferences['notification_preferences'] as $pref) {
						if (($pref['frequency'] != 'never') && ($pref['notification'] != 'confirm_sms_communication_channel')) {
							$newPrefs["notification_preferences[{$pref['notification']}][frequency]"] = 'never';
						}
					}
					if (count($newPrefs)) {
						$newPrefs['as_user_id'] = $advisor['id'];
						$api->put("users/self/communication_channels/{$communicationChannels[0]['id']}/notification_preferences", $newPrefs);
					}
										
					/* reset password */
					if (isset($_REQUEST['reset_passwords'])) {
						$logins = $api->get("users/{$advisor['id']}/logins");
						// FIXME I'm totally just assuming that a user account has a login (and only one)
						$api->put("accounts/1/logins/{$logins[0]['id']}",
							array(
								'login[password]' => $advisorLogin['password']
							)
						);
					}
					
				/* otherwise, create one! */
				} else {
					$advisor = $api->post('accounts/1/users',
						array(
							'user[name]' => $advisorLogin['name'],
							'user[short_name]' => $advisorLogin['short_name'],
							'user[sortable_name]' => $advisorLogin['sortable_name'],
							'pseudonym[unique_id]' => $advisorLogin['login'],
							'psuedonym[password]' => $advisorLogin['password'],
							'pseudonym[sis_user_id]' => $advisorLogin['sis_user_id'],
							'communication_channel[type]' => 'email',
							'communication_channel[address]' => $advisorLogin['email'],
							'communication_channel[skip_confirmation]' => true
						)
					);
				}
				
				/* set up observation pairing */
				$api->put("users/{$advisor['id']}/observees/{$student['id']}");
															
				$advisees[] = array(
					'student' => $student,
					'advisor' => $advisor,
					'advisor_login' => $advisorLogin,
					'advisory_course' => $advisoryCourse
				);
			}
		} while ($users = $usersApi->nextPage());
		

		$page = '
			<p>At any point during the year, you may log in to observe your advisees in Canvas -- you will see what they see, without any danger that you will accidentally make changes. To observe an advisee, use the respective login below to access Canvas. You may configure notifications for each advisee as you wish. Emailed notifications for each advisee will be sent to you at each respective address (hint: <a href="https://support.google.com/mail/answer/6579?hl=en">Gmail filters</a> rock!).</p>
			<p>This page is invisible to your advisees (even if you publish your Advisory &ldquo;course&rdquo;), however <em>pease do not publish <strong>this</strong> page!</em></p>
			<table width="100%" class="striped">
				<tr>
					<th>Advisee</th>
					<th>Login</th>
					<th>Password</th>
					<th>Notification Email</th>
				</tr>';
		$row = 0;
		foreach($advisees as $advisee) {
			echo "{$advisee['student']['name']}\t" .
				"{$advisee['student']['sis_user_id']}\t" .
				"{$advisee['advisor_login']['sis_user_id']}\t" .
				"{$advisee['advisor_login']['login']}\t" .
				"{$advisee['advisor_login']['password']}\t" .
				"{$advisee['advisor_login']['name']}\t" .
				"{$advisee['advisor_login']['sortable_name']}\t" .
				"{$advisee['advisor_login']['short_name']}\t" .
				"{$advisee['advisor_login']['email']}\t" .
				"active\t" .
				"https://" . parse_url(CANVAS_API_URL, PHP_URL_HOST) . "/courses/{$advisoryCourse['id']}/pages/" . LOGIN_PAGE_URL . "\n";
			$page .= "
				<tr" . ($row++ % 2 ? ' class="stripe"' : '') . ">
					<td>{$advisee['student']['name']}</td>
					<td>{$advisee['advisor_login']['login']}</td>
					<td><span style=\"font-family: 'Courier New',Courier,monospace\">{$advisee['advisor_login']['password']}</span></td>
					<td>{$advisee['advisor_login']['email']}</td>
				</tr>";
		}
		$page .= "</table>";
		$page = $api->put("courses/{$advisoryCourse['id']}/pages/" . LOGIN_PAGE_URL,
			array(
				'wiki_page[title]' => 'Advisor-as-Observer Logins',
				'wiki_page[body]' => $page,
				'wiki_page[editing_roles]' => 'teachers',
				'wiki_page[published]' => false,
				'wiki_page[front_page]' => false
			)
		);
		debugFlag("{$advisorLogin['last_name']} advisory updated");
	}
} while ($courses = $coursesApi->nextPage());

debugFlag('FINISH');

?>