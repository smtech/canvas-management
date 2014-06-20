<?php

define('TOOL_NAME', 'Scripts');

require_once('config.inc.php');
require_once(APP_PATH . '/include/page-generator.inc.php');

$termsApi = new CanvasApiProcess(CANVAS_API_URL, CANVAS_API_TOKEN);
$terms = $termsApi->get('/accounts/1/terms');
$termOptions = array();
do {
	foreach($terms['enrollment_terms'] as $term) {
		$termOptions[] = "<option value=\"{$term['id']}\">{$term['name']}</option>";
	}
} while($terms = $termsApi->nextPage());

displayPage('
	<dl>
	
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
		
		<dt>Transfer Outcomes</dt>
			<dd><form action="transfer-outcomes.php">
				<div>source_url <input name="source_url" type="text" /></div>
				<div>destination_url <input name="destination_url" type="text" /></div>
				<input type="submit" value="Transfer" />
			</dd>
	</dl>
');

?>