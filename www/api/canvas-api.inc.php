<?php

/* we use Pest to interact with the RESTful API */
require_once('Pest.php');

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
function callCanvasApi($verb, $url, $data) {
	global $PEST;
	
	$json = null;

	do {
		$retry = false;
		try {
			$json = $PEST->$verb($url, $data, buildCanvasAuthorizationHeader());
		} catch (Pest_ServerError $e) {
			/* who knows what goes on in the server's mind... try again */
			$retry = true;
		} catch (Exception $e) {
			exitOnError('API Error',
				array(
					'Something went awry in the API.',
					$e->getMessage(),
					"<dl><dt>Verb</dt><dd>$verb</dd>" .
					"<dt>URL</dt><dd>$url</dd>" .
					'<dt>Data</dt>' .
					'<dd><pre>' . print_r($data, true) . '</pre></dd></dl>'
				)
			);
		}
	} while ($retry == true);
	
	return $json;
}

/**
 * Echo a page of HTML content to the browser, wrapped in some CSS niceities
 **/
function displayPage($content) {
	echo '<html>
<head>
	<title>' . TOOL_NAME . '</title>
	<link rel="stylesheet" href="script-ui.css" />
</head>
<body>
<h1>' . TOOL_NAME . '</h1>
<h2>St. Mark&rsquo;s School</h2>
<div id="header">
	<a href="' . TOOL_START_PAGE . '">Start Over</a>
</div>
<div id="content">
'. $content . '
</div>
<div id="footer">
	<a href="http://www.stmarksschool.org">St. Mark&rsquo;s School</a> &bull; <a href="http://area51.stmarksschoo.org">Academic Technology</a> &bull; 25 Marlboro Road, Southborough, MA 01772
</div>
</body>
</html>';
}

?>