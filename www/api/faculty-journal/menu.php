<?php

require_once('.ignore.faculty-journal-authentication.inc.php');
require_once('config.inc.php');
require_once('../canvas-api.inc.php');
require_once('../mysql.inc.php');

// TODO: it would be nice to cache generated pages, to save on API call time

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
						$menu[$section['name']][$enrollment['user']['id']] = $enrollment['user']['sortable_name'];
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
			'" . serialize($menu) . "'
		)
	");
}


?>
<html>
<head>
	<script type="text/javascript">
		/*jslint browser: true, devel: true, eqeq: true, plusplus: true, sloppy: true, vars: true, white: true */

		function updateFacultyJournal() {
			var studentId = document.getElementById('menu').value;
			top.location.href = 'https://<?= parse_url(CANVAS_API_URL, PHP_URL_HOST) ?>/users/' + studentId + '/user_notes?course_id=<?= $_REQUEST['course_id'] ?>';
		}
		
		function nextStudent() {
			do {
				document.getElementById('menu').selectedIndex += 1;
				if (document.getElementById('menu').selectedIndex == document.getElementById('menu').length) {
					document.getElementById('menu').selectedIndex = 0;
				}
			} while (document.getElementById('menu').value == '');
			updateFacultyJournal();
		}
		
		function previousStudent() {
			do {
				document.getElementById('menu').selectedIndex -= 1;
				if (document.getElementById('menu').selectedIndex < 0) {
					document.getElementById('menu').selectedIndex = document.getElementById('menu').length - 1;
				}
			} while (document.getElementById('menu').value == '');
			updateFacultyJournal();
		}
	</script>
	<style>
	a {
		text-decoration: none;
	}
	a:link, a:visited {
		color: black;
	}
	
	#menu-box {
		position: absolute;
		top: 50%;
		height: 30px;
		width: 90%;
		margin-top: -15px;
		text-align: center;
	}
	</style>
</head>
<body>
	<div id="menu-box">
		<a href="javascript:previousStudent();">&#9664;</a></li>
		<select id="menu" onChange="updateFacultyJournal()">
			<option disabled value="">Choose a student</option>
			<?php
				foreach ($menu as $section => $students) {
					echo '<optgroup label="' . $section . '">';
					foreach ($students as $id => $name) {
						echo '<option value="' . $id . '"' . ($id == $_REQUEST['user_id'] ? ' selected' : '') . '>' . $name . '</option>';
					}
				}
			?>
		</select>
		<a href="javascript:nextStudent();">&#9654;</a>
	</div>
</ul>
</body>
</html>