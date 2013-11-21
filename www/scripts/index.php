<?php

require_once('config.inc.php');
require_once(APP_PATH . '/include/page-generator.inc.php');

displayPage('
	<dl>
	
		<dt>List Teachers of Courses</dt>
			<dd><form action="list-teachers-of-courses.php">
				enrollment_term <input type="text" name="enrollment_term" />
			</form></dd>
	
	</dl>
');

?>