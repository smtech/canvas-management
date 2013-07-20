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

/* what Canvas API user are we going to connect as? */
require_once('.ignore.blackboard-import-authentication.inc.php');


/* configurable options */
require_once('blackboard-import.config.inc.php');

/* handles the core of the Canvas API interactions */
require_once('canvas-api.inc.php');

/* we do directly work with Pest on some AWS API calls */
require_once('Pest.php');



/***********************************************************************
 *                                                                     *
 * Globals & Constants                                                 *
 *                                                                     *
 ***********************************************************************/

define('DEBUGGING', true);
debug_log('***********************************************************************');

/* Blackboard-specific names */
define('Bb_MANIFEST_NAME', 'imsmanifest.xml'); // name of the manifest file
define('NAMESPACE_Bb', 'bb'); // prefix for the Blackboard xml namespace
define('Bb_RES_FILE_EXT', '.dat'); // file extension of resource files
define('Bb_EMBED_DIR', 'embedded'); // name of the embedded files directory in the res00000 directory
define('Bb_CONTENT_COLLECTION_DIR', 'csfiles'); // name of the content collection directory in ExportFile
define('Bb_TIMESTAMP_FORMAT', '!Y-m-d H:i:s T');
define('CANVAS_TIMESTAMP_FORMAT', 'Y-m-d\TH:iP');

/* indices */
define('MANIFEST', 'Manifest'); // $course[]
define('CONTENT_COLLECTION', 'Content Collection'); // $course[]
define('GRADEBOOK', 'Gradebook'); // $course[]
define('CANVAS_SMART_URL', 'smart_url'); // $attachments[]
define('Bb_ITEM_TITLE', 'Bb_item_title'); // $page[]

/* XML Receipt values */
define('NAMESPACE_CANVAS', 'c'); // canvas namespace prefix
define('XMLNS_CANVAS', 'http://stmarksschool.instructure.com/blackboard-import'); // whatever

define('ATTRIBUTE_CANVAS_IMPORT_TYPE', 'canvas-import-type');
define('ATTRIBUTE_INDENT_LEVEL', 'canvas-indent-level');
define('ATTRIBUTE_CANVAS_ID', 'canvas-id');
define('ATTRIBUTE_CANVAS_URL', 'canvas-url');
define('ATTRIBUTE_Bb_FILE_INFO', 'bb-file-info');
define('ATTRIBUTE_Bb_RELEASE_DATE', 'bb-release-date');

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

define('NODE_ATTACHMENTS', 'files');
define('NODE_CONTENT_COLLECTION', 'contentcollection');
define('NODE_FILE', 'file');
define('NODE_TITLE', 'title');
define('NODE_TEXT', 'text');


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
	// FIXME: this needs to become an error logger, with an OPTION to exit -- some ExportFiles are missing content collection items that they can't import without!
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
function loadFileAsSimpleXmlWithLowercaseNodesAndAttributes($fileName) {
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
				// TODO: need a per-session temp directory structure to prevent over-writes/conflicts
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
 * Look up the course specifed by the course URL
 **/
function parseCourseUrl($courseUrl) {
	$canvasHost = parse_url(CANVAS_API_URL, PHP_URL_HOST);
	return (int) preg_replace("|https?://$canvasHost/courses/(\d+).*|i", '\\1', $courseUrl);	
}

/**
 * process the XML of the manifest file and prepare a preview for the user before
 * committing to the actual import into Canvas
 **/
function processManifest($manifestName, $course) {
	$manifestFile = buildPath(WORKING_DIR, $manifestName);
	if (file_exists($manifestFile)) {
		$manifest = loadFileAsSimpleXmlWithLowercaseNodesAndAttributes($manifestFile);
		
		$course = processCourseSettings($manifest, $course);
		$course[MANIFEST] = $manifest;
		
		$course[CONTENT_COLLECTION] = uploadContentCollection($course);
		
		$course[GRADEBOOK] = processGradebook($course);
		
		$moduleNodes = $course[MANIFEST]->xpath('/manifest/organizations/organization/item');
		foreach ($moduleNodes as $moduleNode) {
			$itemNodes = $moduleNode->item;
			if ($itemNodes) {
				$res = getBbResourceFile($moduleNode);
				$module = createCanvasModule($moduleNode, $res, $course);
				foreach($itemNodes as $itemNode) {
					processItem($itemNode, $course, $module);
				}
			}
		}
		
		processAnnouncements($course);
		
		$html = "<h3>&ldquo;{$course['name']}&rdquo; Imported</h3><p>Open <a target=\"_blank\" href=\"http://" . parse_url(CANVAS_API_URL, PHP_URL_HOST) . '/courses/' . $course['id'] . "\">{$course['name']}</a> in Canvas.";

		$receiptFile = buildPath(WORKING_DIR, RECEIPT_FILE_NAME);
		if ($course[MANIFEST]->asXml($receiptFile)) {
			$fileInfo = array('name' => RECEIPT_FILE_NAME);
			if ($receiptFile = uploadCanvasFile(basename($receiptFile), dirname($receiptFile), $fileInfo, $course)) {
				$html .= ' A receipt file (<a href="https://' . parse_url(CANVAS_API_URL, PHP_URL_HOST) . "/courses/{$course['id']}/files/{$receiptFile['id']}/download?wrap=1\">" . RECEIPT_FILE_NAME . '</a>) detailing the actions taken has been uploaded to your course files in Canvas.';
			} else {
				// TODO: make a download link
				$html .= ' Normally a file (' . RECEIPT_FILE_NAME . ') would have been uploaded to your course files in Canvas, however the upload failed. It\'s contents are displayed below.</p>';
				$html .= '<pre>' . print_r($course[MANIFEST], true) . '</pre>';
			}
		} else {
			$html .= ' Normally a file (' . RECEIPT_FILE_NAME . ') would have been uploaded to your course files in Canvas, however it could not be created. It\'s contents are displayed below.</p>';
			$html .= '<pre>' . print_r($course[MANIFEST], true) . '</pre>';
		}

		displayPage($html);
		
	} else exitOnError('Missing Manifest', "The manifest file ($manifestName) that should have been included in your Blackboard Exportfile cannot be found.");
}

/**
 * Update Canvas course settings to match Bb
 **/
function processCourseSettings($manifest, $course) {
	if ($courseSettingsNodes = $manifest->xpath('//resource[@type=\'course/x-bb-coursesetting\']')) {
		$courseSettingsNode = $courseSettingsNodes[0];
		$courseSettings = loadFileAsSimpleXmlWithLowercaseNodesAndAttributes(buildPath(WORKING_DIR, (string) $courseSettingsNode->attributes()->identifier . Bb_RES_FILE_EXT));
		
		$courseTitle = getBbCourseTitle($courseSettingsNode, $courseSettings);
		
		$json = callCanvasApi('put', "/courses/{$course['id']}",
			array(
				'course[name]' => $courseTitle,
				'course[course_code]' => $courseTitle,
				'course[start_at]' => getBbCourseStart($courseSettingsNode, $courseSettings),
				'course[end_at]' => getBbCourseEnd($courseSettingsNode, $courseSettings),
				'course[public_description]' => getBbCourseDescription($courseSettingsNode, $courseSettings),
				'course[sis_course_id]' => getBbCourseId($courseSettingsNode, $courseSettings),
				'course[default_view]' => 'modules'
			)
		);
		
		$courseUpdate = json_decode($json, true);
		
		if ($courseUpdate) {
			foreach($courseUpdate as $key => $value) {
				$course[$key] = $value;
				$courseSettingsNode->addAttribute(
					'canvas-' . str_replace('_', '-', $key),
					(is_array($value) ? json_encode($value) : $value)
				);
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
 * Uploads the files from the Content Collection, building a list of for
 * future reference
 **/

function uploadContentCollection($course) {
	$contentCollectionPath = buildPath(WORKING_DIR, Bb_CONTENT_COLLECTION_DIR);
	$files = glob("$contentCollectionPath\\\\*");
	$contentCollection = array();
	$contentCollectionNode = $course[MANIFEST]->addChild(NODE_CONTENT_COLLECTION);
	foreach ($files as $file) {
		if (is_file($file) && !preg_match('|^.*\.xml$|i', $file)) {
			$fileInfo = getBbLomFileInfo($file);
			$fileInfo['path'] = buildPath(CANVAS_CONTENT_COLLECTION_PATH, $fileInfo['path']);
			if ($fileInfo) {
				$canvasFile = uploadCanvasFile(basename($file), dirname($file), $fileInfo, $course);
				$contentCollectionFileNode = $contentCollectionNode->addChild(NODE_FILE, htmlentities($fileInfo['name']));
				foreach($canvasFile as $key => $value) {
					$contentCollectionFileNode->addAttribute(
						'canvas-' . str_replace('_', '-', $key),
						(is_array($value) ? json_encode($value) : htmlentities($value))
					);
					$fileInfo[$key] = $value;
				}
				$contentCollection[$fileInfo['x-id']] = $fileInfo;
			} elseif (!is_dir($file)) { // ham-handed way to exclude the csfiles\/ directory
				exitOnError('Missing LOM Information', "A file ($file) was missing its LOM (Learning Object Metadata) file and is therefore lost to the content collection.");
			}
		}
	}
	return $contentCollection;
}

/**
 * process gradebook and return an associative array of parsings
 **/
function processGradebook($course) {

	// TODO: do something creative with the embedded grading schme <SCALES> -- Canvas has no API access to grading schemes
	if ($gradebookNode = $course[MANIFEST]->xpath("//resource[@type='course/x-bb-gradebook']")) {
		$gradebookNode = $gradebookNode[0];
		$gradebookResourceFile = getBbResourceFile($gradebookNode);
		if ($categoryNodes = $gradebookResourceFile->xpath('//categories/category')) {
			$gradebook = array();
			foreach($categoryNodes as $categoryNode) {
				$gradebook = array_merge($gradebook, createCanvasAssignmentGroupAndAssignments($gradebookResourceFile, $categoryNode, $course));
			}
			
			/* some assignments may not be assigned to a category? how could this be? I have no idea. None. */
			if ($gradebookNode->xpath("//outcomedefinition[categoryid[@value='']]")) {
				$gradebook = array_merge($gradebook, createCanvasAssignmentGroupAndAssignments($gradebookResourceFile, null, $course));
			}
			return $gradebook;
		}
	}
	return false;
}

/**
 * create all of the assignments in a particular category/assignment group
 **/
function createCanvasAssignmentGroupAndAssignments($gradebookNode, $categoryNode, $course) {
	$x_id = '';
	$title = 'Uncategorized Assignments';
	if ($categoryNode) {
		$x_id = (string) $categoryNode->attributes()->id;
		$title = getBbTitle(null, $categoryNode);
	}
	$gradebookCategory = array();
	
	$assignmentNodes = $gradebookNode->xpath("//outcomedefinition[categoryid[@value='$x_id']]");
	if ($assignmentNodes) {
		$assignmentGroup = createCanvasAssignmentGroup($categoryNode, $course);
		foreach ($assignmentNodes as $assignmentNode) {
			if (getBbIsCalculated($assignmentNode) != 'true') {
				$scoreProviderHandle = getBbScoreProviderHandle($assignmentNode);
				switch ($scoreProviderHandle) {
					case 'resource/x-bb-assignment': {
						debug_log("creating assignment for $scoreProviderHandle " . getBbXId($assignmentNode));
						$gradebookCategory[getBbResourceFileName($assignmentNode)] = createCanvasAssignment(
							$assignmentNode,
							getBbResourceFile($assignmentNode),
							$course,
							$gradebookNode,
							$assignmentGroup
						);
						break;
					}
					case 'resource/x-bb-assessment': {
						debug_log("creating quiz for $scoreProviderHandle " . getBbXId($assignmentNode));
					}
				}
			}
		}
	}
	return $gradebookCategory;
}

/**
 * process a module and update XML with notes for import
 **/
function processItem($item, $course, $module, $indent = 0, $breadcrumbs = '') {
	$res = getBbResourceFile($item);
	$item->addAttribute(ATTRIBUTE_INDENT_LEVEL, $indent);
	
	$contentHandler = getBbContentHandler($item, $res);
	switch ($contentHandler) {
		case 'resource/x-bb-assignment': {
			createCanvasModuleItem(
				CANVAS_ASSIGNMENT,
				getCanvasIndentLevel($item, $res),
				$course[GRADEBOOK][getBbResourceFileName($item)], 
				$course,
				$module); 
			break;
		}
		
		case 'resource/x-bb-courselink': {
			// FIXME: is this a special case?
			createCanvasCourseLink($item, $res, $course, $module);
			break;
		}
		
		case 'resource/x-bb-externallink': {
			$text = getBbText($item, $res);
			if (strlen($text)) {
				createCanvasModuleItem(
					CANVAS_PAGE,
					getCanvasIndentLevel($item, $res),
					createCanvasPage($item, $res, $course),
					$course,
					$module
				);
			} else {
				/// FIXME: is this a special case?
				createCanvasExternalUrl($item, $res, $course, $module);
			}
			break;
		}
		
		case 'resource/x-bb-asmt-survey-link': {
			/*createCanvasModuleItem(
				CANVAS_QUIZ,
				getCanvasIndentLevel($item, $res),
				createCanvasQuiz($item, $res, $course),
				$course,
				$module
			);*/
			break;
		}
		
		case 'resource/x-bb-folder':
		case 'resource/x-bb-lesson': {
			$subheader = createCanvasModuleSubheader($item, $res, $course, $module);
			$subitemNodes = $item->item;
			if ($subitemNodes) {
				foreach ($subitemNodes as $subitemNode) {
					// TODO: actually make use of the breadcrumbs!
					processItem($subitemNode, $course, $module, $indent + 1,
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
			$text = getBbText($item, $res);
			if (strlen($text)) {
				createCanvasModuleItem(
					CANVAS_PAGE,
					getCanvasIndentLevel($item, $res),
					createCanvasPage($item, $res, $course, $module),
					$course,
					$module);
			} else {
				// FIXME: is this a special case?
				createCanvasFile($item, $res, $course, $module);
			}
			break;
		}
		
		case 'resource/x-bb-document': {
			$text = getBbText($item, $res);
			$fileAttachmentCount = getBbFileAttachmentCount($item, $res);
			if (strlen($text) == 0 && $fileAttachmentCount == 1) {
				// FIXME: is this a special case?
				createCanvasFile($item, $res, $course, $module);
			} else {
				createCanvasModuleItem(
					CANVAS_PAGE,
					getCanvasIndentLevel($item, $res),
					createCanvasPage($item, $res, $course, $module), 
					$course,
					$module);
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
 * process and post announcements
 **/
function processAnnouncements($course) {
	if ($announcementNodes = $course[MANIFEST]->xpath('//resource[@type=\'resource/x-bb-announcement\']')) {
		// TODO: Include in receipt
		foreach($announcementNodes as $announcementNode) {
			$announcement = createCanvasAnnouncement($announcementNode, getBbResourceFile($announcementNode), $course);
		}
	}
	return false;
}

/***********************************************************************
 *                                                                     *
 * Blackboard (Bb) Functions                                           *
 *                                                                     *
 ***********************************************************************/

/**
 * Extract the name of an item's resource file
 **/
function getBbResourceFileName($item) {
	if (is_object($item)) {
		switch ($item->getName()) {
			case 'item':
				return (string) $item->attributes()->identifierref;
				
			case 'resource':
				return $itemAttributes = $item->attributes()->identifier;
				
			case 'outcomedefinition':
				if (getBbScoreProviderHandle($item) == 'resource/x-bb-assessment') {
					return (string) $item->asidataid->attributes()->value;
				}
				return (string) $item->contentid->attributes()->value;
		}
	}
	return false;
}

/**
 * Return the filename of the resource file that accompanies this item in the
 * archive (as a SimpleXML Element)
 **/
function getBbResourceFile($item) {
	return loadFileAsSimpleXmlWithLowercaseNodesAndAttributes(buildPath(WORKING_DIR, getBbResourceFileName($item) . Bb_RES_FILE_EXT));
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

		/* strip off Bb default name tagging */
		$label = preg_replace('|COURSE_DEFAULT\.(.*)\.\w+\.label|i', '\\1', (string) $labelNode[0]->attributes()->value);
		
		/* it looks like some things still come through as .labels, just rip that stuff off... */
		$label = preg_replace('|.*\.([^\.]*)\.label|i', '\\1', $label);
		
		/* deCamelCase --> de Camel Case */
		$label = preg_replace('|(.*[a-z])([A-Z].*)|', '\\1 \\2', $label);
		
		return $label;	
	} 
		
	return false;
}

/**
 * Extract the title from a res00000 file as text
 **/
function getBbTitle($item, $res) {
	if (is_object($res)) {
		switch($res->getName()) {
			case 'questestinterop': {
				if ($title = $res->assessment->attibutes()->title) {
					return $title;
				}
				break;
			}
			default: {
				if ($titleNode = $res->xpath('//title')) {
					
					return (string) $titleNode[0]->attributes()->value;	
				}
				break;
			}
		}
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
function getBbText($item, $res) {
	if ($textNode = $res->xpath('//text')) {
		$text = str_replace(array('&lt;', '&gt;'), array('<', '>'), (string) $textNode[0]);
		
		return $text;
	}
	
	return false;
}

/**
 *  Extract the number of files attached to this item
 **/
function getBbFileAttachmentCount($item, $res) {
	if ($fileNodes = $res->xpath('//files/file')) {
		return count($fileNodes);
	}
	return 0;
}



/**
 * Extract the name of the first file attachment
 **/
function getBbFileAttachmentName($item, $res) {
	if ($fileNameNode = $res->xpath('//file/name')) {
		return (string) $fileNameNode[0];
	}
	return false;
}

/**
 * Extract the listed size of the first file attachment
 **/
function getBbFileAttachmentSize($item, $res) {
	if ($fileSizeNode = $res->xpath('//file/size')) {
		return (string) $fileSizeNode[0]->attributes()->value;
	}
	return false;
}

/**
 * Extract the x-id, path and filename from a <lom> file accompanying a file
 * from the Content Collection, return as an associative array
 **/
function getBbLomFileInfo($fileName) {
	if ($lomNode = loadFileAsSimpleXmlWithLowercaseNodesAndAttributes("$fileName.xml")) {
		$identifier = (string) $lomNode->relation->resource->identifier;
		if ($identifier) {
			$fileInfo['x-id'] = preg_replace('|^([_0-9]+)#.*|', '$1', $identifier);
			$fileInfo['path'] = preg_replace("|^{$fileInfo['x-id']}#(.*)|", '\\1', $identifier);
			$fileInfo['name'] = basename($fileInfo['path']);
			$fileInfo['path'] = dirname($fileInfo['path']);
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
		return (string) $courseIdNode[0]->attributes()->value . ' (Imported ' . date('Y-m-d h:i:s A') . ')';
	}
	return false;
}

function convertBbTimeStampToCanvasTimeStamp($bbTimestamp) {
	$date = date_create_from_format(Bb_TIMESTAMP_FORMAT, $bbTimestamp);
	if ($date) {
		return date_format($date, CANVAS_TIMESTAMP_FORMAT);
	}
	false;
}

/**
 * Extract course start date and time
 **/
function getBbCourseStart($item, $res) {
	if ($courseStartNode = $res->xpath('//coursestart')) {
		$bbTimestamp = (string) $courseStartNode[0]->attributes()->value;
		if (strlen($bbTimestamp)) {
			return convertBbTimeStampToCanvasTimeStamp();
		}
	}
	return false;
}

/**
 * Extract course end date and time
 **/
function getBbCourseEnd($item, $res) {
	if ($courseEndNode = $res->xpath('//courseend')) {
		$bbTimestamp = (string) $courseEndNode[0]->attributes()->value;
		if (strlen($bbTimestamp)) {
			return convertBbTimeStampToCanvasTimeStamp();
		}
	}
	return false;
}

/**
 * Extract restricted start timestamp
 **/
function getBbRestrictStart($item, $res) {
	if ($restrictStartNode = $res->xpath('//restrictstart')) {
		return convertBbTimeStampToCanvasTimeStamp((string) $restrictStartNode[0]->attributes()->value);
	}
	return false;
}

/**
 * Extract assignment due date timestampe
 **/
function getBbDue($item, $res) {
	if ($dueNode = $item->xpath('//dates/due')) {
		return convertBbTimeStampToCanvasTimeStamp((string) $dueNode[0]->attributes()->value);
	}
	return false;
}

/**
 * Extract points possible for an assignment
 **/
function getBbPointsPossible($item) {
	if ($pointsPossibleNode = $item->xpath('//pointspossible')) {
		return (string) $pointsPossibleNode[0]->attributes()->value;
	}
	return false;
}

/**
 * Extract outcome scale title for an assignment
 **/
function getBbScaleType($item, $gradebookNode) {
	if ($scaleIdNode = $item->xpath('//scaleid')) {
		$scaleId = (string) $scaleIdNode[0]->attributes()->value;
		if ($scaleTitleNode = $gradebookNode->xpath('//scale/type')) {
			return (string) $scaleTitleNode[0]->attributes()->value;
		}
	}
}

/**
 * Extract outcome position
 **/
function getBbPosition ($item, $res) {
	if ($positionNode = $item->xpath('//position')) {
		return (string) $positionNode[0]->attributes()->value;
	}
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

/**
 * Extract the Bb X-ID from an object
 **/
function getBbXId($item) {
	if ($x_id = (string) $item->attributes()->id) {
		return $x_id;
	}
	return false;
}

/**
 * Extract X-ID value from a string
 **/
function parseBbXId($string) {
	/* there is an explicitly identified X-ID */
	if (preg_match('|^/xid-([_0-9]+)|i', $string, $matches)) {
		return $matches[1];
		
	/* ...or the string is just an X-ID entirely */
	} elseif (preg_match('|^[_0-9]+$|', $string)) {
		return $string;
	}
	return false;
}

/**
 * Extract ISCALCULATED value from an assignment
 **/
function getBbIsCalculated($item) {
	if ($isCalculated = (string) $item->iscalculated->attributes()->value) {
		return $isCalculated;
	}
	return false;
}

/**
 * Extract ScoreProviderHandle from an assignment
 **/
function getBbScoreProviderHandle($item) {
	if ($scoreProviderHandle = (string) $item->score_provider_handle->attributes()->value) {
		return $scoreProviderHandle;
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
	if ($moduleId !== $GLOBALS['PREVIOUS_MODULE_ID']) {
		$GLOBALS['PREVIOUS_MODULE_ID'] = $moduleId;
		$GLOBALS['MODULE_ITEM_POSITION'] = 0;
	}
	
	return ++$GLOBALS['MODULE_ITEM_POSITION'];
}

/**
 * Append links to attachments to the HTML text body of an item
 **/
function appendAttachmentLinks(&$item, $res, $course, $text) {
	if ($attachments = uploadCanvasFileAttachments($item, $res, $course)) {
		$text .= "\n<h3>Attached Files</h3>\n";
		foreach ($attachments as $attachment) {
			$text .= "<blockquote><a class=\"instructure_scribd_link instructure_file_link\" href=\"{$attachment[CANVAS_SMART_URL]}\">" . $attachment['display_name'] .'</a></blockquote>'; 
		}
	}
	return $text;
}

/**
 * Scan through the body text and replace Blackboard embed codes with links
 * to Canvas files
 **/
function relinkEmbeddedLinks(&$item, $res, $course, $text) {
	/* links to attached files */
	if (preg_match_all('|@X@EmbeddedFile\.location@X@([^"]+)|', $text, $embeddedAttachments, PREG_SET_ORDER)) {
		foreach($embeddedAttachments as $embeddedAttachment) {
			$fileName = urldecode($embeddedAttachment[1]);
			
			/* is it something that we've already uploaded as an attachment? */
			if ($attachmentFileNode = $item->xpath("//file/@canvas-display-name='$fileName'")) {
				$attachmentFileNodeAttributes = $attachmentFileNode->attributes();
				$text = str_replace($embeddedAttachment[0], "/courses/{$course['id']}/files/{$attachmentFileNodeAttributes['canvas-id']}", $text);
			
			/* or is it something that we now need to upload? */
			} else {
				$localFilePath = buildPath(WORKING_DIR, getBbResourceFileName($item)) . '\\\\' . Bb_EMBED_DIR . '\\\\';
				$localFiles = glob("$localFilePath*");
				$attachments = array();

				$filesNode = $item->addChild(NODE_ATTACHMENTS);
				$file = null;
				foreach ($localFiles as $localFile) {
					$fileInfo['name'] = $fileName;
					
					/* we get lucky and file names match*/
					if ($fileName == basename($localFile)) {
						$file = uploadCanvasFile(basename($localFile), dirname($localFile), $fileInfo, $course);
												
					/* Bb hosed the name and left no record, so we hope there's only one embedded file */
					} elseif (count($localFiles) == 1) {
						$fileInfo['original-name'] = basename($localFile);
						$fileInfo['match'] = 'based on a single file attachment being available';
						$file = uploadCanvasFile(basename($localFile), dirname($localFile), $fileInfo, $course);
					
					/* Bb hosed the name and left no record, but there are a bunch of embedded files, so we try to match by file extension */
					} elseif (pathinfo($localFile, PATHINFO_EXTENSION) == pathinfo($fileName, PATHINFO_EXTENSION)) {								
						$fileInfo['original-name'] = basename($localFile);
						$fileInfo['match'] = 'based on file size and extension';
						$file = uploadCanvasFile(basename($localFile), dirname($localFile), $fileInfo, $course);
					}
					
					if ($file) {
						$file[ATTRIBUTE_Bb_FILE_INFO] = $fileInfo;
						// FIXME: figure out how to record this in the receipt for embeddef files
						/* $attachments[$file['display_name']] = $file;
						$attachmentFileNode = $filesNode->addChild(NODE_FILE, htmlentities($file['display_name']));
						foreach($file as $key => $value) {
							$attachmentFileNode->addAttribute(
								'canvas-' . str_replace('_', '-', $key),
								(is_array($value) ? json_encode($value) : htmlentities($value))
							);
						}*/
						break;
					}
				}
				if ($file) {
					$text = str_replace($embeddedAttachment[0], "/courses/{$course['id']}/files/{$file['id']}", $text);
				} else {
					debug_log("Embedded file not found: '$fileName'"); // FIXME: more detail
				}
			}
		}
		
	/* links to content collection items */
	} elseif (preg_match_all('|@X@EmbeddedFile\.requestUrlStub@X@bbcswebdav/xid-([_0-9]+)|', $text, $embeddedContentCollectionAttachments, PREG_SET_ORDER)) {
		foreach($embeddedContentCollectionAttachments as $embeddedAttachment) {
			$x_id = $embeddedAttachment[1];
			if (isset($course[CONTENT_COLLECTION][$x_id])) {
				$text = str_replace($embeddedAttachment[0], "/courses/{$course['id']}/files/" . $course[CONTENT_COLLECTION][$x_id]['id'], $text);
			} else {
				exitOnError('Embedded Content Collection Item Missing'); // FIXME: more detail
			}
		}
		
	/* miscellaneous internal links -- humorously including /, which will now redirect to Canvas! */
	} elseif (preg_match_all('|@X@EmbeddedFile\.requestUrlStub@X@([^"]+)|', $text, $embeddedBbLinks, PREG_PATTERN_ORDER)) {
		$text = str_replace($embeddedBbLinks[0], $embeddedBbLinks[1], $text);
	} elseif (strpos($text, '@X@') !== false) {
		exitOnError('Unreplaced Embedded Content'); // FIXME: more detail
	}

	// <img src="/courses/903/files/10539/preview" alt="Giant Purple Snorklewhacker.png" />
	$text = preg_replace('|(src="/courses/[^"]+)(")|', '\\1/preview\\2', $text);
	
	// <a class=" instructure_image_thumbnail instructure_file_link" title="Giant Purple Snorklewhacker.png" href="/courses/903/files/10539/download?wrap=1">link to image</a>
	$text = preg_replace('|(href="/courses/[^"]+)(")|', '\\1/download?wrap=1\\2', $text);
	
	return $text;
}

/**
 * Return the Canvas indent level as a string
 **/
function getCanvasIndentLevel($item, $res) {
	$itemAttributes = $item->attributes();
	$indent = (string) $itemAttributes[ATTRIBUTE_INDENT_LEVEL];
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
 * Upload a file to Canvas, returning the file information as an associative
 * array
 **/
function uploadCanvasFile($fileName, $localPath, &$fileInfo, $course) {
	// TODO: It would be faster to figure out a way to do this asynchronously
	/* stage local file for upload */
	$stageName = md5($fileName . time());
	$originalFile = buildPath($localPath, $fileName);
	$fileSize = filesize($originalFile);
	$stageFile = buildPath(UPLOAD_STAGING_DIR, $stageName);
	if (copy($originalFile, $stageFile)) {
		$json = callCanvasApi('post', "/courses/{$course['id']}/files",
			array(
				'url' => UPLOAD_STAGING_URL . $stageName,
				'name' => $fileInfo['name'],
				'size' => $fileSize,
				'content_type' => mime_content_type($originalFile), // doesn't seem to be helping to include this
				'parent_folder_path' => (isset($fileInfo['path']) ? $fileInfo['path'] : CANVAS_DEFAULT_PATH),
				'on_duplicate' => 'rename'
			)
		);
		
		$uploadProcess = json_decode($json, true);
		
		$statusCheck = new Pest($uploadProcess['status_url']);
		
		$delay = (int) $fileSize / 174762.667; // calculated based on Jason Peacock's "5-6min to upload 50mb"
		
		while ($uploadProcess['upload_status'] == 'pending') {
			sleep($delay);
			$delay = 0.5; // default delay after our first guess
			try {
				$json = $statusCheck->get('', '', buildCanvasAuthorizationHeader());
		} catch (Pest_ServerError $e) {
			// Not. My. Problem. Ignoring it. Will retry as usual.
		} catch (Exception $e) {
				exitOnError('Status Check Failed',
					array(
						"A status check on a file upload ($fileName) failed.",
						'<pre>' . print_r($uploadProcess, true) . '</pre>',
						$e->getMessage()
					)
				);
			}
			$uploadProcess = json_decode($json, true);
		}
		
		if ($uploadProcess['upload_status'] == 'ready') {
			// TODO: make sure file metadata gets saved to receipt
			unlink($stageFile);
			return $uploadProcess['attachment'];
		} else {
			exitOnError('File Upload Problem',
				array(
					"There was a problem uploading a file ($fileName)",
					'<pre>' . print_r($uploadProcess, true) . '</pre>'
				)
			);
		}
		
	} else {
		exitOnError('Failed to Stage File for Upload', "We tried to get a file ($fileName) staged for upload to Canvas, but it failed.");
	}
}

/**
 * Upload all of the files attached to an item, returning an array of
 * associative arrays of file information
 **/ 
function uploadCanvasFileAttachments($item, $res, $course) {
	$localFilePath = buildPath(WORKING_DIR, getBbResourceFileName($item)) . '\\\\';
	$localFiles = glob("$localFilePath*");
	$attachments = array();
	if ($attachmentNodes = $res->xpath('//files/file')) {
		$filesNode = $item->addChild(NODE_ATTACHMENTS);
		foreach ($attachmentNodes as $attachmentNode) {
			$file = null;
			$fileInfo = array(
				'name' => getBbFileAttachmentName($item, $attachmentNode),
				'size' => getBbFileAttachmentSize($item, $attachmentNode),
			);
			
			/* we get lucky and file names match*/
			if (($i = array_search("$localFilePath{$fileInfo['name']}", $localFiles)) !== false) {
				$file = uploadCanvasFile(basename($localFiles[$i]), dirname($localFiles[$i]), $fileInfo, $course);
				
			/* it's in the content collection, and we look it up by x-id */
			} elseif ($x_id = parseBbXId($fileInfo['name'])) {
				$file = $course[CONTENT_COLLECTION][$x_id];
				
			/* Bb hosed the name and left no record, so we hope there's only one attachment */
			} elseif (getBbFileAttachmentCount($item, $res) == 1) {
				$fileInfo['original-name'] = basename($localFiles[0]);
				$fileInfo['match'] = 'based on a single file attachment being available';
				$file = uploadCanvasFile(basename($localFiles[0]), dirname($localFiles[0]), $fileInfo, $course);
			
			/* Bb hosed the name and left no record, but there are a bunch of attachments, so we try to match by size and file extension */
			} else { // hoo boy... here we go!
				foreach ($localFiles as $localFile) {
					if (filesize($localFile) == $fileInfo['size'] &&
						pathinfo($localFile, PATHINFO_EXTENSION) == pathinfo($fileInfo['name'], PATHINFO_EXTENSION)) {
						
						$fileInfo['original-name'] = basename($localFile);
						$fileInfo['match'] = 'based on file size and extension';
						$file = uploadCanvasFile(basename($localFile), dirname($localFile), $fileInfo, $course);
						break;
					}
				}
			}
			
			if ($file) {
				$file[ATTRIBUTE_Bb_FILE_INFO] = $fileInfo;
				$file[CANVAS_SMART_URL] = "/courses/{$course['id']}/files/{$file['id']}/download?wrap=1";
				$attachments[$file['display_name']] = $file;
				$attachmentFileNode = $filesNode->addChild(NODE_FILE, htmlentities($file['display_name']));
				foreach($file as $key => $value) {
					$attachmentFileNode->addAttribute(
						'canvas-' . str_replace('_', '-', $key),
						utf8_encode(is_array($value) ? json_encode($value) : htmlentities($value))
					);
				}
				break;
			} else {
				$linkName = (string) $attachmentNode->linkname->attributes()->value;
				$json = callCanvasApi('post', "/courses/{$course['id']}/pages",
					array(
						'wiki_page[title]' => "Missing \"$linkName\"", 
						'wiki_page[body]' => "<h2>Missing &ldquo;$linkName&rdquo;</h2><p>This file was referred to by an item in the Blackboard ExportFile, but was not included in the ExportFile. Therefore it was not available for import and was not uploaded.</p>",
						'wiki_page[published]' => true
					)
				);
				
				$page = json_decode($json, true);
				
				$page[CANVAS_SMART_URL] = "/courses/{$course['id']}/wiki/{$page['url']}";
				$page['display_name'] = $linkName . ' (Missing)';
				$attachments[$page['display_name']] = $page;
				// FIXME: not passed by reference, adding here doesn't make a difference? worth sorting through the nested functionc alls?
				/* if ($x_id = parseBbXId($fileInfo['name'])) {
					$course[CONTENT_COLLECTION][$x_id] = $page;
				}*/
			}
		}
		return $attachments;
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
	
	$label = getBbLabel($item, $res);
	
	$json = callCanvasApi('post', "/courses/{$course['id']}/modules",
		array (
			'module[name]' => $label,
			'module[position]' => ++$GLOBALS['MODULE_POSITION']
		)
	);
	
	$module = json_decode($json, true);
	$item->addAttribute(ATTRIBUTE_CANVAS_IMPORT_TYPE, CANVAS_MODULE);
	$item->addAttribute(ATTRIBUTE_CANVAS_ID, $module['id']);
	
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

	$item->addAttribute(ATTRIBUTE_CANVAS_IMPORT_TYPE, CANVAS_SUBHEADER);
	$item->addAttribute(ATTRIBUTE_CANVAS_ID, $moduleItem['id']);
	
	return $moduleItem;
}

function createCanvasModuleItem($moduleItemType, $indent, $itemArray, $course, $module) {
	/* there really can't be a "default" title... */
	$title = null;
	
	/* ...but we know that most things will have content_ids */
	$referenceName = 'module_item[content_id]';
	
	/* ...and presumably an id -- although pages won't, so we check first */
	$referenceValue = (isset($itemArray['id']) ? $itemArray['id'] : null);
	
	/* try to get the "real" values for title and reference */
	switch ($moduleItemType) {
		case CANVAS_PAGE: {
			$title = $itemArray[Bb_ITEM_TITLE];
			$referenceName = 'module_item[page_url]';
			$referenceValue = $itemArray['url'];
			break;
		}
		case CANVAS_FILE: {
			$title = $itemArray['display_name'];
			break;
		}
		case CANVAS_ASSIGNMENT: {
			$title = $itemArray['name'];
			break;
		}
	}
	$json = callCanvasApi('post', "/courses/{$course['id']}/modules/{$module['id']}/items",
		array(
			'module_item[title]' => $title,
			'module_item[type]' => $moduleItemType,
			'module_item[position]' => nextModuleItemPosition($module['id']),
			'module_item[indent]' => $indent,
			$referenceName => $referenceValue
		)
	);
	
	$moduleItem = json_decode($json, true);
}

/**
 * Create Canvas Page, returning an associative array;
 **/
function createCanvasPage($item, $res, $course) {
	$title = getBbTitle($item, $res);
	if (preg_match('|[0-9a-z]|i', $title) == false) {
		$canvasTitle = getBbXId($res);
	} else {
		$canvasTitle = $title;
	}
	$text = "<h2>$title</h2>\n" . getBbText($item, $res); // Canvas filters out <h1>
	$text = appendAttachmentLinks($item, $res, $course, $text);
	$text = relinkEmbeddedLinks($item, $res, $course, $text);

	
	/* there may be some additional body text to add, depending on mimetype */
	$contentHandler = getBbContentHandler($item, $res);
	switch($contentHandler) {
		case 'resource/x-bb-file':
		case 'resource/x-bb-document': {
			break;			
		}
		
		case 'resource/x-bb-externallink': {
			$text .= '<h3><a href="' . getBbUrl($item, $res) . "\">$title</a></h3>";
			break;
		}
		
	}
		
	$json = callCanvasApi('post', "/courses/{$course['id']}/pages",
		array(
			'wiki_page[title]' => $canvasTitle,
			'wiki_page[body]' => $text,
			'wiki_page[published]' => 'true'
		)			
	);
	$page = json_decode($json, true);
	$page[Bb_ITEM_TITLE] = $title;

	// FIXME: add detail to receipt
	$item->addAttribute(ATTRIBUTE_CANVAS_IMPORT_TYPE, CANVAS_PAGE);
	$titleNode = $item->addChild(NODE_TITLE, htmlentities($title));
	$textNode = dom_import_simplexml($item->addChild(NODE_TEXT));
	$textNode->appendChild(new DOMCdataSection($text));
	
	return $page;
}

/**
 * Create a link to a single File in a module, returning the JSON result of the
 * module item as an associative array.
 *
 * (multiple files are a Page, handled by createCanvasPage())
 **/
function createCanvasFile($item, $res, $course, $module) {
	// TODO: use breadcrumbs to create file upload paths
	if ($attachments = uploadCanvasFileAttachments($item, $res, $course)) {
		$keys = array_keys($attachments);
		$json = callCanvasApi('post', "/courses/{$course['id']}/modules/{$module['id']}/items",
			array(
				'module_item[title]' => getBbTitle($item, $res),
				'module_item[type]' => 'File',
				'module_item[content_id]' => $attachments[$keys[0]]['id'],
				'module_item[position]' => nextModuleItemPosition($module['id']),
				'module_item[indent]' => getCanvasIndentLevel($item, $res)
			)
		);
		
		$moduleItem = json_decode($json, true);
		
		$item->addAttribute(ATTRIBUTE_CANVAS_IMPORT_TYPE, CANVAS_FILE);
		$item->addAttribute(ATTRIBUTE_CANVAS_ID, $moduleItem['id']);
		$item->addAttribute(ATTRIBUTE_CANVAS_URL, $moduleItem['html_url']);
		
		return $moduleItem;
	} else {
		exitOnError('File Attachment Upload Failed', 'The files attached to an item could not be uploaded.');
	}
	return faslse;
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
	
	$item->addAttribute(ATTRIBUTE_CANVAS_IMPORT_TYPE, CANVAS_EXTERNAL_URL);
	$item->addAttribute(ATTRIBUTE_CANVAS_ID, $moduleItem['id']);
	$item->addAttribute(ATTRIBUTE_CANVAS_URL, $moduleItem['html_url']);

	return $moduleItem;
}

/**
 * Create an Assignment in a module, returning the JSON result of the module
 * item as an associate array
 **/
function createCanvasAssignment($item, $res, $course, $gradebookNode, $assignmentGroup) {
	$title = getBbTitle($item, $res);
	$text = getBbText($item, $res);

	/* remove Bb assignment internals */
	$text = substr($text, 0, strpos($text, '<!--BB ASSIGNMENT INTERNALS: SKIP REST-->'));
	
	$text = relinkEmbeddedLinks($item, $res, $course, $text);
	$text = appendAttachmentLinks($item, $res, $course, $text);

	$gradingType = 'points';
	switch (getBbScaleType($item, $gradebookNode)) {
		case 'PERCENT': {
			$gradingType = 'percent';
			break;
		}
		case 'SCORE': {
			$gradingType = 'points';
			break;
		}
		case 'COMPLETE': {
			$gradingType = 'pass_fail';
			break;
		}
		case 'TABULAR': {
			// FIXME: should double-check that all tabular grades are letter grades...
			$gradingType = 'letter_grade';
			break;
		}
		case 'TEXT': {
			// FIXME: This will be an option in Canvas in Q3 or Q4 2013, we think
		}
	}
	
	$json = callCanvasApi('post', "/courses/{$course['id']}/assignments",
		array(
			'assignment[name]' => $title,
			'assignment[position]' => getBbPosition($item, $res),
			'assignment[submission_types]' => '["online_upload"]',
			'assignment[points_possible]' => getBbPointsPossible($item),
			'assignment[grading_type]' => $gradingType,
			'assignment[due_at]' => getBbDue($item, $res),
			'assignment[description]' => $text,
			'assignment[assignment_group_id]' => $assignmentGroup['id'],
			'assignment[published]' => 'true'
		)
	);
	
	$assignment = json_decode($json, true);
	
	// FIXME: should make the receipt more complete!
	$item->addAttribute(ATTRIBUTE_CANVAS_IMPORT_TYPE, CANVAS_ASSIGNMENT);
	$item->addAttribute(ATTRIBUTE_CANVAS_ID, $assignment['id']);
	$item->addAttribute(ATTRIBUTE_CANVAS_URL, $assignment['html_url']);
	$item->addChild(NODE_TITLE, htmlentities($title));
	$textNode = dom_import_simplexml($item->addChild(NODE_TEXT));
	$textNode->appendChild(new DOMCdataSection($text));

	return $assignment;
}

/**
 * Create a Canvas assignment group
 **/
function createCanvasAssignmentGroup($item, $course) {
	$json = callCanvasApi('post', "/courses/{$course['id']}/assignment_groups",
		array(
			// FIXME: name needs to include grading period
			'name' => str_replace('.name', '', getBbTitle(null, $item))
		)
	);
	
	$assignmentGroup = json_decode($json, true);
	
	// FIXME: save to receipt
	
	return $assignmentGroup;
}

function createCanvasCourseLink($item, $res, $course, $module) {
	// FIXME: need to actually build the dang link
	$item->addAttribute(ATTRIBUTE_CANVAS_IMPORT_TYPE, CANVAS_MODULE_ITEM);
}

function createCanvasQuiz($item, $res, $course, $module) {
	// FIXME: need to create the quiz
	$item->addAttribute(ATTRIBUTE_CANVAS_IMPORT_TYPE, CANVAS_QUIZ);
}


function createCanvasConference($item, $res, $course, $module) {
	// FIXME: need to create the conference
	$item->addAttribute(ATTRIBUTE_CANVAS_IMPORT_TYPE, CANVAS_CONFERENCE);
}

/**
 * Create an announcement in Canvas, returning the associative array
 * describing it
 **/
function createCanvasAnnouncement($item, $res, $course) {
	/* can't seem to change the post date in canvas... ugh */
	$postDate = getBbRestrictStart($item, $res);
	$title = getBbTitle($item, $res);
	$date = date_create_from_format(CANVAS_TIMESTAMP_FORMAT, $postDate);
	$title .= date_format($date, ' (n/j/Y \a\t g:i A)');
	
	$text = getBbText($item, $res);
	$text = appendAttachmentLinks($item, $res, $course, $text);
	$text = relinkEmbeddedLinks($item, $res, $course, $text);
	$json = callCanvasApi('post', "/courses/{$course['id']}/discussion_topics",
		array (
			'title' => $title,
			'message' => $text,
			'is_announcement' => 'true'
		)
	);
	
	$announcement = json_decode($json, true);
	
	// TODO: verify that this is writing to receipt
	$item->addAttribute(ATTRIBUTE_CANVAS_IMPORT_TYPE, CANVAS_ANNOUNCEMENT);
	$item->addAttribute(ATTRIBUTE_CANVAS_ID, $announcement['id']);
	$item->addAttribute(ATTRIBUTE_CANVAS_URL, $announcement['html_url']);
	$item->addAttribute(ATTRIBUTE_Bb_RELEASE_DATE, $postDate);
	$item->addChild(NODE_TITLE, htmlentities($title));
	$textNode = dom_import_simplexml($item->addChild(NODE_TEXT));
	$textNode->appendChild(new DOMCdataSection($text));
}

/**
 * We're not going to import this information (either because there is no
 * matching type, or we're just not ready yet...
 **/
function createCanvasNoImport($item, $res) {
	$item->addAttribute(ATTRIBUTE_CANVAS_IMPORT_TYPE, CANVAS_NO_IMPORT);
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
			processManifest(Bb_MANIFEST_NAME, $course);
		}
	} else {
		/* well, it appears that nothing has happened yet, so let's just start with
		   a basic file upload form, as an aperitif to the main event... */
		displayPage('
<style><!--
	.LMS {
		background-color: #c3d3df;
		padding: 20px;
		min-width: 200px;
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
				<input type="hidden" name="MAX_FILE_SIZE" value="262144000" /><!-- see .htaccess -->
				<label for="BbExportFile">Blackboard ExportFile <span class="comment">(250MB maximum size)</span></label>
				<input id="BbExportFile" name="BbExportFile" type="file" />
			</td>
			<td id="arrow">
				<input type="submit" value="&rarr;" onsubmit="if (this.getAttribute(\'submitted\')) return false; this.setAttribute(\'submitted\',\'true\'); this.setAttribute(\'enabled\', \'false\');" />
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