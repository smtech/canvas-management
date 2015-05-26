<?php

define('TOOL_NAME', 'Scripts');

define('CACHE_DURATION', 7/*days*/ * 24/*hours*/ * 60/*min*/ * 60/*sec*/);

require_once('.ignore.live-authentication.inc.php');
require_once('config.inc.php');
require_once(SMCANVASLIB_PATH . '/include/page-generator.inc.php');

$termOptions = getCache('key', 'index-terms','data');
if (!$termOptions) {
	$termsApi = new CanvasApiProcess(CANVAS_API_URL, CANVAS_API_TOKEN);
	$terms = $termsApi->get('/accounts/1/terms');
	$termOptions = array();
	do {
		foreach($terms['enrollment_terms'] as $term) {
			$termOptions[] = "<option value=\"{$term['id']}\">{$term['name']}</option>";
		}
	} while($terms = $termsApi->nextPage());
	setCache('key', 'index-terms', 'data', $termOptions);
}

displayPage('
	<dl>
	
		<dt>Advisors as Observers</dt>
			<dd>This script requires that the "Users can delete their institution-assigned email address" setting be enabled in Canvas.</dd>
			<dd><form action="advisors-as-observers.php">
				<div>enrollment_term_id <select name="enrollment_term_id" />' . implode($termOptions) . '</select></div>
				<div>reset_passwords <input name="reset_passwords" type="checkbox" value="yes" unchecked /></div>
				<input type="submit" value="Create/Reset" />
			</form></dd>
			<dd><form action="turn-off-advisor-notifications.php">
				Turn off notifications for all advisor accounts. <input type="submit" value="Silence" />
			</form></dd>
			
		<dt>Publish Current Advisory Courses</dt>
			<dd><form action="publish-current-advisory-courses.php" /><input type="submit" value="Publish" /></form></dd>

		<dt><a href="assignments-due-on-a-day.php">Assignments due on a day</a></dt>
		
		<dt>Courses in Term with ID</dt>
			<dd><form action="courses-in-term-with-id.php">
				enrollment_term_id <select name="enrollment_term_id" />' . implode($termOptions) . '</select>
				<input type="submit" value="List" />
			</form></dd>
			
		<dt>Courses with a single assignment group</dt>
			<dd><form action="courses-with-a-single-assignment-group.php">
				enrollment_term_id <select name="enrollment_term_id" />' . implode($termOptions) . '</select>
				<input type="submit" value="List" />
			</form></dd>
			
		<dt><a href="generate-course-and-section-sis_id.php">Generate course and section SIS Id</a></dt>
	
		<dt>List teachers of courses</dt>
			<dd><form action="list-teachers-of-courses.php">
				enrollment_term_id <select name="enrollment_term_id" />' . implode($termOptions) . '</select>
				<input type="submit" value="List" />
			</form></dd>
			
		<dt>List users enrolled in term</dt>
			<dd><form action="list-users-enrolled-in-term.php">
				enrollment_term_id <select name="enrollment_term_id" />' . implode($termOptions) . '</select>
				<input type="submit" value="List" />
			</form></dd>
	
		<dt><a href="list-users-with-non-blackbaud-sis_id.php">List users with non-Blackbaud SIS ID</a></dt>
		
		<dt><a href="list-users-without-sis_id.php">List users with SIS ID</a></dt>
		
		<dt><a href="students-as-teachers-audit.php">Students as teachers audit</a></dt>
		
		<dt>2014 Summer Course Merge</dt>
			<dd><form action="summer-course-merge.php" enctype="multipart/form-data" method="post">
				<div>merge_csv <input id="csvId" name="merge_csv" type="file" /></div>
				<input type="submit" value="Merge" />
			</form></dd>

		<dt>Transfer Outcomes</dt>
			<dd><form action="transfer-outcomes.php">
				<div>source_url <input name="source_url" type="text" /></div>
				<div>destination_url <input name="destination_url" type="text" /></div>
				<input type="submit" value="Transfer" />
			</form></dd>			
	</dl>
');

?>