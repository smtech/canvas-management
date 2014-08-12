<?php

define ('TOOL_NAME', "Summer Course Merge");
require_once('config.inc.php');

require_once(SMCANVASLIB_PATH . '/include/working-directory.inc.php');
define('UPLOAD_DIR', '/var/www-data/canvas/scripts'); // where we'll store uploaded files

debugFlag('START');
/*
$mergeCsv = null;
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	if (isset($_FILES['merge_csv'])) {
		if ($_FILES['merge_csv']['error'] === UPLOAD_ERR_OK) {
			$mergeCsv = buildPath(UPLOAD_DIR, basename($_FILES['merge_csv']['name']));
			// FIXME: need a per-session temp directory structure to prevent over-writes/conflicts
			if (!move_uploaded_file($_FILES['merge_csv']['tmp_name'], $mergeCsv)) {
				displayError(
					array(
						'$_FILES[merge_csv]' => $_FILES['merge_csv'],
						'$mergeCsv' => $mergeCsv
					), true,
				'Upload Error',
				'There was an error with your file upload. See the <a href="http://www.php.net/manual/en/features.file-upload.errors.php">PHP Documentation</a> for more information.'
				);
			}
		} else {
			displayError(
				array(
					'merge_csv[error]' => $_FILES['merge_csv']['error'],
					'Request' => $_REQUEST
				), true,
				'Upload Error',
				'There was an error with your file upload. See the <a href="http://www.php.net/manual/en/features.file-upload.errors.php">PHP Documentation</a> for more information.'
			);
			exit;
		}
	} else {
		displayError(
			array(
				'Request' => $_REQUEST
			), true,
			'No File Uploaded'
		);
		exit;
	}
}
echo "Began processing '$mergeCsv'\n";
*/
$api = new CanvasApiProcess(CANVAS_API_URL, CANVAS_API_TOKEN);

// FIXME this ignores pagination -- should fix the Api Process to make that not a problem
$termsResponse = $api->get('accounts/1/terms');
$terms = array();
foreach($termsResponse['enrollment_terms'] as $term) {
	$terms[$term['sis_term_id']] = $term['id'];
}

// FIXME this also ignores pagination
$accountsResponse = $api->get('accounts/132/sub_accounts', array('recursive' => 'true'));
$accounts = array();
foreach($accountsResponse as $acct) {
	$accounts[$acct['sis_account_id']] = $acct['id'];
}

if (($csv = fopen(/*$mergeCsv*/'Summer Course Merge - merge.csv', 'r')) !== false) {
	$col = fgetcsv($csv); {
		$summerCourseSisId = array_search('summer_course_sis_id', $col);
		$action = array_search('action', $col);
		$courseSisId = array_search('course_sis_id', $col);
		$sectionSisId = array_search('section_sis_id', $col);
		$longName = array_search('long_name', $col);
		$shortName = array_search('short_name', $col);
		$accountId = array_search('account_id', $col);
		$termId = array_search('term_id', $col);
		$teacherSisId = array_search('teacher_sis_id', $col);
	}
	while (($data = fgetcsv($csv)) !== false) {
		switch ($data[$action]) {
			case 'add': break;{
			
				/* create the course */
				$course = $api->post("accounts/{$accounts[$data[$accountId]]}/courses",
					array(
						'account_id' => $accounts[$data[$accountId]],
						'course[name]' => $data[$longName],
						'course[course_code]' => $data[$shortName],
						'course[term_id]' => $terms[$data[$termId]],
						'course[course_sis_id]' => $data[$courseSisId]
					)
				);
				echo "Created '{$course['name']}' with SIS ID {$course['sis_course_id']} and ID {$course['id']}\n";
				
				/* create the matching section name and SIS ID */
				// FIXME probably not a good idea to assume that there is just one section... but there are, in this case
				$section = $api->post("courses/{$course['id']}/sections",
					array(
						'course_section[name]' => $data[$longName],
						'course_section[sis_section_id]' => $data[$sectionSisId]
					)
				);
				echo "Created section '{$section['name']}' with SIS ID {$section['sis_section_id']} and ID {$section['id']}\n";
				
				/* enroll the teacher in the section */
				// FIXME again, poor form to assume only one unique response here
				$users = $api->get('accounts/1/users',
					array(
						'search_term' => $data[$teacherSisId]
					)
				);
				$user = $users[0];
				
				$api->post("sections/{$section['id']}/enrollments",
					array(
						'enrollment[user_id]' => $user['id'],
						'enrollment[type]' => 'TeacherEnrollment'
					)
				);
				echo "Enrolled {$user['name']} with SIS ID {$user['sis_user_id']} and ID {$user['id']} as teacher\n";
				break;
			}
			case 'delete': {
				break;
			}
			case 'rename': {
				// do nothing -- this will be caught later
				break;
			}
			case 'modify':
			default: {
				$courseResponse = $api->get("accounts/{$accounts[$data[$accountId]]}/courses"
					array(
						'search_term' => $data[$summerCourseSisId]
					)
				);
				$course = $courseResponse[0];
				$course = $api->("courses/{$course['id']}",
					array(
						'course[name]' => $data[$longName],
						'course[course_code]' => $data[$shortName],
						'course[course_sis_id]' => $data[$courseSisId]
					)
				);
				echo "Updated '{$course['name']' from SIS ID {$data[$summerCourseSisId]} to {$course['sis_course_id']} with ID {$course['id']}\n";
				
				$sectionResponse = $api->get("courses/{$course['id']}/sections");
				if ($section = $sectionResponse[0]) {
					$section = $api->put("sections/{$section['id']}",
						'name' => $data[$longName],
						'sis_section_id' => $data[$sectionSisId]
					);
					echo "Updated section to '{$section['name']}' to SIS ID '{$section['sis_section_id']} with ID {$section['id']}\n";
				} else {
					$section = $api->post("courses/{$course['id']}/sections",
						array(
							'course_section[name]' => $data[$longName],
							'course_section[sis_section_id]' => $data[$sectionSisId]
						)
					);
					echo "Created section '{$section['name']}' with SIS ID {$section['sis_section_id']} and ID {$section['id']}\n";
				}
			}
		}
	}
}

debugFlag('FINISH');

?>