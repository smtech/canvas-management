<?php

/* set some reasonable default values */
// search button name and default title text
$search = (!empty($_REQUEST['submit']) ? $_REQUEST['submit'] : "Search");

// title text
$title = (!empty($_REQUEST['title']) ? $_REQUEST['title'] : $search);

// optional directions
$directions = (!empty($_REQUEST['directions']) ? $_REQUEST['directions'] : null);

// optional google apps domain
$domain = (!empty($_REQUEST['domain']) ? $_REQUEST['domain'] : null);

// optional restrictions to be added to the search (e.g. 'to:user@domain.com')
$within = (!empty($_REQUEST['within']) ? $_REQUEST['within'] : null);

// href target for search
$target = (!empty($_REQUEST['target']) ? $_REQUEST['target'] : '_top');

// query placeholder in search form
$placeholder = (!empty($_REQUEST['placeholder']) ? $_REQUEST['placeholder'] : 'Enter your search query here&hellip;');

// optional url to a file browsing interface (e.g. Google Drive)
$browseUrl = (!empty($_REQUEST['browse_target']) ? $_REQUEST['browse_url'] : null);

// browse link text
$browseText = (!empty($_REQUEST['browse_text']) ? $_REQUEST['browse_text'] : 'Browse');

// href target of browse link
$browseTarget = (!empty($_REQUEST['browse_target']) ? $_REQUEST['browse_target'] : '_top');

// optional url to css styleheet
$css = (!empty($_REQUEST['css']) ? $_REQUEST['css'] : null);

/**
 * If $b isn't empty, then return $a
 **/
function showAifB($a, $b) {
	if (!empty($b)) {
		return $a;
	}
}

/**
 * If $value isn't empty, return a hidden input field
 **/
function hiddenField($name, $value) {
	return showAifB('<input name="' . $name . '" value="' . $value . '" type="hidden" />', $value);
}

/* if we're receiving query, handle that rather than showing a form */
if (isset($_REQUEST['query'])) {
	header('Location: https://drive.google.com/' . (!empty($_REQUEST['domain']) ? "a/{$_REQUEST['domain']}/" : '') . "#search/{$_REQUEST['query']}" . showAifB(" $within", $within));
	exit;
}	

?>
<html>
<head>
	<style>
		body {
			font-family: Arial, Helvetica, sans-serif;
			font-size: 12pt;
		}
		
		h1 {
			font-size: 14pt;
			font-weight: bold;
			margin: 0;
			padding: 0;
		}
		
		.container {
			display: table;
			width: 100%;
		}
		
		.visible {
			display: table-cell;
			vertical-align: middle;
			padding: 3px;
		}
		
		.auto-expand, input[type=text] {
			width: 100%;
		}
	</style>
	<?= showAifB('<link rel="stylesheet" href="' . $css . '" />', $css); ?>
</head>
<body>
	<h1><?= $title ?><?= showAifB(' | <a href="' . $browseUrl . '" target="' . $browseTarget . '">' . $browseText . '</a>', $browseUrl) ?></h1>
	<?= showAifB('<label for="query">' . $directions .'</label>', $directions) ?>
	<div class="container">
		<form action="<?= $_SERVER['PHP_SELF'] ?>" target="<?= $target ?>">
			<?= hiddenField('domain', $domain) ?>
			<?= hiddenField('within', $within) ?>
			<div class="auto-expand visible">
				<input name="query" type="text" placeholder="<?= $placeholder ?>" />
			</div>
			<div class="visible">
				<input type="submit" value="<?= $search ?>" />
			</div>
		</form>
	</div>
</body>
</html>