<?php

define('TOOL_NAME', 'Blackboard 8 &rarr; Canvas Import Tool'); // what the tool calls itself
define('TOOL_START_PAGE', $_SERVER['PHP_SELF']); // the start page (and where Start Over links back to

define('UPLOAD_DIR', '/var/www-data/canvas/blackboard-import'); // where we'll store uploaded files
define('WORKING_DIR', buildPath(UPLOAD_DIR, 'tmp')); // where we'll be dumping temp files (and cleaning up, too!)

define('UPLOAD_STAGING_DIR', buildPath(dirname(__FILE__), 'upload-staging')); // directory of the publicly accessible directory files will be staged in to transfer to Canvas
define('UPLOAD_STAGING_URL', 'http://' . buildPath($_SERVER['SERVER_NAME'] . dirname($_SERVER['REQUEST_URI']), 'upload-staging') . '/'); // URL to the upload staging directory from the web

define('API_CLIENT_ERROR_RETRIES', 2); // how many times to retry requests for which we got client errors that we don't entirely believe

define('FILE_ATTACHMENT_PREFIX', '<h3>Attached Files</h3><blockquote>');
define('FILE_ATTACHMENT_SUFFIX', '</blockquote>');

define('CONTENT_LINK_PREFIX', '<h3>Linked Content</h3><blockquote>');
define('CONTENT_LINK_SUFFIX', '</blockquote>');

define('BREADCRUMB_SEPARATOR', ' > '); // when creating a breadcrumb trail in the names of subitems

define('CANVAS_Bb_IMPORT_ACCOUNT_ID', 167); // the default account in which to create new courses
define('CANVAS_DEFAULT_PATH', 'Imported from Blackboard'); // the folder that most files will be imported into
define('CANVAS_CONTENT_COLLECTION_PATH', buildPath(CANVAS_DEFAULT_PATH, 'Content Collection')); // the folder that the content collection will be imported into

define('RECEIPT_FILE_NAME', 'Import Receipt.xml'); // the name of the generated and uploaded receipt
define('CANVAS_NAMESPACE_URI', buildPath($_SERVER['SERVER_NAME'], dirname($_SERVER['REQUEST_URI']))); // use the URL of this script as the URI for the namespace
define('CANVAS_NAMESPACE_PREFIX', 'canvas');

?>