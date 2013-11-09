<?php

require_once(__DIR__ . '/../config.inc.php');
require_once(APP_PATH . '/include/debug.inc.php');

if (!defined('WORKING_DIR')) {
	define('WORKING_DIR', '/var/www-data/canvas/tmp/');
	debug_log('Using default WORKING_DIR = "' . WORKING_DIR . '"');
}

/**
 * Delete all the files from a directory
 **/
function flushDir($dir) {
	$files = glob("$dir/*");
	foreach($files as $file) {
		if (is_dir($file)) {
			flushDir($file);
			rmdir($file);
		} elseif (is_file($file)) {
			unlink($file);
		}
	}
	
	$hiddenFiles = glob("$dir/.*");
	foreach($hiddenFiles as $hiddenFile)
	{
		if (is_file($hiddenFile)) {
			unlink($hiddenFile);
		}
	}
}

/**
 * build a file path out of component directories and filenames
 **/
function buildPath() {
	$args = func_get_args();
	$path = '';
	foreach ($args as $arg) {
		if (strlen($path)) {
			$path .= "/$arg";
		} else {
			$path = $arg;
		}
	}

	/* DO NOT USE realpath() -- it hoses everything! */
	$path = str_replace('//', '/', $path);
	
	/* 'escape' the squirrely-ness of Bb's pseudo-windows paths-in-filenames */
	$path = preg_replace("|(^\\\\]\\\\)([^\\\\])|", '\\1\\\2', $path);
	
	return $path;
}

/**
 * Allow for session-based temp directories
 **/
$WORKING_DIR_FIRST_RUN = true;
$SESSION_WORKING_DIR = null;
function getWorkingDir()
{
	if ($GLOBALS['WORKING_DIR_FIRST_RUN']) {
		$GLOBALS['SESSION_WORKING_DIR'] = buildPath(WORKING_DIR, md5($_SERVER['REMOTE_ADDR'] . time()));
		mkdir($GLOBALS['SESSION_WORKING_DIR']);
		$GLOBALS['WORKING_DIR_FIRST_RUN'] = false;
	}
	return buildPath($GLOBALS['SESSION_WORKING_DIR']);
}

?>