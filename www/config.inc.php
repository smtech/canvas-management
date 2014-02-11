<?php

/* this will break if there are mod_rewrites, but will do for now... */
define('APP_URL', 'http://' . $_SERVER['SERVER_NAME'] . str_replace(realpath($_SERVER['DOCUMENT_ROOT']), '', realpath(__DIR__)));
define('APP_PATH', realpath(__DIR__));

/* customize generated pages */
define('SCHOOL_NAME', 'St. Mark&rsquo;s School');
define('SCHOOL_URL', 'http://www.stmarksschool.org');
define('SCHOOL_CANVAS_INSTANCE', 'https://stmarksschool.instructure.com');
define('SCHOOL_DEPT', 'Academic Technology');
define('SCHOOL_DEPT_URL', 'http://area51.stmarksschool.org');
define('SCHOOL_ADDRESS', '25 Marlboro Road, Southborough, Massachusetts, 01772');
define('SCHOOL_COLOR_LIGHT', 'white'); // masthead foreground
define('SCHOOL_COLOR_DARK', '#003359'); // masthead background and link colors

?>