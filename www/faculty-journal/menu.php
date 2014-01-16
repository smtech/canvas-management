<?php

require_once(__DIR__ . '/../config.inc.php');
require_once(__DIR__ . '/.ignore.faculty-journal-authentication.inc.php');
require_once(__DIR__ . '/config.inc.php');
require_once(APP_PATH . '/include/canvas-api.inc.php');

?>
<html>
<head>
	<meta http-equiv="Pragma" content="no-cache" />
	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
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
				
		$.getScript(
			'https://<?= $_SERVER['SERVER_NAME'] . dirname($_SERVER['PHP_SELF']) ?>/student-loader.php?course_id=<?= $_REQUEST['course_id'] ?>&user_id=<?= $_REQUEST['user_id'] ?>',
			function() {
				loadStudents();
			}
		);

	</script>
	<style>
	a {
		text-decoration: none;
	}
	a:link, a:visited {
		color: black;
	}
	
	table {
		margin: auto;
	}
	
	tr {
		vertical-align: middle;
	}
	
	#menu-box {
		position: absolute;
		top: 50%;
		height: 30px;
		width: 90%;
		margin-top: -15px;
		text-align: center;
	}
	
	#menu-placeholder {
		display: inline;
		width: auto;
	}
	</style>
</head>
<body>
<div id="menu-box">
	<table>
		<tr>
			<td><a href="javascript:previousStudent();">&#9664;</a></li></td>
			<td id="menu-placeholder">
				<table>
					<tr>
						<td>
							<select disabled>
								<option>Loading students...</option>
							</select>
						</td>
						<td><img src="ajax-loader.gif" height="16" width="16" /></td>
					</tr>
				</table>
			</td>
			<td><a href="javascript:nextStudent();">&#9654;</a></td>
		</tr>
	</table>
</div>
</body>
</html>