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

$uploadDir = "/var/www-data/canvas/blackboard-import/";
$workingDir = $uploadDir . "tmp/";
$manifestName = "imsmanifest.xml";

/* defines $canvasApiToken and $canvasInstanceUrl */
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
function stageUpload($uploadDir, $workingDir) {
	if ($_SERVER["REQUEST_METHOD"] == "POST") {
		if (isset($_FILES["BbExportFile"])) {
			if ($_FILES["BbExportFile"]["error"] === UPLOAD_ERR_OK) {
				// ... file was succesfully uploaded, process it
				$uploadFile = $uploadDir . basename($_FILES["BbExportFile"]["name"]);
				move_uploaded_file($_FILES["BbExportFile"]["tmp_name"], $uploadFile);
				$zip = new ZipArchive();
				if ($zipRsrc = $zip->open($uploadFile)) {
					$zip->extractTo($workingDir);
					$zip->close();
					return true;
				} else exitOnError("Unzipping Failed", "The file you uploaded could not be unzipped.");
			} else exitOnError("Upload Error", array("There was an error with your file upload:", "Error " . $_FILES["BbExportFile"]["error"] . ': See the <a href="http://www.php.net/manual/en/features.file-upload.errors.php">PHP Documentation</a> for more information.'));
		} else exitOnError("No File Uploaded");
	}
	return false;
}

/**
 * Strips the labels off of content area titles that indicate that they are
 * the Bb defaults, then expands CamelCased names into separate words and
 * trims any extraneous spaces
 **/
function stripBbCOURSE_DEFAULT($string) {
	return trim(preg_replace("/(.*)([A-Z].*)/", "\\1 \\2", preg_replace("/COURSE_DEFAULT\.(.+)\.[A-Z_]+\.label/", "\\1", $string)));
}

/**
 * Works through a nested list of items in the manifest, indicating the level
 * of nested-ness to the callback function (which expects an item and a level).
 * Depth-first iteration, so you get use callbackDummy() to get a nice tab-
 * indented view of what you've found.
 **/
function recursivelyIterateItems($manifestItem, $callback, $level = 0) {
	call_user_func($callback, $manifestItem->title, $level);
	if (isset($manifestItem->item)) {
		foreach ($manifestItem->item as $item) {
			recursivelyIterateItems($item, $callback, $level + 1);
		}
	}
}

/**
 * A dummy callback function for recursivelyIterateItems() that lets us do
 * a quick print of what we've got from the manifest to see if it "makes sense"
 **/
$manifest_text = "";
function callbackDummy($text, $level) {
	global $manifest_text;
	$manifest_text .= "\n";
	for ($i = 0; $i < $level; $i++) {
		$manifest_text .= "\t";
	}
	$manifest_text .= stripBbCOURSE_DEFAULT($text);
}

/**
 * Parse the XML of the manifest file and prepare a preview for the user before
 * committing to the actual import into Canvas
 **/
function parseManifest($workingDir, $manifestName) {
	$manifestFile = $workingDir . $manifestName;
	if (file_exists($manifestFile)) {
		$manifest = simplexml_load_file($manifestFile);
		
		foreach($manifest->organizations->organization->item as $item) {
			recursivelyIterateItems($item, "callbackDummy");
		}
		
		global $manifest_text;
		echo "<pre>$manifest_text</pre><hr />";
		echo '<pre>';
		print_r($manifest);
		echo '</pre>';
		
		return ($manifest);
	} else exitOnError("Missing Manifest", "The manifest file (imsmanifest.xml) that should have been included in your Blackboard Exportfile cannot be found.");
}

/**
 * Clean out the working directory, make it ready for our next import
 **/
function flushDir($workingDir) {
	$files = glob("$workingDir*");
	foreach($files as $file) {
		if (is_dir($file)) {
			flushDir($file);
			rmdir($file);
		} elseif (is_file($file)) {
			unlink($file);
		}
	}
}

/* are we uploading a file? */
if (isset($_FILES["BbExportFile"])) {
	if (stageUpload($uploadDir, $workingDir)) {
		$manifest = parseManifest($workingDir, $manifestName);
		flushDir($workingDir);
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
			<input type="submit" value="Import File" />
		</form>
		</body>
		</html>';
	exit;
}

?>