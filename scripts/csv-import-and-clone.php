<pre><?php

require_once('vendor/autoload.php');
require_once('.ignore.live-authentication.inc.php');
require_once('config.inc.php');
require_once(SMCANVASLIB_PATH . '/include/working-directory.inc.php');

$api = new CanvasPest(CANVAS_API_URL, CANVAS_API_TOKEN);

/* store the uploaded file */
// TODO real file verification checks
ini_set('auto_detect_line_endings',TRUE); // Windows is stupid
$csvFile = buildPath(getWorkingDir(), 'sis_import.csv');
move_uploaded_file($_FILES['csv']['tmp_name'], $csvFile);

/* store the course list for future cloning purposes */
$clones = array();
if (($handle = fopen($csvFile, 'r')) !== false) {
	if(($headers = fgetcsv($handle)) !== false) {
		while (($data = fgetcsv($handle)) !== false) {
			$i = count($clones);
			foreach($headers as $key=>$header) {
				$clones[$i][$header] = $data[$key];
			}
		}
	}
	fclose($handle);
}

header('Content-Disposition: attachment; filename="' . date('Y-m-d_H-i-s') .'-clones.csv";');
header('Content-Type: text/csv');

/* build a local cache of account id numbers, organized by SIS id  (we're
   intentionally not including the root account because, honestly, who would
   put courses loose in the root account?) */
$subaccountsResponse = $api->get('accounts/1/sub_accounts', array('recursive' => 'true'));
$accounts = array();
do {
	foreach ($subaccountsResponse as $s) {
		if (array_key_exists('sis_account_id', $s)) {
			$accounts[$s['sis_account_id']] = $s['id'];
		}
	}
} while ($subaccountsResponse = $api->nextPage());

/* build a local cache of term id numbers, organized by SIS id */
$termsResponse = $api->get('accounts/1/terms', array('workflow_state[]' => 'all'));
$terms = array();
do {
	foreach ($termsResponse['enrollment_terms'] as $t) {
		if (array_key_exists('sis_term_id', $t)) {
			$terms[$t['sis_term_id']] = $t['id'];
		}
	}
} while ($termsResponse = $api->nextPage());

/* create and clone courses */
echo "id," . implode(",", $headers) . "\n";
$courses = array();
foreach ($clones as $c) {
	/* create the cloned course */
	$cc = $api->post("accounts/{$accounts[$c['account_id']]}/courses", array(
		'account_id' => $accounts[$c['account_id']],
		'course[sis_course_id]' => $c['course_id'],
		'course[name]' => $c['long_name'],
		'course[course_code]' => $c['short_name'],
		'course[term_id]' => $terms[$c['term_id']],
		'course[start_at]' => $c['start_date'],
		'course[end_at]' => $c['end_date']
	));
	
	/* duplicate course settings */
	if (!array_key_exists($c['template_id'], $courses)) {

		/* pull course settings as completely as possible */		
		$course = $api->get("courses/{$c['template_id']}/settings");
		$course = array_merge($course, $api->get("courses/{$c['template_id']}"));
		
		/* clear settings that are provided by the SIS import CSV */
		unset($course['id']);
		unset($course['sis_course_id']);
		unset($course['integration_id']);
		unset($course['name']);
		unset($course['course_code']);
		unset($course['account_id']);
		unset($course['enrollment_term_id']);
		unset($course['start_at']);
		unset($course['end_at']);
		unset($course['enrollments']);
		
		$courses[$c['template_id']]['course'] = $course;
	}
	$api->put("/courses/{$cc['id']}", $courses[$c['template_id']]);
	
	// TODO  nice to figure out navigation settings copy
	
	/* duplicate course content */
	$migration = $api->post("courses/{$cc['id']}/content_migrations", array(
		'migration_type' => 'course_copy_importer',
		'settings[source_course_id]' => $c['template_id']
	));
	
	echo "{$cc['id']},\"" . implode("\",\"", $c) . "\"\n";
}

?></pre>