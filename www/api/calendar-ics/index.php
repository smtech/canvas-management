<?php

require_once('../page-generator.inc.php');

require_once('config.inc.php');

displayPage('<p>Choose the direction you want to send your data:</p>
<h3><a href="export.php">From a Canvas course calendar to an ICS feed</a></h3>
<h3><a href="import.php">From an ICS feed into a Canvas Course calendar</a></h3>');

?>