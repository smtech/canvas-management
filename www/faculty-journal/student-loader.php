<?php

	require_once(__DIR__ . '/../config.inc.php');
	require_once(__DIR__ . '/.ignore.faculty-journal-authentication.inc.php');
	require_once(__DIR__ . '/config.inc.php');
	require_once(APP_PATH . '/include/canvas-api.inc.php');
	require_once(APP_PATH . '/include/mysql.inc.php');
	
	// TODO: it would be nice to cache generated pages, to save on API call time

 	header("Content-type: text/javascript");
	
	$menu = array();
	
	$acceptableCache = date('Y-m-d H:i:s',time() - CACHE_DURATION);
	$menuCacheResponse = mysqlQuery("
		SELECT * FROM `menus`
			WHERE
				`course_id` = '{$_REQUEST['course_id']}' AND
				`cached` > '$acceptableCache'
	");
	$menuCache = $menuCacheResponse->fetch_assoc();
	
	if ($menuCache) {
		$menu = unserialize($menuCache['menu']);
	} else {
		mysqlQuery("
			DELETE FROM `menus`
				WHERE
					`course_id` = '{$_REQUEST['course_id']}'
		");
	
		$sections = callCanvasApi(
			CANVAS_API_GET,
			"/courses/{$_REQUEST['course_id']}/sections"
		);
		
		
		foreach($sections as $section) {
			$enrollments = callCanvasApiPaginated(
				CANVAS_API_GET,
				"/sections/{$section['id']}/enrollments"
			);
			
			if ($enrollments) {
				$menu[$section['name']] = array();
				do {
					foreach ($enrollments as $enrollment) {
						if ($enrollment['role'] == 'StudentEnrollment') {
							$menu[$section['name']][$enrollment['user']['id']] = addslashes($enrollment['user']['sortable_name']);
						}
					}
				} while ($enrollments = callCanvasApiNextPage());
			}
		}
		mysqlQuery("
			INSERT INTO `menus`
			(
				`course_id`,
				`menu`
			)
			VALUES (
				'{$_REQUEST['course_id']}',
				'" . addslashes(serialize($menu)) . "'
			)
		");
	}

	$innerHTML = '<select class="vertical-center" id="menu" onChange="updateFacultyJournal()">';
	foreach ($menu as $section => $students) {
		$innerHTML .= '<optgroup label="' . $section . '">';
		foreach ($students as $id => $name) {
			$innerHTML .= '<option value="' . $id . '"' . ($id == $_REQUEST['user_id'] ? ' selected' : '') . '>' . $name . '</option>';
		}
	}
	$innerHTML .= '</select>';
?>

function loadStudents() {
	var menu = document.getElementById('menu-placeholder');
	menu.innerHTML = '<?= $innerHTML ?>';
}
