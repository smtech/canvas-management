<?php

/*
	The basic workflow:

	1. Upload ZIP archive
	2. Unzip ZIP archive and extract course name, teacher, etc.
	3. Create (or link to existing) Canvas course
	4. Upload items via API
	5. Open Canvas course
	6. Clean out files (when Canvas API calls complete)
*/

define("UPLOAD_DIR", "/var/www-data/canvas/blackboard-import/"); // where we'll store uploaded files
define("WORKING_DIR", UPLOAD_DIR . "tmp/"); // where we'll be dumping temp files (and cleaning up, too!)
$manifestName = "imsmanifest.xml"; // name of the manifest file

define("IMPORT_TYPE", "importType");
define("ATTR_INDENT_LEVEL", "indentLevel");

define("CANVAS_MODULE", "MODULE");
define("CANVAS_FILE", "FILE");
define("CANVAS_PAGE", "PAGE");
define("CANVAS_EXTERNAL_URL", "EXTERNAL_URL");
define("CANVAS_MODULE_ITEM", "MODULE_ITEM");
define("CANVAS_QUIZ", "QUIZ");
define("CANVAS_ASSIGNMENT", "ASSIGNMENT");
define("CANVAS_DISCUSSION", "DISCUSSION");
define("CANVAS_SUBHEADER", "SUBHEADER");
define("CANVAS_ANNOUNCEMENT", "ANNOUNCEMENT");
define("DO_NOT_IMPORT", "NO IMPORT");

/* defines CANVAS_API_TOKEN and CANVAS_INSTANCE_URL */
require_once(".ignore.canvas-authentication.php");

/**
 * A handy little helper function to print a (somewhat) friendly error
 * message and fail out when things get hairy.
 **/
function exitOnError($title, $text = "") {
	echo "
		<html>
		<body>
		<h1>$title</h1>";
	if (is_array($text)) {
		foreach ($text as $line) {
			echo "<p>$line</p>";
		}
	} elseif (strlen($text)) {
		echo "<p>$text</p>";
	}
	echo "
		<p><a href=\"" . $_SERVER["PHP_SELF"] . "\">Try again.</a></p>
		</body>
		</html>";
	exit;
}

/**
 * Handles the actual file uploading and unzipping into the working directory
 **/
function stageUpload() {
	if ($_SERVER["REQUEST_METHOD"] == "POST") {
		if (isset($_FILES["BbExportFile"])) {
			if ($_FILES["BbExportFile"]["error"] === UPLOAD_ERR_OK) {
				// ... file was succesfully uploaded, process it
				$uploadFile = UPLOAD_DIR . basename($_FILES["BbExportFile"]["name"]);
				move_uploaded_file($_FILES["BbExportFile"]["tmp_name"], $uploadFile); // FIXME would be good to create a per-session temp directory
				$zip = new ZipArchive();
				if ($zipRsrc = $zip->open($uploadFile)) {
					$zip->extractTo(WORKING_DIR);
					$zip->close();
					return true;
				} else exitOnError("Unzipping Failed", "The file you uploaded could not be unzipped.");
			} else exitOnError("Upload Error", array("There was an error with your file upload:", "Error " . $_FILES["BbExportFile"]["error"] . ': See the <a href="http://www.php.net/manual/en/features.file-upload.errors.php">PHP Documentation</a> for more information.'));
		} else exitOnError("No File Uploaded");
	}
	return false;
}

/**
 * Is this XML item a Bb application?
 **/
function isBbApplication($item) {
	return preg_match("|COURSE_DEFAULT\..*\.APPLICATION\.label|", $item->title);
}

/**

/**
 * Parse a module and update XML with notes for import
 **/
function parseContentArea($contentArea, $manifest, $indent = -1) {
	foreach ($contentArea->item as $item) {
		if ($indent >= 0) {
			$item->addAttribute(ATTR_INDENT_LEVEL, $indent);
		}
		if (isset($item->item)) { /* if there are subitems, this must be a subheader */
			if ($indent < 0) {
				$item->addAttribute(IMPORT_TYPE, CANVAS_MODULE);
			} else {
				$item->addAttribute(IMPORT_TYPE, CANVAS_SUBHEADER);
			}
			parseContentArea($item, $manifest, $indent + 1);
		} else { /* we're going to have to dig deeper into the res00000.dat file */
			$resFile = WORKING_DIR . $item->attributes()->identifierref . ".dat";
			if (file_exists($resFile)) {
				$res = simplexml_load_file($resFile);
				if ($res->CONTENTHANDLER && $res->CONTENTHANDLER->attributes()) {
					$contentHandler = $res->CONTENTHANDLER->attributes();
					switch ($contentHandler["value"]) {
						case "resource/x-bb-assignment": {
							$item->addAttribute(IMPORT_TYPE, CANVAS_ASSIGNMENT);
							break;
						}
						
						case "resource/x-bb-courselink": {
							$item->addAttribute(IMPORT_TYPE, CANVAS_MODULE_ITEM);
							break;
						}
						
						case "resource/x-bb-externallink": {
							if (strlen((string) $res->body->text)) {
								$item->addAttribute(IMPORT_TYPE, CANVAS_PAGE);
							} else {
								$item->addAttribute(IMPORT_TYPE, CANVAS_EXTERNAL_URL);
							}
							break;
						}
						
						case "resource/x-bb-asmt-survey-link": {
							$item->addAttribute(IMPORT_TYPE, CANVAS_QUIZ);
							break;
						}
						
						case "resource/x-bb-folder":
						case "resource/x-bb-lesson": {
							if ($indent < 0) {
								$item->addAttribute(IMPORT_TYPE, CANVAS_MODULE);
							} else {
								$item->addAttribute(IMPORT_TYPE, CANVAS_SUBHEADER);
							}
							break;
						}
						
						case "resource/x-bb-vclink": {
							$item->addAttribute(IMPORT_TYPE, CANVAS_CONFERENCE);
							break;
						}
						
						case "resource/x-bb-file": {
							if (strlen((string) $res->body->text)) {
								$item->addAttribute(IMPORT_TYPE, CANVAS_PAGE);
							} else {
								$item->addAttribute(IMPORT_TYPE, CANVAS_FILE);
							}
							break;
						}
						
						case "resource/x-bb-document": {
							$item->addAttribute(IMPORT_TYPE, CANVAS_PAGE);
							break;
						}
					}
				} else {
					$item->addAttribute(IMPORT_TYPE, DO_NOT_IMPORT);		
				}
			} else exitOnError("Missing Resource File",array("A resource file containing details about one of your items is missing.", $resFile));
		}
	}
}

/**
 * Parse the XML of the manifest file and prepare a preview for the user before
 * committing to the actual import into Canvas
 **/
function parseManifest($manifestName) {
	$manifestFile = WORKING_DIR . $manifestName;
	if (file_exists($manifestFile)) {
		$manifest = simplexml_load_file($manifestFile);
		
		parseContentArea($manifest->organizations->organization, $manifest);
		
		echo '<pre>';
		print_r($manifest);
		echo '</pre>';
		
	} else exitOnError("Missing Manifest", "The manifest file (imsmanifest.xml) that should have been included in your Blackboard Exportfile cannot be found.");
}

/**
 * Clean out the working directory, make it ready for our next import
 **/
function flushDir($dir) {
	$files = glob("$dir*");
	foreach($files as $file) {
		if (is_dir($file)) {
			flushDir($file);
			rmdir($file);
		} elseif (is_file($file)) {
			unlink($file);
		}
	}
}


/***********************************************************************
 *                                                                     *
 * The main program... whee!                                           *
 *                                                                     *
 ***********************************************************************/

echo "<p><a href=\"{$_SERVER["PHP_SELF"]}\">Restart</a></p>";

/* are we uploading a file? */
if (isset($_FILES["BbExportFile"])) {
	if (stageUpload()) {
		$manifest = parseManifest($manifestName);
		flushDir(WORKING_DIR);
	}
} else {
/* well, it appears that nothing has happened yet, so let's just start with
   a basic file upload form, as an aperitif to the main event... */
	echo '
		<html>
		<body>
		<form enctype="multipart/form-data" action="' . $_SERVER["PHP_SELF"] . '" method="POST">
			<input type="hidden" name="phase" value="1" />
			<!-- 262144000 bytes == 250MB -- if this needs to change, be sure to update the .htaccess file! -->
			<input type="hidden" name="MAX_FILE_SIZE" value="262144000" />
			Import this file: <input name="BbExportFile" type="file" />
			<input type="submit" value="Import File" onsubmit="if (this.getAttribute(\'submitted\')) return false; this.setAttribute(\'submitted\',\'true\');" />
		</form>
		</body>
		</html>';
	exit;
}

?>