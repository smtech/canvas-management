<?php

if(!defined('API_CLIENT_ERROR_RETRIES')) {
	define('API_CLIENT_ERROR_RETRIES', 5);
}
if(!defined('API_SERVER_ERROR_RETRIES')) {
	define('API_SERVER_ERROR_RETRIES', API_CLIENT_ERROR_RETRIES * 5);
}
if (!defined('DEBUGGING')) {
	define('DEBUGGING', false);
}

/* we use Pest to interact with the RESTful API */
require_once('PestCanvas.php');

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
$PEST = new PestCanvas(CANVAS_API_URL);
function callCanvasApi($verb, $url, $data = array()) {
	global $PEST;
	
	$json = null;

	$clientRetryCount = 0;
	$serverRetryCount = 0;
	do {
		$retry = false;
		try {
			$json = $PEST->$verb($url, $data, buildCanvasAuthorizationHeader());
		} catch (Pest_ServerError $e) {
			/* who knows what goes on in the server's mind... try again */
			$serverRetryCount++;
			$retry = true;
			debug_log('Retrying after Canvas API server error. ' . $e->getMessage());
		} catch (Pest_ClientError $e) {
			/* I just watched the Canvas API throw an unauthorized error when, in fact,
			   I was authorized. Everything gets retried a few times before I give up */
			$clientRetryCount++;
			$retry = true;
			debug_log('Retrying after Canvas API client error. ' . $e->getMessage()); 
		} catch (Exception $e) {
			displayError(array(
				'Error' => $e->getMessage(),
				'Verb' => $verb,
				'URL' => $url,
				'Data' => $data
			), true, 'API Error', 'Something went awry in the API');
			exit;
		}
	} while ($retry == true && $clientRetryCount < API_CLIENT_ERROR_RETRIES && $serverRetryCount < API_SERVER_ERROR_RETRIES);
	
	if ($clientRetryCount == API_CLIENT_ERROR_RETRIES) {
		displayError(array(
			'Status' => $PEST->lastStatus(),
			'Error' => $PEST->lastBody(),
			'Verb' => $verb,
			'URL' => $url,
			'Data' => $data
		), true, 'Probable Client Error', 'After trying ' . API_CLIENT_ERROR_RETRIES . ' times, we still got this error message from the API.');
		exit;
	}
	
	return $json;
}

/**
 * Helper function to conditionally fill the log file with notes!
 **/
function debug_log($message) {
	if (DEBUGGING) {
		error_log($message);
	}
}

?>