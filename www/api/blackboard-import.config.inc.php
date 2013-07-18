<?php

define('TOOL_NAME', 'Blackboard 8 &rarr; Canvas Import Tool');
define('UPLOAD_DIR', '/var/www-data/canvas/blackboard-import'); // where we'll store uploaded files
define('WORKING_DIR', buildPath(UPLOAD_DIR, 'tmp')); // where we'll be dumping temp files (and cleaning up, too!)
define('UPLOAD_STAGING_DIR', buildPath(dirname(__FILE__), 'upload-staging'));


/* configurable... but why? */
define('TOOL_START_PAGE', $_SERVER['PHP_SELF']);
define('UPLOAD_STAGING_URL', 'http://' . buildPath($_SERVER['SERVER_NAME'] . dirname($_SERVER['REQUEST_URI']), 'upload-staging') . '/');
define('BREADCRUMB_SEPARATOR', ' > '); // when creating a breadcrumb trail in the names of subitems
define('CANVAS_Bb_IMPORT_ACCOUNT_ID', 167); // the default account in which to create new courses
define('CANVAS_DEFAULT_PATH', 'Imported from Blackboard'); // the folder that most files will be imported into
define('CANVAS_CONTENT_COLLECTION_PATH', buildPath(CANVAS_DEFAULT_PATH, 'Content Collection'));
define('RECEIPT_FILE_NAME', 'Import Receipt.xml'); // the name of the generated and uploaded receipt
define('WAIT_FOR_STATUS_UPDATE', 1);

?>