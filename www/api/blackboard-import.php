<?php

/*
	The basic workflow:

	1. Upload ZIP archive (DONE)
	2. Unzip ZIP archive and extract course name, teacher, etc. (DONE)
	3. Create (or link to existing) Canvas course
	4. Upload items via API
		a. csfiles/ contents (build a list referenced by x-id and Canvas ID for
		   future reference)
		b. walk through resources in manifest and post all attachments (build a
		   list referenced by res00000, x-id if available, filename and Canvas ID)
		c. post items and then append them to appropriate modules
		d. perhaps create a nicey-nice front page that lists all the things in the
		   manifest TOC?
	5. Open Canvas course
	6. Clean out files (when Canvas API calls complete)
*/


/***********************************************************************
 *                                                                     *
 * Requirments & includes                                              *
 *                                                                     *
 ***********************************************************************/
 
/* REQUIRES the PHP 5 XSL extension
   http://www.php.net/manual/en/xsl.installation.php */

/* defines CANVAS_API_TOKEN, CANVAS_API_URL */
require_once('.ignore.canvas-authentication.php');

/* handles the core of the RESTful API interactions */
require_once('Pest.php');


/***********************************************************************
 *                                                                     *
 * Globals & Constants                                                 *
 *                                                                     *
 ***********************************************************************/

/* configurable... but why? */
define('UPLOAD_DIR', '/var/www-data/canvas/blackboard-import'); // where we'll store uploaded files
define('WORKING_DIR', buildPath(UPLOAD_DIR, 'tmp')); // where we'll be dumping temp files (and cleaning up, too!)
define('TOOL_NAME', 'Blackboard 8 &rarr; Canvas Import Tool');
define('BREADCRUMB_SEPARATOR', ' > '); // when creating a breadcrumb trail in the names of subitems
define('CANVAS_Bb_IMPORT_ACCOUNT_ID', 167); // the default account in which to create new courses

/* Blackboard-specific names */
define('Bb_MANIFEST_NAME', 'imsmanifest.xml'); // name of the manifest file
define('Bb_RES_FILE_EXT', '.dat'); // file extension of resource files
define('Bb_CONTENT_COLLECTION_DIR', 'csfiles');

define('DEBUGGING', true);

/* XML Receipt values */
define('CANVAS_IMPORT_TYPE', 'canvas-import-type');
define('CANVAS_INDENT_LEVEL', 'canvas-indent-level');
define('CANVAS_ID', 'canvas-id');
define('CANVAS_URL', 'canvas-url');

define('CANVAS_MODULE', 'Module');
define('CANVAS_FILE', 'File');
define('CANVAS_PAGE', 'Page');
define('CANVAS_EXTERNAL_URL', 'External URL');
define('CANVAS_MODULE_ITEM', 'Module Item (link to a course item)');
define('CANVAS_QUIZ', 'Quiz');
define('CANVAS_ASSIGNMENT', 'Assignment');
define('CANVAS_DISCUSSION', 'Discussion');
define('CANVAS_SUBHEADER', 'Module Subheader');
define('CANVAS_ANNOUNCEMENT', 'Announcement');
define('CANVAS_NO_IMPORT', 'Ignored and not imported');


/***********************************************************************
 *                                                                     *
 * Helpers                                                             *
 *                                                                     *
 ***********************************************************************/
 
/**
 * A handy little helper function to print a (somewhat) friendly error
 * message and fail out when things get hairy.
 **/
function exitOnError($title, $text = '') {
	$html = "<h3>$title</h3>";
	if (is_array($text)) {
		foreach ($text as $line) {
			$html .= "\n<p>" . $line . "</p>";
		}
	} elseif (strlen($text)) {
		$html .= "\n<p>". $text . "</p>";
	}
	displayPage($html);
	exit;
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
	<a href="' . $_SERVER['PHP_SELF'] . '">Start Over</a>
</div>
<div id="content">
'. $content . '
</div>
<div id="footer">
	Copyright &copy; 2013 Seth Battis, in some annoyance. Not for redistribution or reuse without <a href="mailto:SethBattis@stmarksschool.org?subject=Blackboard+to+Canvas+Importer">explicit permission</a>. Like Major League Baseball.
</div>
</body>
</html>';
}

/**
 * Helper function to conditionally fill the log file with notes!
 **/
function debug_log($message) {
	if (DEBUGGING) {
		error_log($message);
	}
}

/**
 * Force nodes and attributes to all lower-case in a given XML document,
 * returning a SimpleXML object.
 **/
function simplexml_load_file_lowercase($fileName) {
	if (file_exists($fileName)) {
		$xmlWoNkYcAsE = simplexml_load_file($fileName);
		$xslt = new XSLTProcessor();
		$xsl = simplexml_load_file('./lowercase-transform.xsl');
		$xslt->importStylesheet($xsl);
		return (simplexml_load_string($xslt->transformToXML($xmlWoNkYcAsE)));
	} else {
		return false;
	}
}

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

	try {
		$json = $PEST->$verb($url, $data, buildCanvasAuthorizationHeader());
	} catch (Exception $e) {
		exitOnError('API Error',
			array(
				'Something went awry in the API.',
				$e->getMessage(),
				"verb: $verb",
				"url: $url",
				'data:',
				'<pre>' . print_r($data, true) . '</pre>'
			)
		);
	}
	
	return $json;
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
	return $path;
}
 

/***********************************************************************
 *                                                                     *
 * Import Stages                                                       *
 *                                                                     *
 ***********************************************************************/

/**
 * Handles the actual file uploading and unzipping into the working directory
 **/
function stageUpload() {
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		if (isset($_FILES['BbExportFile'])) {
			if ($_FILES['BbExportFile']['error'] === UPLOAD_ERR_OK) {
				$uploadFile = buildPath(UPLOAD_DIR, basename($_FILES['BbExportFile']['name']));
				// FIXME: need a per-session temp directory structure to prevent over-writes/conflicts
				move_uploaded_file($_FILES['BbExportFile']['tmp_name'], $uploadFile);
				$zip = new ZipArchive();
				if ($zipRsrc = $zip->open($uploadFile)) {
					flushDir(WORKING_DIR);
					$zip->extractTo(WORKING_DIR);
					$zip->close();
					return true;
				} else exitOnError('Unzipping Failed', 'The file you uploaded could not be unzipped.');
			} else exitOnError('Upload Error', array('There was an error with your file upload.', 'Error ' . $_FILES['BbExportFile']['error'] . ': See the <a href="http://www.php.net/manual/en/features.file-upload.errors.php">PHP Documentation</a> for more information.'));
		} else exitOnError('No File Uploaded');
	}
	return false;
}

/**
 * Parse a module and update XML with notes for import
 **/
function parseItem($item, $manifest, $course, $module, $indent = 0, $breadcrumbs = '') {
	$res = getBbResourceFile($item);
	$item->addAttribute(CANVAS_INDENT_LEVEL, $indent);
	
	$contentHandler = getBbContentHandler($item, $res);
	switch ($contentHandler) {
		case 'resource/x-bb-assignment': {
			createCanvasAssignment($item, $res, $course, $module);
			break;
		}
		
		case 'resource/x-bb-courselink': {
			createCanvasCourseLink($item, $res, $course, $module);
			break;
		}
		
		case 'resource/x-bb-externallink': {
			$text = getBbBodyText($item, $res);
			if (strlen($text)) {
				createCanvasPage($item, $res, $course, $module);
			} else {
				createCanvasExternalUrl($item, $res, $course, $module);
			}
			break;
		}
		
		case 'resource/x-bb-asmt-survey-link': {
			createCanvasQuiz($item, $res, $course, $module);
			break;
		}
		
		case 'resource/x-bb-folder':
		case 'resource/x-bb-lesson': {
			$subheader = createCanvasModuleSubheader($item, $res, $course, $module);
			$subitemNodes = $item->item;
			if ($subitemNodes) {
				foreach ($subitemNodes as $subitemNode) {
					// TODO: actually make use of the breadcrumbs!
					parseItem($subitemNode, $manifest, $course, $module, $indent + 1,
						$breadcrumbs . (strlen($breadcrumbs) ? BREADCUMB_SEPARATOR : '') . $subheader['title']
					);
				}
			}
			break;
		}
		
		case 'resource/x-bb-vclink': {
			createCanvasConference($item, $res, $course, $module);
			break;
		}
		
		case 'resource/x-bb-file': {
			$text = getBbBodyText($item, $res);
			if (strlen($text)) {
				createCanvasPage($item, $res, $course, $module);
			} else {
				createCanvasFile($item, $res, $course, $module);
			}
			break;
		}
		
		case 'resource/x-bb-document': {
			$text = getBbBodyText($item, $res);
			$fileAttachmentCount = getBbFileAttachmentCount($item, $res);
			if (strlen($text) == 0 && $fileAttachmentCount == 1) {
				createCanvasFile($item, $res, $course, $module);
			} else {
				createCanvasPage($item, $res, $course, $module);
			}
			break;
		}
		
		default: {
			createCanvasNoImport($item, $res);
			break;
		}
	}
}

/**
 * Update Canvas course settings to match Bb
 **/
function parseCourseSettings($manifest, $course) {
	if ($courseSettingsNodes = $manifest->xpath('//resource[@type=\'course/x-bb-coursesetting\']')) {
		$courseSettingsNode = $courseSettingsNodes[0];
		$courseSettings = simplexml_load_file_lowercase(buildPath(WORKING_DIR, (string) $courseSettingsNode->attributes()->identifier . Bb_RES_FILE_EXT));
		
		$courseTitle = getBbCourseTitle($courseSettingsNode, $courseSettings);
		
		$json = callCanvasApi('put', "/courses/{$course['id']}",
			array(
				'course[name]' => $courseTitle,
				'course[course_code]' => $courseTitle,
				'course[start_at]' => getBbCourseStart($courseSettingsNode, $courseSettings),
				'course[end_at]' => getBbCourseEnd($courseSettingsNode, $courseSettings),
				'course[public_description]' => getBbCourseDescription($courseSettingsNode, $courseSettings),
				'course[sis_course_id]' => getBbCourseId($courseSettingsNode, $courseSettings), // FIXME: Don't override pre-existing SIS IDs
				'course[default_view]' => 'modules'
			)
		);
		
		$courseUpdate = json_decode($json, true);
		
		if ($courseUpdate) {
			foreach($courseUpdate as $key => $value) {
				$course[$key] = $value;
				$courseSettingsNode->addAttribute("canvas-$key", $value);
			}
			return $course;
		} else {
			exitOnError('Course Settings Failed',
				array(
					'There was a problem trying to import the course settings for your course.',
					'<pre>' . print_r($json, true) . '</pre>'
				)
			);
		}		
	} else {
		exitOnError('Missing Course Settings', 'The course settings resource file for your course could not be identified.');
	}
	return false;
}

/**
 * Parse the XML of the manifest file and prepare a preview for the user before
 * committing to the actual import into Canvas
 **/
function parseManifest($manifestName, $course) {
	$manifestFile = buildPath(WORKING_DIR, $manifestName);
	if (file_exists($manifestFile)) {
		$manifest = simplexml_load_file_lowercase($manifestFile);
		
		$course = parseCourseSettings($manifest, $course);
		
		// FIXME: emailed Cortny and Jason re: file upload API crapping out
		// $contentCollection = canvasUploadContentCollection($manifest, $course);
		
		$moduleNodes = $manifest->xpath('/manifest/organizations/organization/item');
		foreach ($moduleNodes as $moduleNode) {
			$itemNodes = $moduleNode->item;
			if ($itemNodes) {
				$res = getBbResourceFile($moduleNode);
				$module = createCanvasModule($moduleNode, $res, $course);
				foreach($itemNodes as $itemNode) {
					parseItem($itemNode, $manifest, $course, $module);
				}
			}
		}
		
		// TODO: when the file upload is fixed, export this XML and upload it to the course, posting a link on the finished page
		$html = "<h3>&ldquo;{$course['name']}&rdquo; Imported</h3><p>Open <a target=\"_blank\" href=\"http://" . parse_url(CANVAS_API_URL, PHP_URL_HOST) . '/courses/' . $course['id'] . "\">{$course['name']}</a> in Canvas.</p>";
		$html .= '<h3>Receipt</h3>';
		$html .= '<pre>';
		$html .= print_r($manifest, true);
		$html .= '</pre>';
		displayPage($html);
		
	} else exitOnError('Missing Manifest', "The manifest file ($manifestName) that should have been included in your Blackboard Exportfile cannot be found.");
}

/**
 * Look up the course specifed by the course URL
 **/
function parseCourseUrl($courseUrl) {
	// TODO: force default view to modules

	$canvasHost = parse_url(CANVAS_API_URL, PHP_URL_HOST);
	return (int) preg_replace("|https?://$canvasHost/courses/(\d+).*|i", '\\1', $courseUrl);	
}

/***********************************************************************
 *                                                                     *
 * Blackboard (Bb) Functions                                           *
 *                                                                     *
 ***********************************************************************/

/**
 * Is this XML item a Bb application?
 **/
function isBbApplication($item) {
	return preg_match('|COURSE_DEFAULT\..*\.APPLICATION\.label|', $item->title);
}

/**
 * Strip off COURSE_DEFAULT metadata and CamelCasing
 **/
function stripBbCourseDefault($label) {
	$camelCaseTitle = preg_replace('|course_default\.(.+)\.label|i', '\\1', $label);
	return preg_replace('|(.*[a-z])([A-Z].*)|', '\\1 \\2', $camelCaseTitle);
}

/**
 * Return the filename of the resource file that accompanies this item in the
 * archive (as a SimpleXML Element)
 **/
function getBbResourceFile($item) {
	return simplexml_load_file_lowercase(buildPath(WORKING_DIR, (string) $item->attributes()->identifierref . Bb_RES_FILE_EXT));
}

/**
 *  Extract the contenthandler mimetype from a res00000 file as text
 **/
function getBbContentHandler($item, $res) {
	if ($contentHandlerNode = $res->xpath('//contenthandler')) {
	
		return (string) $contentHandlerNode[0]->attributes()->value;
	}

	return false;
}

/**
 * Extract the label text from a res00000 file as text
 **/
function getBbLabel($item, $res) {
	if ($labelNode = $res->xpath('/coursetoc/label')) {
		$label = preg_replace('|COURSE_DEFAULT\.(.*)\.\w+\.label|', '\\1', (string) $labelNode[0]->attributes()->value);
		
		return $label;	
	} 
		
	return false;
}

/**
 * Extract the title from a res00000 file as text
 **/
function getBbTitle($item, $res) {
	if ($titleNode = $res->xpath('//title')) {
		
		// FIXME: Clean up COURSE_DEFAULT names
		
		return (string) $titleNode[0]->attributes()->value;	
	}
		
	return false;
}

/**
 * Extract the URL from a res00000 file as text
 **/
function getBbUrl($item, $res) {
	if ($urlNode = $res->xpath('//url')) {
		
		return (string) $urlNode[0]->attributes()->value;
	}
		
	return false;
}

/**
 * Extract content item text from a res00000 file as html
 **/
function getBbBodyText($item, $res) {
	if ($textNode = $res->xpath('/content/body/text')) {
		$text = str_replace(array('&lt;', '&gt;'), array('<', '>'), (string) $textNode[0]);
		
		return $text;
	}
	
	return false;
}

/**
 *  Extract the number of files attached to this item
 **/
function getBbFileAttachmentCount($item, $res) {
	if ($fileNodes = $res->xpath('/content/files/file')) {
		return count($fileNodes);
	}
	return 0;
}

/**
 * Extract the x-id, path and filename from a <lom> file accompanying a file
 * from the Content Collection, return as an associative array
 **/
function getBbLomFileInfo($fileName) {
	if ($lomNode = simplexml_load_file_lowercase("$fileName.xml")) {
		$identifier = (string) $lomNode->relation->resource->identifier;
		if ($identifier) {
			$fileInfo['x-id'] = preg_replace('|^([_0-9]+)#.*|', '$1', $identifier);
			$fileInfo['path'] = preg_replace("|^{$fileInfo['x-id']}#(.*)|", '\\1', $identifier);
			$fileInfo['name'] = basename($fileInfo['path']);
			$fileInfo['path'] = dirname($fileInfo['path']); // FIXME: may need to trim off the leading / to prevent files ending up in weird places?
			return $fileInfo;
		}
	}
	return false;
}

/**
 * Extract a course name from the course settings res00000 file
 **/
function getBbCourseTitle($item, $res) {
	if ($titleNode = $res->xpath('/course/title')) {
		return (string) $titleNode[0]->attributes()->value;
	}
	return false;
}

/**
 * Extract a course id from the course settings file
 **/
function getBbCourseId($item, $res) {
	if ($courseIdNode = $res->xpath('/course/courseid')) {
		return (string) $courseIdNode[0]->attributes()->value;
	}
	return false;
}

/**
 * Extract course start date and time
 **/
function getBbCourseStart($item, $res) {
	if ($courseStartNode = $res->xpath('//coursestart')) {
		$startDate = new DateTime();
		$startDate->createFromFormat('%Y-%m-%d %H:%i:%s %T', (string) $courseStartNode[0]->attributes()->value);
		return $startDate->format('%Y-%m-%dT%H:%i%P');
	}
	return false;
}

/**
 * Extract course end date and time
 **/
function getBbCourseEnd($item, $res) {
	if ($courseEndNode = $res->xpath('//courseend')) {
		$endDate = new DateTime();
		$endDate->createFromFormat('%Y-%m-%d %H:%i:%s %T', (string) $courseEndNode[0]->attributes()->value);
		return $endDate->format('%Y-%m-%dT%H:%i%P');
	}
	return false;
}

/**
 * Extract the course description
 **/
function getBbCourseDescription($item, $res) {
	if ($courseDescription = (string) $res->description) {
		return $courseDescription;
	}
	return false;
}

/***********************************************************************
 *                                                                     *
 * Canvas Content Builders                                             *
 *                                                                     *
 ***********************************************************************/

/**
 * Calculate the next module item position
 **/
$MODULE_ITEM_POSITION = 0;
$PREVIOUS_MODULE_ID = null;
function nextModuleItemPosition($moduleId) {
	global $MODULE_ITEM_POSITION, $PREVIOUS_MODULE_ID;
	if ($moduleId !== $PREVIOUS_MODULE_ID) {
		$PREVIOUS_MODULE_ID = $moduleId;
		$MODULE_ITEM_POSITION = 0;
	}
	
	return ++$MODULE_ITEM_POSITION;
}

/**
 * Return the Canvas indent level as a string
 **/
function getCanvasIndentLevel($item, $res) {
	$itemAttributes = $item->attributes();
	$indent = (string) $itemAttributes[CANVAS_INDENT_LEVEL];
	if (strlen($indent)) {
		return $indent;
	}
	return false;
}

/**
 * Return Canvas course as an associative array
 **/
function getCanvasCourse($courseId) {
	if ($courseId) {
		$json = callCanvasApi('get', "/courses/$courseId", array());
		$course = json_decode($json, true);
		if (!$course['id']) {
			exitOnError('Invalid Course ID',
				array (
					"The course ID in the URL you entered for the target Canvas course ($courseUrl) could not be found by the Canvas API.",
					'<pre>' . print_r($json, true) . '</pre>'
				)
			);
		}
		return $course;
	}
	return false;
}

/**
 * Create a new Canvas course and return as an associative array
 **/
function createCanvasCourse() {
	$json = callCanvasApi('post', 'accounts/' . CANVAS_Bb_IMPORT_ACCOUNT_ID . '/courses',
		array(
			'account_id' => CANVAS_Bb_IMPORT_ACCOUNT_ID
		)
	);
	$course = json_decode($json, true);
	if (!$course['id']) {
		exitOnError('Could Not Create Course',
			array(
				'Something went wrong and we could not create a target course in Canvas.',
				'<pre>' . print_r($json, true) . '</pre>' 
			)
		);
	}
	return $course;
}

/**
 * Create Canvas module, returning the JSON result as an associative array
 **/
$MODULE_POSITION = 0;
function createCanvasModule($item, $res, $course) {
	global $MODULE_POSITION;
	
	$label = getBbLabel($item, $res);
	
	$json = callCanvasApi('post', "/courses/{$course['id']}/modules",
		array (
			'module[name]' => $label,
			'module[position]' => ++$MODULE_POSITION
		)
	);
	
	$module = json_decode($json, true);
	$item->addAttribute(CANVAS_IMPORT_TYPE, CANVAS_MODULE);
	$item->addAttribute(CANVAS_ID, $module['id']);
	
	return $module;
}

function createCanvasModuleSubheader($item, $res, $course, $module) {
	$json = callCanvasApi('post', "/courses/{$course['id']}/modules/{$module['id']}/items",
		array (
			'module_item[title]' => getBbTitle($item, $res),
			'module_item[type]' => 'SubHeader',
			'module_item[position]' => nextModuleItemPosition($module['id']),
			'module_item[indent]' => getCanvasIndentLevel($item, $res)
		)
	);
	
	$moduleItem = json_decode($json, true);

	$item->addAttribute(CANVAS_IMPORT_TYPE, CANVAS_SUBHEADER);
	$item->addAttribute(CANVAS_ID, $moduleItem['id']);
	
	return $moduleItem;
}

/**
 * Create Canvas Page, returning the JSON result of the module item pointed
 * at the page as an associative array;
 **/
function createCanvasPage($item, $res, $course, $module) {
	$title = getBbTitle($item, $res);
	$text = "<h2>$title</h2>\n" . getBbBodyText($item, $res); // Canvas filters out <h1>
	
	/* there may be some additional body text to add, depending on mimetype */
	$contentHandler = getBbContentHandler($item, $res);
	switch($contentHandler) {
		case 'resource/x-bb-file': {
			// TODO: <files>
		}
		case 'resource/x-bb-document': {
			// TODO: check for file attachments
			break;			
		}
		
		case 'resource/x-bb-externallink': {
			$text .= '<blockquote><a href="' . getBbUrl($item, $res) . "\">$title</a></blockquote>";
			break;
		}
		
	}
	
	// TODO: Process embedded links and images
	
	$json = callCanvasApi('post', "/courses/{$course['id']}/pages",
		array(
			'wiki_page[title]' => $title,
			'wiki_page[body]' => $text,
			'wiki_page[published]' => 'true'
		)			
	);
	$page = json_decode($json, true);
	
	$json = callCanvasApi('post', "/courses/{$course['id']}/modules/{$module['id']}/items",
		array(
			'module_item[title]' => $title,
			'module_item[type]' => 'Page',
			'module_item[position]' => nextModuleItemPosition($module['id']),
			'module_item[indent]' => getCanvasIndentLevel($item, $res),
			'module_item[page_url]' => $page['url']
		)
	);
	
	$moduleItem = json_decode($json, true);

	$item->addAttribute(CANVAS_IMPORT_TYPE, CANVAS_PAGE);
	$item->addAttribute(CANVAS_ID, $moduleItem['id']);
	$item->addAttribute(CANVAS_URL, $moduleItem['html_url']);
	$item->addChild('text', htmlentities($text));

	return $moduleItem;
}

/**
 * Upload a file to Canvas, returning the file information as an associative
 * array
 **/
function canvasUploadFile($fileName, $localPath, &$fileInfo, $course) {
	$json = callCanvasApi('post', "/courses/{$course['id']}/files",
		array(
			'name' => $fileInfo['name'],
			'size' => $fileInfo['size'],
			'parent_folder_path' => $fileInfo['path'],
			'on_duplicate' => 'rename'
		)
	);
	
	$fileUpload = json_decode($json, true);
		
	if ($fileUpload) {
		$uploadRequest = new Pest($fileUpload['upload_url']);
		$uploadParams = $fileUpload['upload_params'];
		$uploadParams['file'] = '@' . str_replace('\\', '\\\\', buildPath($localPath, $fileName));
		
		try {
			$response = $uploadRequest->post('', $uploadParams);
		} catch (Exception $e) {
			exitOnError('Problem Uploading File',
				array(
					"There was a problem with the API uploading a file ($fileName).",
					'<pre>' . print_r($e->getMessage(), true) . '</pre>',
					'<pre>' . print_r($uploadParams, true) . '</pre>'
				)
			);
		}
		
		$httpheaders = http_parse_headers($response);
		if ($httpheaders['location']) {
			$confirm = new Pest($httpheaders['location']);
			$json = $confirm->post('', array(), buildCanvasAuthorizationHeader());
			
			$file = json_decode($json, true);
			if ($file) {
				foreach ($file as $key => $value) {
					$fileInfo[$key] = $value;
				}
				
				return $file;
			} else {
				exitOnError('Could Not Confirm File Upload',
					array(
						"Although a file ($fileName) appears to have uploaded, it could not be confirmed through the Canvas API.",
						'<pre>' . print_r($json, true) . '</pre>'
					)
				);
			}
		} else {
			exitOnError('Upload to Canvas Failed',
				array(
					"A file ($fileName) could not be uploaded to Canvas.",
					'<pre>' . print_r($response, true) . '</pre>'
				)
			);
		}
	} else {
		exitOnError('Problem Starting File Upload',
			array(
				"There was a problem starting the upload process to Canvas for a file ($fileName).",
				'<pre>' . print_r($json, true) . '</pre>'
			)
		);
	}
	
	return false;
}

/**
 * Uploads the files from the Content Collection, building a list of for
 * future reference
 **/

function canvasUploadContentCollection($manifest, $course) {
	$contentCollectionPath = buildPath(WORKING_DIR, Bb_CONTENT_COLLECTION_DIR);
	$files = glob("$contentCollectionPath\\\\*");
	$contentCollection = array();
	foreach ($files as $file) {
		if (!preg_match('|^.*\.xml$|i', $file)) {
			$fileInfo = getBbLomFileInfo($file);
			if ($fileInfo) {
				$fileInfo['size'] = filesize($file);
				$canvasFile = canvasUploadFile(basename($file), dirname($file), $fileInfo, $course);
			}
		}
		$contentCollection[] = $fileInfo;
	}
	
	return $contentCollection;
}

function createCanvasFile($item, $res, $course, $module) {
	$item->addAttribute(CANVAS_IMPORT_TYPE, CANVAS_FILE);
	createCanvasPage($item, $res, $course, $module); // FIXME: this is... not right
}

/**
 * Create an External URL in a module, returning the JSON result of the module
 * item as an associative array.
 **/
function createCanvasExternalUrl($item, $res, $course, $module) {
	$json = callCanvasApi('post', "/courses/{$course['id']}/modules/{$module['id']}/items",
		array(
			'module_item[title]' => getBbTitle($item, $res),
			'module_item[type]' => 'ExternalUrl',
			'module_item[position]' => nextModuleItemPosition($module['id']),
			'module_item[indent]' => getCanvasIndentLevel($item, $res),
			'module_item[external_url]' => getBbUrl($item, $res)
		)
	);
	
	$moduleItem = json_decode($json, true);
	
	$item->addAttribute(CANVAS_IMPORT_TYPE, CANVAS_EXTERNAL_URL);
	$item->addAttribute(CANVAS_ID, $moduleItem['id']);
	$item->addAttribute(CANVAS_URL, $moduleItem['html_url']);
}

/**
 * Create an Assignment in a module, returning the JSON result of the module
 * item as an associate array
 **/
$ASSIGNMENT_POSITION = 0;
function createCanvasAssignment($item, $res, $course, $module) {
	global $ASSIGNMENT_POSITION;
	// TODO: Need to pull points value, due date out of Bb gradebook settings
	// TODO: Need to handle file attachments
	$title = getBbTitle($item, $res);
	$text = getBbBodyText($item, $res);
	
	/* remove Bb assignment internals */
	$text = substr($text, 0, strpos($text, '<!--BB ASSIGNMENT INTERNALS: SKIP REST-->'));
	
	$json = callCanvasApi('post', "/courses/{$course['id']}/assignments",
		array(
			'assignment[name]' => $title,
			'assignment[position]' => ++$ASSIGNMENT_POSITION,
			'assignment[submission_types]' => '["online_upload"]',
			'assignment[description]' => $text,
			'assignment[published]' => 'true'
		)
	);
	
	$assignment = json_decode($json, true);
	
	$json = callCanvasApi('post', "/courses/{$course['id']}/modules/{$module['id']}/items",
		array(
			'module_item[title]' => $title,
			'module_item[type]' => 'Assignment',
			'module_item[content_id]' => $assignment['id'],
			'module_item[position]' => nextModuleItemPosition($module['id']),
			'module_item[indent]' => getCanvasIndentLevel($item, $res)
		)
	);
	
	$moduleItem = json_decode($json, true);

	$item->addAttribute(CANVAS_IMPORT_TYPE, CANVAS_ASSIGNMENT);
	$item->addAttribute(CANVAS_ID, $moduleItem['id']);
	$item->addAttribute(CANVAS_URL, $moduleItem['html_url']);
	$item->addChild('text', htmlentities($text));

	return $moduleItem;
}

function createCanvasCourseLink($item, $res, $course, $module) {
	$item->addAttribute(CANVAS_IMPORT_TYPE, CANVAS_MODULE_ITEM);
}

function createCanvasQuiz($item, $res, $course, $module) {
	$item->addAttribute(CANVAS_IMPORT_TYPE, CANVAS_QUIZ);
}


function createCanvasConference($item, $res, $course, $module) {
	$item->addAttribute(CANVAS_IMPORT_TYPE, CANVAS_CONFERENCE);
}

/**
 * We're not going to import this information (either because there is no
 * matching type, or we're just not ready yet...
 **/
function createCanvasNoImport($item, $res) {
	$item->addAttribute(CANVAS_IMPORT_TYPE, CANVAS_NO_IMPORT);
}

/***********************************************************************
 *                                                                     *
 * The main program... whee!                                           *
 *                                                                     *
 ***********************************************************************/

function main() {
	global $Bb_MANIFEST_NAME;

	/* are we uploading a file? */
	if (isset($_FILES['BbExportFile'])) {
		if (stageUpload()) {
			$courseId = parseCourseUrl($_REQUEST["courseUrl"]);
			$course = null;
			if($courseId) {
				$course = getCanvasCourse($courseId);
			} else {
				$course = createCanvasCourse();
			}
			$manifest = parseManifest(Bb_MANIFEST_NAME, $course);
		}
	} else {
		/* well, it appears that nothing has happened yet, so let's just start with
		   a basic file upload form, as an aperitif to the main event... */
		displayPage('
<style><!--
	.LMS {
		background-color: #c3d3df;
		padding: 20px;
		width: 50%;
		border-radius: 20px;
	}
	
	#arrow {
		padding: 0px 20px;
	}
	
	#arrow input[type=submit] {
		font-size: 24pt;
	}
--></style>	
<form enctype="multipart/form-data" action="' . $_SERVER['PHP_SELF'] . '" method="POST">
	<table>
		<tr valign="middle">
			<td id="Bb" class="LMS">
				<input type="hidden" name="MAX_FILE_SIZE" value="262144000" />
				<label for="BbExportFile">Blackboard ExportFile <span class="comment">(250MB / 26,214,400 Bytes maximum size)</span></label>
				<input id="BbExportFile" name="BbExportFile" type="file" />
			</td>
			<td id="arrow">
				<input type="submit" value="&rarr;" onsubmit="if (this.getAttribute(\'submitted\')) return false; this.setAttribute(\'submitted\',\'true\');" />
			</td>
			<td id="canvas" class="LMS">
				<label for="courseUrl">Canvas Course URL <span class="comment">(leave blank to import into a new course)</span><label>
				<input id="courseUrl" name="courseUrl" type="text" />
			</td>
		</tr>
	</table>
</form>');
		exit;
	}
}

main();

?>