<?php

require_once('debug.inc.php');

if(!defined('API_CLIENT_ERROR_RETRIES')) {
	define('API_CLIENT_ERROR_RETRIES', 5);
	debug_log('Using default API_CLIENT_ERROR_RETRIES = ' . API_CLIENT_ERROR_RETRIES, DEBUGGING_INFORMATION);
}
if(!defined('API_SERVER_ERROR_RETRIES')) {
	define('API_SERVER_ERROR_RETRIES', API_CLIENT_ERROR_RETRIES * 5);
	debug_log('Using default API_SERVER_ERROR_RETRIES = ' . API_SERVER_ERROR_RETRIES, DEBUGGING_INFORMATION);
}
if(!defined('DEBUGGING')) {
	define('DEBUGGING', DEBUGGING_LOG);
}

/* the verbs available within the Canvas REST API */
define('CANVAS_API_DELETE', 'delete');
define('CANVAS_API_GET', 'get');
define('CANVAS_API_POST', 'post');
define('CANVAS_API_PUT', 'put');

/* lots of scripts need this format */
define('CANVAS_TIMESTAMP_FORMAT', 'Y-m-d\TH:iP');

/* how long the exception excerpt should be in the log file */
if (!defined('CANVAS_API_EXCEPTION_MAX_LENGTH')) {
	define('CANVAS_API_EXCEPTION_MAX_LENGTH', 50);
	debug_log('Using default CANVAS_API_EXCEPTION_MAX_LENGTH = ' . CANVAS_API_EXCEPTION_MAX_LENGTH, DEBUGGING_INFORMATION);
}

/* we're trying to obscure the workings of the actual API safely in this one
   file, so that everything gets handled consistently, but reasonable minds
   may disagree and wish to catch some of their own exceptions...*/
define('CANVAS_API_EXCEPTION_NONE', 0);
define('CANVAS_API_EXCEPTION_CLIENT', 1);
define('CANVAS_API_EXCEPTION_SERVER', 2);
// TODO: we could extend this to include more specificity about the Pest exceptions...

/* we use Pest to interact with the RESTful API */
require_once('Pest.php');

/* handles HTML page generation */
require_once('page-generator.inc.php');

/**
 * Generate the Canvas API authorization header
 **/
function buildCanvasAuthorizationHeader() {
	return array ('Authorization' => 'Bearer ' . CANVAS_API_TOKEN);
}

/**
 * Pass-through a Pest request with added Canvas authorization token
 **/
$PEST = new Pest(CANVAS_API_URL);
function callCanvasApi($verb, $url, $data = array(), $throwingExceptions = CANVAS_API_EXCEPTION_NONE) {
	global $PEST;
	$response = null;

	$clientRetryCount = 0;
	$serverRetryCount = 0;
	do {
		$retry = false;
		try {
			$response = $PEST->$verb($url, $data, buildCanvasAuthorizationHeader());
		} catch (Pest_ServerError $e) {
			if ($throwingExceptions & CANVAS_API_EXCEPTION_SERVER) {
				throw $e;
			} else {
				/* who knows what goes on in the server's mind... try again */
				$serverRetryCount++;
				$retry = true;
				debug_log('Retrying after Canvas API server error. ' . preg_replace('%(.{0,' . CANVAS_API_EXCEPTION_MAX_LENGTH . '}.+)%', '\\1...', $e->getMessage()));
			}
		} catch (Pest_ClientError $e) {
			if ($throwingExceptions & CANVAS_API_EXCEPTION_CLIENT) {
				throw $e;
			} else {
				/* I just watched the Canvas API throw an unauthorized error when, in fact,
				   I was authorized. Everything gets retried a few times before I give up */
				$clientRetryCount++;
				$retry = true;
				debug_log('Retrying after Canvas API client error. ' . preg_replace('%(.{0,' . CANVAS_API_EXCEPTION_MAX_LENGTH . '}.+)%', '\\1...', $e->getMessage())); 
			}
		} catch (Exception $e) {
			displayError(
				array(
					'Error' => $e->getMessage(),
					'Verb' => $verb,
					'URL' => $url,
					'Data' => $data
				),
				true,
				'API Error',
				'Something went awry in the API'
			);
			exit;
		}
	} while ($retry == true && $clientRetryCount < API_CLIENT_ERROR_RETRIES && $serverRetryCount < API_SERVER_ERROR_RETRIES);
	
	if ($clientRetryCount == API_CLIENT_ERROR_RETRIES) {
		displayError(
			array(
				'Status' => $PEST->lastStatus(),
				'Error' => $PEST->lastBody(),
				'Verb' => $verb,
				'URL' => $url,
				'Data' => $data
			),
			true,
			'Probable Client Error',
			'After trying ' . API_CLIENT_ERROR_RETRIES . ' times, we still got this error message from the API. (Remember to check to be sure that the object ID passed to the API is valid and exists if the API tells you that you\'re not authorized... because you\'re not authorized to work with things that don\'t exist!)'
		);
		exit;
	}
	
	if ($serverRetryCount == API_SERVER_ERROR_RETRIES) {
		displayError(
			array(
				'Status' => $PEST->lastStatus(),
				'Error' => $PEST->lastBody(),
				'Verb' => $verb,
				'URL' => $url,
				'Data' => $data
			),
			true,
			'Probable Server Error',
			'After trying ' . API_CLIENT_ERROR_RETRIES . ' times, we still got this error message from the API.'
		);
		exit;
	}
	
	$responseArray = json_decode($response, true);
	
	displayError(
		array(
			'API Call' => array(
				'Verb' => $verb,
				'URL' => $url,
				'Data' => $data
			),
			'Response' => $responseArray
		),
		true,
		'API Call',
		null,
		DEBUGGING_CANVAS_API
	);
	
	return $responseArray;
}

?>