<?php

require_once('config.inc.php');
define('TOOL_NAME', 'Forgot your password?');
define('TOOL_START_LINK', 'Return to Canvas Login');
define('TOOL_START_PAGE', SCHOOL_CANVAS_INSTANCE);
require_once(APP_PATH . '/include/page-generator.inc.php');

displayPage('

<dl>
	<dt>Are you a St. Mark&rsquo;s student or teacher?</dt>
		<dd>Please log in with your whole St. Mark&rsquo; email address and password. If you have forgotten your password, please contact the Help Desk at x4001 (students may also visit the Dean of Students&rsquo; office for password lookups).</dd>
		
	<dt>Are you non-teaching St. Mark&rsquo;s faculty or staff?</dt>
		<dd>If you would like access to Canvas, please contact <a href="mailto:CanvasHelp@stmarksschool.org?subject=Canvas%20Access,%20please!">CanvasHelp@stmarksschool.org</a> from your school email address and we&rsquo;ll get you set up!</dd>
		
	<dt>Are you a student in an online St. Mark&rsquo;s course?</dt>
		<dd>Please contact <a href="mailto:CanvasHelp@stmarksschool.org?subject=Canvas%20Password%20Problem">CanvasHelp@stmarksschool.org</a> from the email address that you registered with us for assistance. (If you can&rsquo;t remember which email address you used, please be sure to include your full name and a good explanation of what&rsquo;s going on so we can help you figure it out!)</dd>
</dl>
');

?>