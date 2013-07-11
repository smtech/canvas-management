<?php

/*
	1. Upload ZIP archive
	2. Unzip ZIP archive and extract course name, teacher, etc.
	3. Create (or link to existing) Canvas course
	4. Upload items via API
	5. Open Canvas course
	6. Clean out files (when Canvas API calls complete)
*/

$uploadDir = "/var/www-data/canvas/blackboard-import/";
$workingDir = $uploadDir . "tmp/";

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

function unzipUpload() {
	if ($_SERVER["REQUEST_METHOD"] == "POST") {
		if (isset($_FILES["bbzip"])) {
			if ($_FILES["bbzip"]["error"] === UPLOAD_ERR_OK) {
				// ... file was succesfully uploaded, process it
				$uploadFile = $uploadDir . basename($_FILES["bbzip"]["name"]);
				move_uploaded_file($_FILES["bbzip"]["tmp_name"], $uploadFile);
				$zip = new ZipArchive();
				if ($zipRsrc = $zip->open($uploadFile)) {
					$zip->extractTo($workingDir);
					$zip->close();
					return true;
				} else exitOnError("Unzipping Failed", "The file you uploaded could not be unzipped.");
			} else exitOnError("Upload Error", array("There was an error with your file upload:", $_FILES["bbzip"]["error"]));
		} else exitOnError("No File Uploaded");
	}
	return false;
}

function parseManifest() {
	$manifestFile = $workingDir . "imsmanifest.xml";
	if (file_exists($manifestFile)) {
		$manifest = simplexml_load_file($workingDir . "imsmanifest.xml");
		print_r($manifest);
	} else exitOnError("Missing Manifest", "The manifest file (imsmanifest.xml) that should have been included in your Blackboard Exportfile cannot be found.");
}

$phase = (isset($_REQUEST["phase"]) ? $_REQUEST["phase"] : NULL);

switch ($phase) {
	
	case 1: { // uploaded the ZIP archive
		if (unzipUpload()) {
			parseManifest();
		}
		break;
	}
	
	default: { // we haven't started the workflow yet
		echo '
			<html>
			<body>
			<form enctype="multipart/form-data" action="' . $_SERVER["PHP_SELF"] . '" method="POST">
				<input type="hidden" name="phase" value="1" />
				<input type="hidden" name="MAX_FILE_SIZE" value="30000" />
				Send this file: <input name="bbzip" type="file" />
				<input type="submit" value="Send File" />
			</form>
			</body>
			</html>';
		exit;
	}
}

?>