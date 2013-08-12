<?php

/* what Canvas API user are we going to connect as? */
require_once('../.ignore.read-only-authentication.inc.php');

/* configurable options */
require_once('config.inc.php');

/* handles HTML page generation */
require_once('../page-generator.inc.php');

/* handles the core of the Canvas API interactions */
require_once('../canvas-api.inc.php');

/* handles working directory functions */
require_once('../working-directory.inc.php');

/* we do directly work with Pest on some AWS API calls */
require_once('../Pest.php');

// TODO: implement an import as well as an export!

if (isset($_REQUEST['organizational_unit_url'])) {
	debugFlag('START', getWorkingDir());
	$path = parse_url($_REQUEST['organizational_unit_url'], PHP_URL_PATH);
	$path = preg_replace('|(accounts/\d+/)?(\w+/\d+).*|', '$2', $path);
	$discussionTopics = calLCanvasApi(CANVAS_API_GET, "$path/discussion_topics",
		array(
			'per_page' => '50'
		)
	); // FIXME: this doesn't really take into account pagination...
	for ($i = 0; $i < count($discussionTopics); $i++) {
		$topicEntries = callCanvasApi(CANVAS_API_GET, "$path/discussion_topics/{$discussionTopics[$i]['id']}/entries",
			array(
				'per_page' => '50'
			)
		); // FIXME: this doesn't really take into account pagination...
		if (count($topicEntries)) {
			$discussionTopics[$i]['entries'] = $topicEntries;
		}
	}
	$jsonExport = json_encode($discussionTopics);
	$organizationalUnit = callCanvasApi(CANVAS_API_GET, $path);
	$fileName = buildPath(getWorkingDir(), date(TIMESTAMP_FORMAT) . preg_replace('|[^\w _]+|', '-', $organizationalUnit['name']) . NAME_SEPARATOR . (INCLUDE_ORGANIZATIONAL_UNIT_ID ? $organizationalUnit['id'] . NAME_SEPARATOR : '') . FILE_NAME . '.json');
	file_put_contents($fileName, $jsonExport);
	
	/* send download to user */
	$filePointer = fopen($fileName, 'r');
	header('Content Description: File Transfer');
	header('Content-Type: ' . mime_content_type($fileName));
	header('Content-Disposition: attachment; filename=' . basename($fileName));
	header('Content-Transfer-Encoding: binary');
	header('Expires: 0');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: public');
	fpassthru($filePointer);
	fclose($filePointer);
	
	/* clean up */
	flushDir(getWorkingDir());
	rmdir(getWorkingDir());
	debugFlag('FINISH');
	exit;
	
} else {
	/* get URL for the organizational unit whose discussions we are archiving */
	displayPage('
	<form method="post" action="' . $_SERVER['PHP_SELF'] . '">
		<lable for="organizational_unit_url">Course, Group or Collection URL <span class="comment">The web URL for the course, group or collection whose discussions you wish to archive.</span></label>
		<input id="organizational_unit_url" name="organizational_unit_url" type="text" />
		<input type="submit" value="Download JSON Archive" />
	</form>
	');
	exit;
}

// FIXME: something is wonky with the Canvas permissions -- I got this to work by giving my API user all privileges on the target sub-account (it should only require read privileges at the parent account level, I believe!)

?>