<?php

require_once(__DIR__ . '/../config.inc.php');

define('TOOL_NAME', 'Blackboard 8 &rarr; Canvas Import Tool'); // what the tool calls itself

define('UPLOAD_DIR', '/var/www-data/canvas/blackboard-import'); // where we'll store uploaded files
define('WORKING_DIR', UPLOAD_DIR . '/tmp'); // where we'll be dumping temp files (and cleaning up, too!)

require_once(APP_PATH . '/include/working-directory.inc.php');

define('UPLOAD_STAGING_DIR', buildPath(__DIR__, 'upload-staging')); // directory of the publicly accessible directory files will be staged in to transfer to Canvas
define('UPLOAD_STAGING_URL', 'http://' . buildPath(APP_URL, 'upload-staging') . '/'); // URL to the upload staging directory from the web

define('FILE_ATTACHMENT_PREFIX', '<h3>Attached Files</h3><blockquote>');
define('FILE_ATTACHMENT_SUFFIX', '</blockquote>');

define('CONTENT_LINK_PREFIX', '<h3>Linked Content</h3><blockquote>');
define('CONTENT_LINK_SUFFIX', '</blockquote>');

define('BREADCUMB_SEPARATOR', ' > '); // when creating a breadcrumb trail in the names of subitems

define('CANVAS_Bb_IMPORT_ACCOUNT_ID', 135); // the default account in which to create new courses
define('CANVAS_DEFAULT_PATH', 'Imported from Blackboard'); // the folder that most files will be imported into
define('CANVAS_CONTENT_COLLECTION_PATH', buildPath(CANVAS_DEFAULT_PATH, 'Content Collection')); // the folder that the content collection will be imported into
define('CANVAS_IMPORT_INFO_DIR', buildPath(CANVAS_DEFAULT_PATH, 'Import Information')); // where information about the import is stored in Canvas

define('CANVAS_IMPORT_RECEIPT_FILENAME', 'Import Receipt.xml'); // the name of the generated and uploaded receipt
define('CANVAS_NAMESPACE_URI', buildPath($_SERVER['SERVER_NAME'], dirname($_SERVER['REQUEST_URI']))); // use the URL of this script as the URI for the namespace
define('CANVAS_NAMESPACE_PREFIX', 'canvas');

?>