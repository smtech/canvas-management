<?php

/*
	The basic workflow:

	1. Upload ZIP archive (DONE)
	2. Unzip ZIP archive and extract course name, teacher, etc. (DONE)
	3. Create (or link to existing) Canvas course
	4. Upload items via API
		a. csfiles/ contents (build a list referenced by XID and Canvas ID for
		   future reference)
		b. walk through resources in manifest and post all attachments (build a
		   list referenced by res00000, XID if available, filename and Canvas ID)
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
define('ATTRIBUTE_INDENT', 'indent');
define('ATTRIBUTE_Bb_FILE_INFO', 'bb-file-info');
define('ATTRIBUTE_Bb_RELEASE_DATE', 'bb-release-date');

define('CANVAS_MODULE', 'Module');
define('CANVAS_FILE', 'File');
define('CANVAS_PAGE', 'Page');
define('CANVAS_EXTERNAL_URL', 'ExternalURL');
define('CANVAS_MODULE_ITEM', 'Module Item (link to a course item)');
define('CANVAS_QUIZ', 'Quiz');
define('CANVAS_ASSIGNMENT', 'Assignment');
define('CANVAS_ASSIGNMENT_GROUP', 'Assignment Group');
define('CANVAS_DISCUSSION', 'Discussion');
define('CANVAS_SUBHEADER', 'Module Subheader');
define('CANVAS_ANNOUNCEMENT', 'Announcement');
define('CANVAS_NO_IMPORT', 'Ignored and not imported');

define('NODE_COURSE_SETTINGS', 'course-settings');
define('NODE_ANNOUNCEMENTS', 'announcements');
define('NODE_ANNOUNCEMENT', 'announcement');
define('NODE_CONTENT_COLLECTION', 'content-collection');
define('NODE_FILE', 'file');
define('NODE_ASSIGNMENTS', 'assignments');
define('NODE_ASSIGNMENT_GROUP', 'assignment-group');
define('NODE_ASSIGNMENT', 'assignment');
define('NODE_PAGE', 'page');
define('NODE_TITLE', 'title');
define('NODE_TEXT', 'text');
define('NODE_ATTACHMENTS', 'files');
define('NODE_EMBEDDED_FILES', 'embedded');


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
	displayError($text, is_array($text), $title);
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
function getWorkingDir()
{
	return WORKING_DIR;
}


/***********************************************************************
 *                                                                     *
 * XML Helpers                                                         *
 *                                                                     *
 ***********************************************************************/
 
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
 * Helper function to construct attribute names with a (possible)
 * namespace prefix
 **/
function prependNamespace($attributeName, $namespacePrefix = CANVAS_NAMESPACE_PREFIX) {
	if (strlen($namespacePrefix)) {
		return "$namespacePrefix:$attributeName";
	}
	return $attributeName;
}

function appendCanvasResponseToReceipt($itemXml, $canvasResponseArray) {
	$itemDom = dom_import_simplexml($itemXml);
	foreach($canvasResponseArray as $key => $value) {
		$itemDom->setAttributeNS(
			CANVAS_NAMESPACE_URI,
			prependNamespace($key),
			(is_array($value) ? serialize($value) : $value)
		);
	}
}

/**
 * Helper function to add a new node into the manifest XML as a child of either
 * a known and specified node, or of a named node which may or may not exist.
 * If the parent is named, but does not exist, create it and insert it before
 * the successorName element, if it can be found (or append it at the end of
 * the manifest if the successor can't be found).
 * Returns the node that was added as a SimpleXMLElement
 **/
function addElementToReceipt($manifestXml, $elementName, $parentXmlOrNameOrNamespaceArray, $elementValue = null, $isCdata = false, $successorXmlOrNameOrNamespaceArray = 'organizations') {
	$manifestDom = dom_import_simplexml($manifestXml);
	$parentDom = comboFind($manifestXml, $parentXmlOrNameOrNamespaceArray);
	if (is_array($parentDom)) {
		$successorDom = comboFind($manifestXml, $successorXmlOrNameOrNamespaceArray);
		if ($successorDom) {
			$parentDom = $manifestDom->insertBefore(
				new DOMElement(
					prependNamespace($parentDom['name'], $parentDom['prefix']),
					null,
					$parentDom['uri']
				),
				$successorDom
			);
		} else {
			$parentDom = $manifestDom->appendChild(
				new DOMElement(
					prependNamespace($parentName),
					null,
					CANVAS_NAMESPACE_URI
				)
			);
		}
	}
	
	$elementDom = false;
	
	/* is our value Cdata? Then let's add a Cdata node! */
	if ($isCdata) {
		$elementDom = $parentDom->appendChild(
			new DOMElement(
				prependNamespace($elementName),
				null,
				CANVAS_NAMESPACE_URI
			)
		);
		$cdataDom = $elementDom->appendChild(new DOMCdataSection($elementValue));
	
	/* if not, encode those HTML entities, just to be safe */
	} else {
		$elementDom = $parentDom->appendChild(
			new DOMElement(
				prependNamespace($elementName),
				htmlentities($elementValue),
				CANVAS_NAMESPACE_URI
			)
		);
	}
	
	return simplexml_import_dom($elementDom);
}

function comboFind($manifestXml, $elementXmlOrNameOrNamespaceArray) {
	/* were we just given a SimpleXMLElement node? */
	if (is_object($elementXmlOrNameOrNamespaceArray)) {
		return dom_import_simplexml($elementXmlOrNameOrNamespaceArray);
		
	/* or were we given a node name to find? */
	} else {
		$elementDom = null;
		$elementName = (is_string($elementXmlOrNameOrNamespaceArray) ? $elementXmlOrNameOrNamespaceArray : null);
		$elementNamespacePrefix = null;
		$elementNamespaceUri = null;
		
		/* does the name exist in a namespace? */
		if (is_array($elementXmlOrNameOrNamespaceArray)) {
			$elementName = $elementXmlOrNameOrNamespaceArray['name'];
			$elementNamespacePrefix = (
				isset($elementXmlOrNameOrNamespaceArray['prefix']) ?
				$elementXmlOrNameOrNamespaceArray['prefix'] :
				CANVAS_NAMESPACE_PREFIX
			);
			$elementNamespaceUri = (
				isset($elementXmlOrNameOrNamespaceArray['uri']) ?
				$elementXmlOrNameOrNamespaceArray['uri'] :
				CANVAS_NAMESPACE_URI
			);
		}

		if ($elementNamespaceUri) {
			$manifestXml->registerXPathNamespace($elementNamespacePrefix, $elementNamespaceUri);
		}
		$xpathResult = $manifestXml->xpath(
			'//' . (
				$elementNamespaceUri ?
				prependNamespace($elementName, $elementNamespacePrefix) :
				$elementName
			)
		);
		
		/* if we can find that node, awesome */
		if (count($xpathResult)) {
			return dom_import_simplexml($xpathResult[0]);			
		} elseif ($elementNamespaceUri) {
			return array(
				'name' => $elementName,
				'prefix' => $elementNamespacePrefix,
				'uri' => $elementNamespaceUri
			);
		} else {
			return false;
		}
	}
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
					flushDir(getWorkingDir());
					$zip->extractTo(getWorkingDir());
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
	$manifestFile = buildPath(getWorkingDir(), $manifestName);
	if (file_exists($manifestFile)) {
		$manifest = loadFileAsSimpleXmlWithLowercaseNodesAndAttributes($manifestFile);
		
		$course = processCourseSettings($manifest, $course);

		$course[MANIFEST] = $manifest;
		
		$course[CONTENT_COLLECTION] = uploadContentCollection($course);
		
		$course[GRADEBOOK] = processGradebook($course);
		
		$xpathResult = $course[MANIFEST]->xpath('/manifest/organizations/organization/item');
		foreach ($xpathResult as $moduleXml) {
			$itemsXml = $moduleXml->item;
			if ($itemsXml) {
				$resXml = getBbResourceFile($moduleXml);
				$module = createCanvasModule($moduleXml, $resXml, $course);
				foreach($itemsXml as $itemXml) {
					processItem($itemXml, $course, $module);
				}
			}
		}
		
		processAnnouncements($course);
		
		// FIXME: process courselinks here (but only AFTER the import receipt is cleaned up -- need that to create the links)
		
		$html = "<h3>&ldquo;{$course['name']}&rdquo; Imported</h3><p>Open <a target=\"_blank\" href=\"http://" . parse_url(CANVAS_API_URL, PHP_URL_HOST) . '/courses/' . $course['id'] . "\">{$course['name']}</a> in Canvas.";

		$receiptFile = buildPath(getWorkingDir(), RECEIPT_FILE_NAME);
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
		debug_log('FINISHED');
		exit;
		
	} else exitOnError('Missing Manifest', "The manifest file ($manifestName) that should have been included in your Blackboard Exportfile cannot be found.");
}

/**
 * Update Canvas course settings to match Bb
 **/
function processCourseSettings($manifest, $course) {
	if ($xpathResult = $manifest->xpath('//resource[@type=\'course/x-bb-coursesetting\']')) {
		$courseSettingsXml = $xpathResult[0];
		$courseSettings = loadFileAsSimpleXmlWithLowercaseNodesAndAttributes(buildPath(getWorkingDir(), (string) $courseSettingsXml->attributes()->identifier . Bb_RES_FILE_EXT));
		
		$courseTitle = getBbTitle($courseSettings);
		
		$json = callCanvasApi('put', "/courses/{$course['id']}",
			array(
				'course[name]' => $courseTitle,
				'course[course_code]' => $courseTitle,
				'course[start_at]' => getBbCourseStart($courseSettings),
				'course[end_at]' => getBbCourseEnd($courseSettings),
				'course[public_description]' => getBbCourseDescription($courseSettings),
				'course[sis_course_id]' => getBbCourseId($courseSettings),
				'course[default_view]' => 'modules'
			)
		);
		
		$courseUpdate = json_decode($json, true);
		
		if ($courseUpdate) {
			/* update import receipt */
			// FIXME: argle-bargle should be the session ID or timestamp (or both?), if there were a session ID
			$manifest->addAttribute(prependNamespace('import-receipt'), 'argle-bargle', CANVAS_NAMESPACE_URI);
			$manifestDom = dom_import_simplexml($manifest);
			$xpathResult = $manifest->xpath('//organizations');
			$organizationsDom = dom_import_simplexml($xpathResult[0]);
			$courseDom = new DOMElement(prependNamespace(NODE_COURSE_SETTINGS), null, CANVAS_NAMESPACE_URI);
			$courseDom = $manifestDom->insertBefore($courseDom, $organizationsDom);
			$courseXml = simplexml_import_dom($courseDom);
			
			$course = array_merge($course, $courseUpdate);
			appendCanvasResponseToReceipt($courseXml, $courseUpdate);

			return $course;
		} else {
			displayError(
				$json,
				false,
				'Course Settings Failed',
				'There was a problem trying to import the course settings for your course.'
			);
			exit;
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
	$ccPath = buildPath(getWorkingDir(), Bb_CONTENT_COLLECTION_DIR);
	$ccFilenames = glob("$ccPath\\\\*");
	$contentCollection = array();
	$ccXml = addElementToReceipt($course[MANIFEST], NODE_CONTENT_COLLECTION, 'manifest');
	foreach ($ccFilenames as $ccFilename) {
		if (is_file($ccFilename) && !preg_match('|^.*\.xml$|i', $ccFilename)) {
			$fileInfo = getBbLomFileInfo($ccFilename);
			$fileInfo['bb-path'] = $fileInfo['path'];
			$fileInfo['path'] = buildPath(CANVAS_CONTENT_COLLECTION_PATH, $fileInfo['bb-path']);
			if ($fileInfo) {
				$file = uploadCanvasFile(basename($ccFilename), dirname($ccFilename), $fileInfo, $course);
				foreach($fileInfo as $key => $value) {
					$file["bb-$key"] = $value;
				}
				$file[CANVAS_SMART_URL] = "/courses/{$course['id']}/files/{$file['id']}";
				$fileXml = addElementToReceipt($course[MANIFEST], NODE_FILE, $ccXml);
				appendCanvasResponseToReceipt($fileXml, $file);
				$contentCollection[$file['bb-xid']] = $file;
			} elseif (!is_dir($ccFilename)) { // ham-handed way to exclude the csfiles\/ directory
				displayError($ccFilename, false, 'Missing LOM Information', "A file ($ccFilename) was missing its LOM (Learning Object Metadata) file and is therefore lost to the content collection.");
				exit;
			}
		}
	}
	return $contentCollection;
}

/**
 * process gradebook and return an associative array of parsings
 **/
function processGradebook($course) {

	// TODO: do something creative with the embedded grading scheme <SCALES> -- Canvas has no API access to grading schemes
	if ($xpathResult = $course[MANIFEST]->xpath("//resource[@type='course/x-bb-gradebook']")) {
		$gradebookXml = $xpathResult[0];
		$gradebookResourceFile = getBbResourceFile($gradebookXml);
		if ($xpathResult = $gradebookResourceFile->xpath('//categories/category')) {
			$gradebook = array();
			foreach($xpathResult as $categoryXml) {
				$gradebook = array_merge($gradebook, createCanvasAssignmentGroupAndAssignments($gradebookResourceFile, $categoryXml, $course));
			}
		}

		/* some assignments may not be assigned to a category? how could this be? I have no idea. None. */
		$gradebook = array_merge($gradebook, createCanvasAssignmentGroupAndAssignments($gradebookResourceFile, null, $course));
		return $gradebook;
	}
	return false;
}

/**
 * create all of the assignments in a particular category/assignment group
 **/
function createCanvasAssignmentGroupAndAssignments($gradebookXml, $categoryXml, $course) {
	$xid = '';
	$title = 'Uncategorized Assignments';
	if ($categoryXml) {
		$xid = getBbXid($categoryXml);
		$title = getBbTitle($categoryXml);
	} else {
		$categoryXml = new SimpleXMLElement('<category id=""><title value="' . $title . '"/><description/><isuserdefined value="true"/><iscalculated value="false"/><isscorable value="false"/></category>');
	}
	
	$gradebookCategory = array();
	
	$xpathResult = $gradebookXml->xpath("//outcomedefinition[categoryid[@value='$xid'] and iscalculated[@value='false'] and not(score_provider_handle[@value='resource/x-bb-assessment'])]");
	if ($xpathResult) {
		$assignmentGroup = createCanvasAssignmentGroup($categoryXml, $course);
		foreach ($xpathResult as $assignmentXml) {
			$scoreProviderHandle = getBbScoreProviderHandle($assignmentXml);
			switch ($scoreProviderHandle) {
				case 'resource/x-bb-assessment': {
					// TODO: Canvas has no API access to create quiz questions
					break;
				}
				case 'resource/x-bb-assignment':
				default: {
					$gradebookCategory[getBbResourceFileName($assignmentXml)] = createCanvasAssignment(
						$assignmentXml,
						getBbResourceFile($assignmentXml),
						$course,
						$gradebookXml,
						$assignmentGroup
					);
					break;
				}
			}
		}
	}
	return $gradebookCategory;
}

/**
 * process a module and update XML with notes for import
 **/
function processItem($itemXml, $course, $module, $indent = 0, $breadcrumbs = '') {
	$resXml = getBbResourceFile($itemXml);
	$itemXml->addAttribute(prependNamespace(ATTRIBUTE_INDENT), $indent, CANVAS_NAMESPACE_URI);
	
	$contentHandler = getBbContentHandler($resXml);
	switch ($contentHandler) {
		case 'resource/x-bb-assignment': {
			createCanvasModuleItem(
				$itemXml,
				CANVAS_ASSIGNMENT,
				getCanvasIndentLevel($itemXml),
				$course[GRADEBOOK][getBbResourceFileName($itemXml)], 
				$course,
				$module); 
			break;
		}
		
		case 'resource/x-bb-externallink': {
			$text = getBbText($resXml);
			if (strlen($text)) {
				createCanvasModuleItem(
					$itemXml,
					CANVAS_PAGE,
					getCanvasIndentLevel($itemXml),
					createCanvasPage($itemXml, $resXml, $course),
					$course,
					$module
				);
			} else {
				createCanvasModuleItem(
					$itemXml,
					CANVAS_EXTERNAL_URL,
					getCanvasIndentLevel($itemXml),
					array(
						'title' => getBbTitle($resXml),
						'url' => getBbUrl($resXml),
					),
					$course,
					$module
				);
			}
			break;
		}
		
		case 'resource/x-bb-asmt-survey-link': {
			// TODO: Canvas has no API access to create quiz questions
			break;
		}
		
		case 'resource/x-bb-folder':
		case 'resource/x-bb-lesson': {
			$subheader = createCanvasModuleSubheader($itemXml, $resXml, $course, $module);
			$subitemsXml = $itemXml->item;
			if ($subitemsXml) {
				foreach ($subitemsXml as $subitemXml) {
					// TODO: actually make use of the breadcrumbs!
					processItem($subitemXml, $course, $module, $indent + 1,
						$breadcrumbs . (strlen($breadcrumbs) ? BREADCUMB_SEPARATOR : '') . $subheader['title']
					);
				}
			}
			break;
		}
		
		case 'resource/x-bb-vclink': {
			// TODO: Canvas has no API access to create a conference
			break;
		}
		
		case 'resource/x-bb-courselink': // will have link added to the page text at the end of processManifest()
		case 'resource/x-bb-file':
		case 'resource/x-bb-document': {
			$text = getBbText($resXml);
			$fileAttachmentCount = getBbFileAttachmentCount($resXml);
			if (strlen($text) == 0 && $fileAttachmentCount == 1) {
				$attachments = uploadCanvasFileAttachments($itemXml, $resXml, $course);
				$keys = array_keys($attachments);
				$attachments[$keys[0]][Bb_ITEM_TITLE] = getBbTitle($resXml);
				createCanvasModuleItem(
					$itemXml,
					CANVAS_FILE,
					getCanvasIndentLevel($itemXml),
					$attachments[$keys[0]],
					$course,
					$module
				);
			} else {
				createCanvasModuleItem(
					$itemXml,
					CANVAS_PAGE,
					getCanvasIndentLevel($itemXml),
					createCanvasPage($itemXml, $resXml, $course, $module), 
					$course,
					$module);
			}
			break;
		}
		
		default: {
			createCanvasNoImport($itemXml);
			break;
		}
	}
}

/**
 * process and post announcements
 **/
function processAnnouncements($course) {
	if ($xpathResult = $course[MANIFEST]->xpath('//resource[@type=\'resource/x-bb-announcement\']')) {
		foreach($xpathResult as $announcementXml) {
			$announcement = createCanvasAnnouncement($announcementXml, getBbResourceFile($announcementXml), $course);
		}
	}
	return false;
}

/**
 * process and append course links
 **/
function processCourseLinks($course) {
	if ($xpathResult = $course[MANIFEST]->xpath("//resource[@type='resource/x-bb-link']")) {
		foreach($xpathResult as $courseLinkXml) {
			//appendCourseLink ($courseLinkXml);
		}
	}
}

/***********************************************************************
 *                                                                     *
 * Blackboard (Bb) Functions                                           *
 *                                                                     *
 ***********************************************************************/

/**
 * Extract the name of an item's resource file
 **/
function getBbResourceFileName($node) {
	if (is_object($node)) {
		switch ($node->getName()) {
			case 'item':
				return (string) $node->attributes()->identifierref;
				
			case 'resource':
				return $node->attributes()->identifier;
				
			case 'outcomedefinition':
				if (getBbScoreProviderHandle($node) == 'resource/x-bb-assessment') {
					return (string) $node->asidataid->attributes()->value;
				}
				return (string) $node->contentid->attributes()->value;
		}
	}
	return false;
}

/**
 * Return the filename of the resource file that accompanies this item in the
 * archive (as a SimpleXML Element)
 **/
function getBbResourceFile($node) {
	return loadFileAsSimpleXmlWithLowercaseNodesAndAttributes(buildPath(getWorkingDir(), getBbResourceFileName($node) . Bb_RES_FILE_EXT));
}

/**
 *  Extract the contenthandler mimetype from a res00000 file as text
 **/
function getBbContentHandler($node) {
	if ($xpathResult = $node->xpath('//contenthandler')) {
		return (string) $xpathResult[0]->attributes()->value;
	}

	return false;
}

/**
 * Extract the label text from a res00000 file as text
 **/
 // TODO: this could really just be another special case of getBbTitle()
function getBbLabel($node) {
	if ($xpathResult = $node->xpath('/coursetoc/label')) {

		/* strip off Bb default name tagging */
		$label = preg_replace('|COURSE_DEFAULT\.(.*)\.\w+\.label|i', '\\1', (string) $xpathResult[0]->attributes()->value);
		
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
function getBbTitle($node) {
	if (is_object($node)) {
		switch($node->getName()) {
			case 'questestinterop': {
				if ($title = $node->assessment->attibutes()->title) {
					return $title;
				}
				break;
			}
			case 'category': {
				if ($title = (string) $node->title->attributes()->value) {
					return $title;
				}
				break;
			}
			case 'course': {
				if ($xpathResult = $node->xpath('/course/title')) {
					return (string) $xpathResult[0]->attributes()->value;
				}
				break;
			}
			default: {
				if ($xpathResult = $node->xpath('//title')) {
					return (string) $xpathResult[0]->attributes()->value;	
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
function getBbUrl($node) {
	if ($xpathResult = $node->xpath('//url')) {
		return (string) $xpathResult[0]->attributes()->value;
	}
		
	return false;
}

/**
 * Extract content item text from a res00000 file as html
 **/
function getBbText($node) {
	if ($xpathResult = $node->xpath('//text')) {
		$text = str_replace(array('&lt;', '&gt;'), array('<', '>'), (string) $xpathResult[0]);
		
		return $text;
	}
	
	return false;
}

/**
 *  Extract the number of files attached to this item
 **/
function getBbFileAttachmentCount($node) {
	if ($xpathResult = $node->xpath('//files/file')) {
		return count($xpathResult);
	}
	return 0;
}



/**
 * Extract the name of the first file attachment
 **/
function getBbFileAttachmentName($node) {
	if ($xpathResult = $node->xpath('//file/name')) {
		return (string) $xpathResult[0];
	}
	return false;
}

/**
 * Extract the listed size of the first file attachment
 **/
function getBbFileAttachmentSize($node) {
	if ($xpathResult = $node->xpath('//file/size')) {
		return (string) $xpathResult[0]->attributes()->value;
	}
	return false;
}

/**
 * Extract the XID, path and filename from a <lom> file accompanying a file
 * from the Content Collection, return as an associative array
 **/
function getBbLomFileInfo($fileName) {
	if ($lomXml = loadFileAsSimpleXmlWithLowercaseNodesAndAttributes("$fileName.xml")) {
		$identifier = (string) $lomXml->relation->resource->identifier;
		if ($identifier) {
			$fileInfo['xid'] = preg_replace('|^([_0-9]+)#.*|', '$1', $identifier);
			$fileInfo['path'] = preg_replace("|^{$fileInfo['xid']}#(.*)|", '\\1', $identifier);
			$fileInfo['name'] = basename($fileInfo['path']);
			$fileInfo['path'] = dirname($fileInfo['path']);
			return $fileInfo;
		}
	}
	return false;
}

/**
 * Extract a course id from the course settings file
 **/
function getBbCourseId($node) {
	if ($xpathResult = $node->xpath('/course/courseid')) {
		return (string) $xpathResult[0]->attributes()->value . ' (Imported ' . date('Y-m-d h:i:s A') . ')';
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
function getBbCourseStart($node) {
	if ($xpathResult = $node->xpath('//coursestart')) {
		$bbTimestamp = (string) $xpathResult[0]->attributes()->value;
		if (strlen($bbTimestamp)) {
			return convertBbTimeStampToCanvasTimeStamp();
		}
	}
	return false;
}

/**
 * Extract course end date and time
 **/
function getBbCourseEnd($node) {
	if ($xpathResult = $node->xpath('//courseend')) {
		$bbTimestamp = (string) $xpathResult[0]->attributes()->value;
		if (strlen($bbTimestamp)) {
			return convertBbTimeStampToCanvasTimeStamp();
		}
	}
	return false;
}

/**
 * Extract restricted start timestamp
 **/
function getBbRestrictStart($node) {
	if ($xpathResult = $node->xpath('//restrictstart')) {
		return convertBbTimeStampToCanvasTimeStamp((string) $xpathResult[0]->attributes()->value);
	}
	return false;
}

/**
 * Extract assignment due date timestampe
 **/
function getBbDue($node) {
	if ($dueXml = $node->dates->due) {
		return convertBbTimeStampToCanvasTimeStamp((string) $dueXml->attributes()->value);
	}
	return false;
}

/**
 * Extract points possible for an assignment
 **/
function getBbPointsPossible($node) {
	if ($pointsPossibleXml = $node->pointspossible) {
		return (string) $pointsPossibleXml->attributes()->value;
	}
	return false;
}

/**
 * Extract outcome scale title for an assignment
 **/
function getBbScaleType($node) {
	if ($scaleIdXml = $node->scaleid) {
		$scaleId = (string) $scaleIdXml->attributes()->value;
		if ($xpathResult = $node->xpath("//scale[@id='$scaleId']/type")) {
			return (string) $xpathResult[0]->attributes()->value;
		}
	}
}

/**
 * Extract outcome position
 **/
function getBbPosition($node) {
	if ($positionXml = $node->position) {
		return (string) $positionXml->attributes()->value;
	}
}

/**
 * Extract the course description
 **/
function getBbCourseDescription($node) {
	if ($courseDescription = (string) $node->description) {
		return $courseDescription;
	}
	return false;
}

/**
 * Extract the Bb XID from an object
 **/
function getBbXid($node) {
	if ($xid = (string) $node->attributes()->id) {
		return $xid;
	}
	return false;
}

/**
 * Extract XID value from a string
 **/
function parseBbXid($string) {
	/* there is an explicitly identified XID */
	if (preg_match('|^/xid-([_0-9]+)|i', $string, $matches)) {
		return $matches[1];
		
	/* ...or the string is just an XID entirely */
	} elseif (preg_match('|^[_0-9]+$|', $string)) {
		return $string;
	}
	return false;
}

/**
 * Extract ISCALCULATED value from an assignment
 **/
function getBbIsCalculated($node) {
	if ($isCalculated = (string) $$node->iscalculated->attributes()->value) {
		return $isCalculated;
	}
	return false;
}

/**
 * Extract ScoreProviderHandle from an assignment
 **/
function getBbScoreProviderHandle($node) {
	if ($scoreProviderHandle = (string) $node->score_provider_handle->attributes()->value) {
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
function appendAttachmentLinks($itemXml, $resXml, $course, $text) {
	if ($attachments = uploadCanvasFileAttachments($itemXml, $resXml, $course)) {
		$text .= '<div id="bb_file_attachments">' . FILE_ATTACHMENT_PREFIX;
		foreach ($attachments as $attachment) {
			$text .= "<div class=\"bb_file_attachment\"><a class=\"instructure_scribd_link instructure_file_link\" href=\"{$attachment[CANVAS_SMART_URL]}\">" . $attachment['display_name'] .'</a><div>'; 
		}
		$text .= FILE_ATTACHMENT_SUFFIX . '</div>';
	}
	return $text;
}

/**
 * Scan through the body text and replace Blackboard embed codes with links
 * to Canvas files
 **/
function relinkEmbeddedLinks($itemXml, $resXml, $course, $text) {
	/* links to attached files */
	if (preg_match_all('|@X@EmbeddedFile\.location@X@([^"]+)|', $text, $embeddedAttachments, PREG_SET_ORDER)) {
		foreach($embeddedAttachments as $embeddedAttachment) {
			$fileName = urldecode($embeddedAttachment[1]);
			
			/* is it something that we've already uploaded as an attachment? */
			$itemXml->registerXPathNamespace(CANVAS_NAMESPACE_PREFIX, CANVAS_NAMESPACE_URI);
			if ($xpathResult = $itemXml->xpath("//file/@" . prependNamespace('display_name') . "='$fileName'")) {
				$attachmentId = (string) $xpathResult[0]->attributes(CANVAS_NAMESPACE_PREFIX, true)->id;
				$text = str_replace($embeddedAttachment[0], "/courses/{$course['id']}/files/$attachmentId", $text);
			
			/* or is it something that we now need to upload? */
			} else {
				$localFilePath = buildPath(getWorkingDir(), getBbResourceFileName($itemXml)) . '\\\\' . Bb_EMBED_DIR . '\\\\';
				$localFiles = glob("$localFilePath*");
				$attachments = array();

				$filesXml = addElementToReceipt($course[MANIFEST], NODE_EMBEDDED_FILES, $itemXml);
				$file = null;
				foreach ($localFiles as $localFile) {
					$fileInfo['name'] = $fileName;
					
					/* we get lucky and file names match*/
					if ($fileName == basename($localFile)) {
						$file = uploadCanvasFile(basename($localFile), dirname($localFile), $fileInfo, $course);
												
					/* Bb hosed the name and left no record, so we hope there's only one embedded file */
					} elseif (count($localFiles) == 1) {
						$fileInfo['filesystem-name'] = basename($localFile);
						$fileInfo['import-match-rationale'] = 'based on a single file attachment being available';
						$file = uploadCanvasFile(basename($localFile), dirname($localFile), $fileInfo, $course);
					
					/* Bb hosed the name and left no record, but there are a bunch of embedded files, so we try to match by file extension */
					} elseif (pathinfo($localFile, PATHINFO_EXTENSION) == pathinfo($fileName, PATHINFO_EXTENSION)) {								
						$fileInfo['filesystem-name'] = basename($localFile);
						$fileInfo['import-match-rationale'] = 'based on file size and extension';
						$file = uploadCanvasFile(basename($localFile), dirname($localFile), $fileInfo, $course);
					}
					
					if ($file) {
						foreach($fileInfo as $key => $value) {
							$file["bb-$key"] = $value;
						}
						$fileXml = addElementToReceipt($course[MANIFEST], NODE_FILE, $filesXml);
						appendCanvasResponseToReceipt($fileXml, $file);
						break;
					}
				}
				if ($file) {
					$text = str_replace($embeddedAttachment[0], "/courses/{$course['id']}/files/{$file['id']}", $text);
				} else {
					debug_log("Missing embedded file '$fileName' in item " . getBbXid($itemXml));
				}
			}
		}
		
	/* links to content collection items */
	} elseif (preg_match_all('|@X@EmbeddedFile\.requestUrlStub@X@bbcswebdav/xid-([_0-9]+)|', $text, $embeddedContentCollectionAttachments, PREG_SET_ORDER)) {
		foreach($embeddedContentCollectionAttachments as $embeddedAttachment) {
			$xid = $embeddedAttachment[1];
			if (isset($course[CONTENT_COLLECTION][$xid])) {
				$text = str_replace($embeddedAttachment[0], "/courses/{$course['id']}/files/" . $course[CONTENT_COLLECTION][$xid]['id'], $text);
			} else {
				// TODO: generate a wiki page and link to this for HREFs, standard graphic for SRC (or something like that)
				debug_log("Missing item $xid, embedded in " . getBbXid($resXml));
			}
		}
		
	/* miscellaneous internal links -- humorously including /, which will now redirect to Canvas! */
	} elseif (preg_match_all('|@X@EmbeddedFile\.requestUrlStub@X@([^"]+)|', $text, $embeddedBbLinks, PREG_PATTERN_ORDER)) {
		$text = str_replace($embeddedBbLinks[0], $embeddedBbLinks[1], $text);
	} elseif (preg_match_all('|@X@[^"]*|', $text, $unmatchedEmbedCodes)) {
		debug_log(count($unmatchedEmbedCodes) . " unmatched embed codes in " . getBbXid($itemXml));
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
function getCanvasIndentLevel($node) {
	$indent = (string) $node->attributes(CANVAS_NAMESPACE_PREFIX, true)->indent;
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
		
		/* $course[] not included in receipt because we need to process the manifest
		   first -- will be added in processCourseSettings() */
		
		return $course;
	}
	return false;
}

/**
 * Upload a file to Canvas, returning the file information as an associative
 * array
 **/
// TODO: It would be faster to figure out a way to do this asynchronously
function uploadCanvasFile($fileName, $localPath, &$fileInfo, $course) {
	/* stage local file for upload */
	/* Nota bene: Canvas needs the file extension to confirm the mimetype in the API request! */
	$stageName = md5($fileName . time()) . '.' . pathinfo($fileName, PATHINFO_EXTENSION);
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
			debug_log('AWS API server error. ' . $e->getMessage() . ' Retrying.');
		} catch (Exception $e) {
				displayError($e->getMessage(), false, 'Upload Failed', "A status check on a file upload ($fileName) failed.");
				exit;
			}
			$uploadProcess = json_decode($json, true);
		}
		
		if ($uploadProcess['upload_status'] == 'ready') {
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
function uploadCanvasFileAttachments($itemXml, $resXml, $course) {
	$localFilePath = buildPath(getWorkingDir(), getBbResourceFileName($itemXml)) . '\\\\';
	$localFiles = glob("$localFilePath*");
	$attachments = array();
	if ($xpathResult = $resXml->xpath('//files/file')) {
		$filesXml = $itemXml->addChild(prependNamespace(NODE_ATTACHMENTS), null, CANVAS_NAMESPACE_URI);
		foreach ($xpathResult as $attachmentXml) {
			// TODO: it would require less fiddling in processItems() if this was a wrapper for a uploadCanvasFileAttachment() (singular!)
			$file = null;
			$fileInfo = array(
				'name' => getBbFileAttachmentName($attachmentXml),
				'size' => getBbFileAttachmentSize($attachmentXml),
			);
			
			/* we get lucky and file names match*/
			if (($i = array_search("$localFilePath{$fileInfo['name']}", $localFiles)) !== false) {
				$file = uploadCanvasFile(basename($localFiles[$i]), dirname($localFiles[$i]), $fileInfo, $course);
				
			/* it's in the content collection, and we look it up by XID */
			} elseif ($xid = parseBbXid($fileInfo['name'])) {
				if (isset($course[CONTENT_COLLECTION][$xid])) {
					$file = $course[CONTENT_COLLECTION][$xid];
				} else {
					debug_log("$xid was not found in the content collection, attached to " . getBbXid($resXml));
				}
				
			/* Bb hosed the name and left no record, so we hope there's only one attachment */
			} elseif (getBbFileAttachmentCount($resXml) == 1) {
				$fileInfo['filesystem-name'] = basename($localFiles[0]);
				$fileInfo['import-match-rationale'] = 'based on a single file attachment being available';
				$file = uploadCanvasFile(basename($localFiles[0]), dirname($localFiles[0]), $fileInfo, $course);
			
			/* Bb hosed the name and left no record, but there are a bunch of attachments, so we try to match by size and file extension */
			} else { // hoo boy... here we go!
				foreach ($localFiles as $localFile) {
					if (filesize($localFile) == $fileInfo['size'] &&
						pathinfo($localFile, PATHINFO_EXTENSION) == pathinfo($fileInfo['name'], PATHINFO_EXTENSION)) {
						
						$fileInfo['filesystem-name'] = basename($localFile);
						$fileInfo['import-match-rationale'] = 'based on file size and extension';
						$file = uploadCanvasFile(basename($localFile), dirname($localFile), $fileInfo, $course);
						break;
					}
				}
			}
			
			if ($file) {
				foreach ($fileInfo as $key => $value) {
					$file["bb-$key"] = $value;
				}
				$file[CANVAS_SMART_URL] = "/courses/{$course['id']}/files/{$file['id']}/download?wrap=1";
				$attachments[$file['display_name']] = $file;
				$fileXml = addElementToReceipt($course[MANIFEST], NODE_FILE, $filesXml);
				appendCanvasResponseToReceipt($fileXml, $file);

				break;
			} else {
				// TODO: this generates a new page for each broken link to the same file -- it would be more elegant to link to one page for all broken links to the same file
				$linkName = (string) $attachmentXml->linkname->attributes()->value;
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
		displayError(
			$json,
			false,
			'Could Not Create Course',
			'Something went wrong and we could not create the target course in Canvas.'
		);
		exit;
	}
	
	/* entered into import receipt in processCourseSettings() */
	
	return $course;
}

/**
 * Create Canvas module, returning the JSON result as an associative array
 **/
$MODULE_POSITION = 0;
function createCanvasModule($itemXml, $resXml, $course) {
	
	$label = getBbLabel($resXml);
	
	$json = callCanvasApi('post', "/courses/{$course['id']}/modules",
		array (
			'module[name]' => $label,
			'module[position]' => ++$GLOBALS['MODULE_POSITION']
		)
	);
	
	$module = json_decode($json, true);
	$module[ATTRIBUTE_CANVAS_IMPORT_TYPE] = CANVAS_MODULE;
	appendCanvasResponseToReceipt($itemXml, $module);
	
	return $module;
}

function createCanvasModuleSubheader($itemXml, $resXml, $course, $module) {
	$json = callCanvasApi('post', "/courses/{$course['id']}/modules/{$module['id']}/items",
		array (
			'module_item[title]' => getBbTitle($resXml),
			'module_item[type]' => 'SubHeader',
			'module_item[position]' => nextModuleItemPosition($module['id']),
			'module_item[indent]' => getCanvasIndentLevel($itemXml)
		)
	);
	
	$moduleItem = json_decode($json, true);
	$moduleItem[ATTRIBUTE_CANVAS_IMPORT_TYPE] = CANVAS_SUBHEADER;
	appendCanvasResponseToReceipt($itemXml, $moduleItem);
	
	return $moduleItem;
}

function createCanvasModuleItem($itemXml, $moduleItemType, $indent, $canvasItemArray, $course, $module) {
	/* there really can't be a "default" title... */
	$title = null;
	
	/* ...but we know that most things will have content_ids */
	$referenceName = 'module_item[content_id]';
	
	/* ...and presumably an id -- although pages won't, so we check first */
	$referenceValue = (isset($canvasItemArray['id']) ? $canvasItemArray['id'] : null);
	
	/* try to get the "real" values for title and reference */
	switch ($moduleItemType) {
		case CANVAS_PAGE: {
			$title = $canvasItemArray[Bb_ITEM_TITLE];
			$referenceName = 'module_item[page_url]';
			$referenceValue = $canvasItemArray['url'];
			break;
		}
		case CANVAS_FILE: {
			$title = $canvasItemArray['display_name'];
			break;
		}
		case CANVAS_ASSIGNMENT: {
			$title = $canvasItemArray['name'];
			break;
		}
		case CANVAS_EXTERNAL_URL: {
			$title = $canvasItemArray['title'];
			$referenceName = 'module_item[external_url]';
			$referenceValue = $canvasItemArray['url'];
			break;
		}
		case CANVAS_FILE: {
			$title = $canvasItemArray[Bb_ITEM_TITLE];
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
	$moduleItem[ATTRIBUTE_CANVAS_IMPORT_TYPE] = CANVAS_MODULE_ITEM;
	appendCanvasResponseToReceipt($itemXml, $moduleItem);
	
	return $moduleItem;
}

/**
 * Create Canvas Page, returning an associative array;
 **/
function createCanvasPage($itemXml, $resXml, $course) {
	$title = getBbTitle($resXml);
	if (preg_match('|[0-9a-z]|i', $title) == false) {
		$canvasTitle = getBbXid($resXml);
	} else {
		$canvasTitle = $title;
	}
	$text = "<h2>$title</h2>\n" . getBbText($resXml); // Canvas filters out <h1>
	$text = appendAttachmentLinks($itemXml, $resXml, $course, $text);
	$text = relinkEmbeddedLinks($itemXml, $resXml, $course, $text);

	
	/* there may be some additional body text to add, depending on mimetype */
	$contentHandler = getBbContentHandler($resXml);
	switch($contentHandler) {
		case 'resource/x-bb-file':
		case 'resource/x-bb-document': {
			break;			
		}
		
		case 'resource/x-bb-externallink': {
			$text .= '<h3><a href="' . getBbUrl($resXml) . "\">$title</a></h3>";
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

	$page[ATTRIBUTE_CANVAS_IMPORT_TYPE] = CANVAS_PAGE;
	$pageXml = addElementToReceipt($course[MANIFEST], NODE_PAGE, $itemXml);
	addElementToReceipt($course[MANIFEST], NODE_TITLE, $pageXml, $page['title']);
	addElementToReceipt($course[MANIFEST], NODE_TEXT, $pageXml, $page['body'], true);
	appendCanvasResponseToReceipt($pageXml, $page);
	
	return $page;
}

/**
 * Create an Assignment in a module, returning the JSON result of the module
 * item as an associate array
 **/
function createCanvasAssignment($itemXml, $resXml, $course, $gradebookXml, $assignmentGroup) {
	if ($resXml) {
		$title = getBbTitle($resXml);
		$text = getBbText($resXml);

		/* remove Bb assignment internals */
		$text = substr($text, 0, strpos($text, '<!--BB ASSIGNMENT INTERNALS: SKIP REST-->'));
		
		$text = relinkEmbeddedLinks($itemXml, $resXml, $course, $text);
		$text = appendAttachmentLinks($itemXml, $resXml, $course, $text);
	} else { /* oy, those uncategorized assignments are wonky! */
		$title = (string) $itemXml->title->attributes()->value;
		$text = (string) $itemXml->description->text;
	}

	$gradingType = 'points';
	switch (getBbScaleType($itemXml)) {
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
			/* I'm not 100% confident that all tabular grades are necessarily equiavlent
			   to letter grades... but they all are in my test set */
			$gradingType = 'letter_grade';
			break;
		}
		case 'TEXT': {
			// TODO: This will be an option in Canvas in Q3 or Q4 2013, we think
		}
	}
	
	$json = callCanvasApi('post', "/courses/{$course['id']}/assignments",
		array(
			'assignment[name]' => $title,
			//'assignment[position]' => getBbPosition($itemXml), // TODO: doesn't seem to "take" in Canvas if position is more than the current number of items -- need to sort by position and add in order
			'assignment[submission_types]' => '["online_upload"]',
			'assignment[points_possible]' => getBbPointsPossible($itemXml),
			'assignment[grading_type]' => $gradingType,
			'assignment[due_at]' => getBbDue($itemXml),
			'assignment[description]' => $text,
			'assignment[assignment_group_id]' => $assignmentGroup['id'],
			'assignment[published]' => 'true'
		)
	);
	
	$assignment = json_decode($json, true);
	
	/* find the assignments tag (or create it, if it hasn't yet been created) */
	// TODO: should group by assignment group!
	$assignment[ATTRIBUTE_CANVAS_IMPORT_TYPE] = CANVAS_ASSIGNMENT;
	$assignmentXml = addElementToReceipt($course[MANIFEST], NODE_ASSIGNMENT, array('name' => NODE_ASSIGNMENTS));
	addElementToReceipt($course[MANIFEST], NODE_TITLE, $assignmentXml, $assignment['name']);
	addElementToReceipt($course[MANIFEST], NODE_TEXT, $assignmentXml, $assignment['description'], true);
	appendCanvasResponseToReceipt($assignmentXml, $assignment);

	return $assignment;
}

/**
 * Create a Canvas assignment group
 **/
function createCanvasAssignmentGroup($itemXml, $course) {
	$json = callCanvasApi('post', "/courses/{$course['id']}/assignment_groups",
		array(
			// TODO: name needs to include grading period
			'name' => str_replace('.name', '', getBbTitle($itemXml))
		)
	);
	
	$assignmentGroup = json_decode($json, true);
	
	$assignmentGroup[ATTRIBUTE_CANVAS_IMPORT_TYPE] = CANVAS_ASSIGNMENT_GROUP;
	$assignmentGroupXml = addElementToReceipt($course[MANIFEST], NODE_ASSIGNMENT_GROUP, array('name' => NODE_ASSIGNMENTS));
	appendCanvasResponseToReceipt($assignmentGroupXml, $assignmentGroup);
	
	return $assignmentGroup;
}

/**
 * Create an announcement in Canvas, returning the associative array
 * describing it
 **/
function createCanvasAnnouncement($itemXml, $resXml, $course) {
	/* can't seem to change the post date in canvas... ugh */
	$postDate = getBbRestrictStart($resXml);
	$title = getBbTitle($resXml);
	$date = date_create_from_format(CANVAS_TIMESTAMP_FORMAT, $postDate);
	$title .= date_format($date, ' (n/j/Y \a\t g:i A)');
	
	$text = getBbText($resXml);
	$text = appendAttachmentLinks($itemXml, $resXml, $course, $text);
	$text = relinkEmbeddedLinks($itemXml, $resXml, $course, $text);
	$json = callCanvasApi('post', "/courses/{$course['id']}/discussion_topics",
		array (
			'title' => $title,
			'message' => $text,
			'is_announcement' => 'true'
		)
	);
	
	$announcement = json_decode($json, true);

	$announcementXml = addElementToReceipt($course[MANIFEST], NODE_ANNOUNCEMENT, array('name' => NODE_ANNOUNCEMENTS));
	addElementToReceipt($course[MANIFEST], NODE_TITLE, $announcementXml, $announcement['title']);
	addElementToReceipt($course[MANIFEST], NODE_TEXT, $announcementXml, $announcement['message'], true);
	appendCanvasResponseToReceipt($announcementXml, $announcement);
	
	return $announcement;
}

/**
 * We're not going to import this information (either because there is no
 * matching type, or we're just not ready yet...
 **/
function createCanvasNoImport($itemXml) {
	$receipt[ATTRIBUTE_CANVAS_IMPORT_TYPE] = CANVAS_NO_IMPORT;
	appendCanvasResponseToReceipt($itemXml, $receipt);
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
		appearance: none;
		font-size: 48pt;
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