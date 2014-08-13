<?php

// TODO generate a real log file
// TODO rejigger a real CSV upload -- something was broken and I was in a rush

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

/* cache term ID/SIS ID associations */
// FIXME this ignores pagination -- should fix the Api Process to make that not a problem
$termsResponse = $api->get('accounts/1/terms');
$terms = array();
foreach($termsResponse['enrollment_terms'] as $t) {
	$terms[$t['sis_term_id']] = $t['id'];
}

/* cache account ID/SIS ID associations */
// FIXME this also ignores pagination
$accountsResponse = $api->get('accounts/1/sub_accounts', array('recursive' => 'true'));
$accounts = array();
foreach($accountsResponse as $a) {
	$accounts[$a['sis_account_id']] = $a['id'];
}

/* cache course ID/SIS ID associations */
$courseResponse = $api->get('accounts/132/courses');
$courses = array();
do {
	foreach($courseResponse as $c) {
		$courses[$c['sis_course_id']] = $c['id'];
	}
} while ($courseResponse = $api->nextPage());

// FIXME stupid file upload isn't stupid working and I stupid don't have stupid time to stupid deal with it
if (($csv = fopen(/*$mergeCsv*/'Summer Course Merge - merge.csv', 'r')) !== false) {
	$column = array();
	
	/* figure out column label indices */
	$columnLabels = fgetcsv($csv);
	while (list($key, $label) = each($columnLabels)) {
		$column[$label] = $key;
	}
	
	/* walk through the data and deal with each course one at a time */
	while (($data = fgetcsv($csv)) !== false) {
	
		switch ($data[$column['action']]) {
			case 'add': {
			
				/* create the course */
				$course = $api->post("accounts/{$accounts[$data[$column['sis_account_id']]]}/courses",
					array(
						'account_id' => $accounts[$data[$column['sis_account_id']]],
						'course[name]' => $data[$column['long_name']],
						'course[course_code]' => $data[$column['short_name']],
						'course[term_id]' => $terms[$data[$column['sis_term_id']]],
						'course[sis_course_id]' => $data[$column['sis_course_id']]
					)
				);
				$courses[$course['sis_course_id']] = $course['id'];
				echo "Created '{$course['name']}' with SIS ID {$course['sis_course_id']} and ID {$course['id']}\n";
				
				/* create the matching section name and SIS ID */
				$section = $api->post("courses/{$course['id']}/sections",
					array(
						'course_section[name]' => $data[$column['long_name']],
						'course_section[sis_section_id]' => $data[$column['sis_section_id']]
					)
				);
				echo "Created section '{$section['name']}' with SIS ID {$section['sis_section_id']} and ID {$section['id']}\n";
				
				/* enroll the teacher in the section */
				// FIXME assuming that one SIS ID cannot be a substring of another. May not be safe!
				$users = $api->get('accounts/1/users',
					array(
						'search_term' => $data[$column['sis_teacher_id']]
					)
				);
				if (($user = $users[0]) != false) {
					$api->post("sections/{$section['id']}/enrollments",
						array(
							'enrollment[user_id]' => $user['id'],
							'enrollment[type]' => 'TeacherEnrollment'
						)
					);
					echo "Enrolled {$user['name']} with SIS ID {$user['sis_user_id']} and ID {$user['id']} as teacher\n";
				} else {
					echo "A teacher with the SIS ID {$data[$column['sis_teacher_id']]} could not be found for this class\n";
				}
				
				break;
			}
			case 'delete': {
			
				/* move deleted courses into sandbox term (no edits) and "Marked for Deletion" account to filter by hand */
				$course = $api->put("courses/{$courses[$data[$column['summer_sis_course_id']]]}",
					array(
						'course[name]' => "OBSOLETE {$course['name']}",
						'course[course_code]' => $course['course_code'],
						'course[term_id]' => $terms['sandbox'],
						'course[account_id]' => $accounts['delete']
					)
				);
				$courses[$course['sis_course_id']] = $course['id'];
				echo "Marked '{$course['name']}' for deletion with SIS ID {$course['sis_course_id']} and ID {$course['id']}\n";
				break;
			}
			case 'rename': {
			
				/* do nothing -- these courses will be handled later */
				break;
			}
			case 'modify': {
			
				/* modify is actually the default case, since everyone gets modified, not just the one's specifically marked */
				
				/* because the course codes are semester-specific, to get them to match, there are some duplicate entries for courses -- modified courses need to refer back to their original course code */
				$data[$column['summer_sis_course_id']] = $data[$column['modified_sis_course_id']];
			}
			default: {
				$course = $api->put("courses/{$courses[$data[$column['summer_sis_course_id']]]}",
					array(
						'course[name]' => $data[$column['long_name']],
						'course[course_code]' => $data[$column['short_name']],
						'course[sis_course_id]' => $data[$column['sis_course_id']],
						'course[term_id]' => $terms[$data[$column['sis_term_id']]],
						'course[account_id]' => $accounts[$data[$column['sis_account_id']]]
					)
				);
				$courses[$course['sis_course_id']] = $course['id'];
				echo "Updated '{$course['name']}' from SIS ID {$data[$column['summer_sis_course_id']]} to {$course['sis_course_id']} with ID {$course['id']}\n";
				
				$sectionResponse = $api->get("courses/{$course['id']}/sections");
				if (($section = $sectionResponse[0]) != false) {
					$section = $api->put("sections/{$section['id']}",
						array(
							'course_section[name]' => $data[$column['long_name']],
							'course_section[sis_section_id]' => $data[$column['sis_section_id']]
						)
					);
					echo "Updated section to '{$section['name']}' to SIS ID '{$section['sis_section_id']}' with ID {$section['id']}\n";
				} else {
					$section = $api->post("courses/{$course['id']}/sections",
						array(
							'course_section[name]' => $data[$column['long_name']],
							'course_section[sis_section_id]' => $data[$column['sis_section_id']]
						)
					);
					echo "Created section '{$section['name']}' with SIS ID {$section['sis_section_id']} and ID {$section['id']}\n";
				}
			}
		}
		echo "\n\n";
	}
}

debugFlag('FINISH');

?>