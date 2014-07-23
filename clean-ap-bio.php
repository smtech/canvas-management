<?php

define ('TOOL_NAME', "Courses in Term {$_REQUEST['enrollment_term_id']} with IDs");

require_once('config.inc.php');
require_once(__DIR__ . '../calendar-ics/.ignore.calendar-ics-authentication.inc.php'); // FIXME not kosher!

$files = callCanvasApiPaginated(
	CANVAS_API_GET,
	'/courses/1043/files',
	array(
		'search_term' => 'SR_Chapter'
	)
);

do {
	foreach ($files as $file) {
		echo "<p>moving {$file['display_name']}...</p>" . PHP_EOL;
		callCanvasApi(
			CANVAS_API_PUT,
			"/files/{$file['id']}",
			array(
				'parent_folder_id' => '5024'
			)
		);
	}
} while ($files = callCanvasApiNextPage());
echo '<h1>All done.</h1>';

?>