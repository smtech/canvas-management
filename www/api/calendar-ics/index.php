<?php

require_once('../page-generator.inc.php');

require_once('config.inc.php');

displayPage('
	<h3>Choose Import/Export Direction</h3>
	<p>In which direction do you want to send your information>?</p>
	<ul>
		<li><a href="export.php">Export:</a> I would like to get an ICS feed of the calendar information (which I can subscribe to in Google, iCal, Outlook, etc.) for a specific course in Canvas.</li>
		<li><a href="import.php">Import:</a> I have an ICS feed (from Google, Smartsheet, iCloud, etc.) that I want to bring into a Canvas course, user or group.</li>
	</ul>
');

?>