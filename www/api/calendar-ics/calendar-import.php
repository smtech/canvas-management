<?php

require_once('.ignore.calendar-ics-authentication.inc.php');
require_once('../page-generator.inc.php');
require_once('../canvas-api.inc.php');
require_once('config.inc.php');

if (isset($_REQUEST['feed'])) {
	$feed = file_get_contents($_REQUEST['feed']);
	
	require_once('./phpicalendar/functions/ical_parser.php');
	
} else {
	displayPage('
<form action="' . $_SERVER['PHP_SELF'] . '" method="get">
	<label for="feed">ICS Feed URL <span class="comment">(must be publicly accessible, not requiring additional authentication)</span></label>
	<input id="feed" name="feed" type="text" />
	<input type="submit" value="Import into Canvas" />
</form>
	');
}
?>