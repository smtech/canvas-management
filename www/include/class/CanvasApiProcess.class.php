<?php

require_once(__DIR__ . '/../../config.inc.php');
require_once(APP_PATH . '/include/debug.inc.php');

if(!defined('API_CLIENT_ERROR_RETRIES')) {
	define('API_CLIENT_ERROR_RETRIES', 5);
	debug_log('Using default API_CLIENT_ERROR_RETRIES = ' . API_CLIENT_ERROR_RETRIES);
}
if(!defined('API_SERVER_ERROR_RETRIES')) {
	define('API_SERVER_ERROR_RETRIES', API_CLIENT_ERROR_RETRIES * 5);
	debug_log('Using default API_SERVER_ERROR_RETRIES = ' . API_SERVER_ERROR_RETRIES);
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
}

/* we're trying to obscure the workings of the actual API safely in this one
   file, so that everything gets handled consistently, but reasonable minds
   may disagree and wish to catch some of their own exceptions...*/
define('CANVAS_API_EXCEPTION_NONE', 0);
define('CANVAS_API_EXCEPTION_CLIENT', 1);
define('CANVAS_API_EXCEPTION_SERVER', 2);
// TODO: we could extend this to include more specificity about the Pest exceptions...

/* we use Pest to interact with the RESTful API */
require_once(APP_PATH . '/include/Pest.php');

/* handles HTML page generation */
require_once(APP_PATH . '/include/page-generator.inc.php');

class CanvasApiProcess {
	
	/**
	 * URL of the Canvas instance API endpoint
	 * @var str
	 */
	private $instanceUrl;
	
	/**
	 * Authorization token to access Canvas instance API
	 * @var str
	 */
	private $authorizationToken;
	
	/**
	 * Pest object to perform actual RESTful API actions
	 * @var Pest
	 */
	private $pest;
	
	/**
	 * Information about the last call made to the Canvas API through this object
	 * @var array
	 */
	private $lastCall = array(
		'verb' => null,
		'path' => null,
		'data' => array(),
		'throwsExceptions' => false,
		'response' => null,
		'pagination' => array()
	);

	/**
	 * Constructor
	 * @param str $apiInstanceUrl URL of the Canvas instance API endpoint
	 * @param str $apiAuthenticationToken Authentication token to grant access to the API of this Canvas instance
	 */
	public function __construct($apiInstanceUrl, $apiAuthenticationToken) {
		$this->instanceUrl = $apiInstanceUrl;
		$this->authorizationToken = $apiAuthenticationToken;
		$this->pest = new Pest($this->instanceUrl);
	}
	
	private function buildCanvasAuthorizationHeader() {
		return array ('Authorization' => 'Bearer ' . $this->authorizationToken);
	}

	
	private function processPaginationLinks() {
		$this->lastCall['pagination'] = array();
		preg_match_all('%<([^>]*)>\s*;\s*rel="([^"]+)"%', $this->pagePest->lastHeader('link'), $links, PREG_SET_ORDER);
		foreach ($links as $link)
		{
			$this->lastCall['pagination'][$link[2]] = $link[1];
		}
	}
	
	/**
	 * Perform a RESTful API call
	 * @param str $verb The RESTful verb (use constants: CANVAS_API_DELETE, CANVAS_API_GET, CANVAS_API_POST, CANVAS_API_PUT)
	 * @param str $path Path to the particular RESTful command
	 * @param array $data Associative array of parameters to for the RESTful command
	 * @param bool $throwsExceptions = false Catch or throw Pest exceptions (sometimes useful to catch them elsewhere...)
	 * @param bool $paginated = false Internal use only
	 */
	public function call($verb, $path, $data = array(), $throwsExceptions = false, $paginated = false) {
		$response = null;
	
		$clientRetryCount = 0;
		$serverRetryCount = 0;
		do {
			$retry = false;
			try {
				if ($paginated) {
					$response = $this->pagePest->$verb($path, $data, $this->buildCanvasAuthorizationHeader());					
				} else {
					$this->pagePest = $this->pest;
					$this->lastCall['verb'] = $verb;
					$this->lastCall['path'] = $path;
					$this->lastCall['data'] = $data;
					$this->lastCall['throwsExceptions'] = $throwsExceptions;
					$response = $this->pest->$verb($path, $data, $this->buildCanvasAuthorizationHeader());
				}
			} catch (Pest_ServerError $e) {
				if ($throwsExceptions & CANVAS_API_EXCEPTION_SERVER) {
					throw $e;
				} else {
					/* who knows what goes on in the server's mind... try again */
					$serverRetryCount++;
					$retry = true;
					debug_log('Retrying after Canvas API server error. ' . substr($e->getMessage(), 0, CANVAS_API_EXCEPTION_MAX_LENGTH));
				}
			} catch (Pest_ClientError $e) {
				if ($throwsExceptions & CANVAS_API_EXCEPTION_CLIENT) {
					throw $e;
				} else {
					/* I just watched the Canvas API throw an unauthorized error when, in fact,
					   I was authorized. Everything gets retried a few times before I give up */
					$clientRetryCount++;
					$retry = true;
					debug_log('Retrying after Canvas API client error. ' . substr($e->getMessage(), 0, CANVAS_API_EXCEPTION_MAX_LENGTH)); 
				}
			} catch (Exception $e) {
				// treat an empty reply as a server error (which, BTW, it dang well is)
				if ($e->getMessage() == 'Empty reply from server') {
					$serverRetryCount++;
					$retry = true;
					debug_log('Retrying after empty reply from server. ' . substr($e->getMessage(), 0, CANVAS_API_EXCEPTION_MAX_LENGTH));
				} else {
					displayError(
						array(
							'Error' => $e->getMessage(),
							'Verb' => $verb,
							'Path' => $path,
							'Data' => $data
						),
						true,
						'API Error',
						'Something went awry in the API'
					);
					exit;
				}
			}
		} while ($retry == true && $clientRetryCount < API_CLIENT_ERROR_RETRIES && $serverRetryCount < API_SERVER_ERROR_RETRIES);
		
		if ($clientRetryCount == API_CLIENT_ERROR_RETRIES) {
			displayError(
				array(
					'Status' => $this->pest->lastStatus(),
					'Error' => $this->pest->lastBody(),
					'Verb' => $verb,
					'Path' => $path,
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
					'Status' => $this->pest->lastStatus(),
					'Error' => $this->pest->lastBody(),
					'Verb' => $verb,
					'Path' => $path,
					'Data' => $data
				),
				true,
				'Probable Server Error',
				'After trying ' . API_CLIENT_ERROR_RETRIES . ' times, we still got this error message from the API.'
			);
			exit;
		}
		
		$this->lastCall['response'] = json_decode($response, true);
		$this->processPaginationLinks();
		
		displayError(
			array(
				'API Call' => array(
					'Verb' => $verb,
					'Path' => $path,
					'Data' => $data
				),
				'Response' => $this->lastCall['response']
			),
			true,
			'API Call',
			null,
			DEBUGGING_CANVAS_API
		);
		
		return $this->lastCall['response'];
	}
	
	public function delete($path, $data = array(), $throwsExceptions = false) {
		return $this->call(CANVAS_API_DELETE, $path, $data, $throwsExceptions);
	}

	public function get($path, $data = array(), $throwsExceptions = false) {
		return $this->call(CANVAS_API_GET, $path, $data, $throwsExceptions);
	}

	public function post($path, $data = array(), $throwsExceptions = false) {
		return $this->call(CANVAS_API_POST, $path, $data, $throwsExceptions);
	}

	public function put($path, $data = array(), $throwsExceptions = false) {
		return $this->call(CANVAS_API_PUT, $path, $data, $throwsExceptions);
	}
	
	private function pageLink($page) {
		if (array_key_exists($page, $this->lastCall['pagination'])) {
			$this->pagePest = new Pest($this->lastCall['pagination'][$page]);
			return $this->call(CANVAS_API_GET, '', '', $this->lastCall['throwsExceptions'], true);
		}
		return false;
	}
	
	public function nextPage() {
		return $this->pageLink('next');
	}
	
	public function prevPage() {
		return $this->pageLink('prev');
	}
	
	public function firstPage() {
		return $this->pageLink('first');
	}
	
	public function lastPage() {
		return $this->pageLink('last');
	}
	
	private function getPageNumber($page) {
		if (array_key_exists($page, $this->lastCall['pagination'])) {
			parse_str(parse_url($this->lastCall['pagination'][$page], PHP_URL_QUERY), $query);
			return $query['page'];
		}
		return -1;
	}
	
	public function getNextPageNumber() {
		return $this->getPageNumber('next');
	}
	
	public function getPrevPageNumber() {
		return $this->getPageNumber('prev');
	}
	
	public function getFirstPageNumber() {
		return $this->getPageNumber('first');
	}
	
	public function getLastPageNumber() {
		return $this->getPageNumber('last');
	}
	
	public function getCurrentPageNumber() {
		$next = $this->getNextPageNumber();
		if ($next > -1) {
			return $next - 1;
		} else {
			$prev = $this->getPrevPageNumber();
			if ($prev > -1) {
				return $prev + 1;
			} else {
				return $this->getFirstPageNumber();
			}
		}
	}

}

?>