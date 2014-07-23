<?php

require_once(__DIR__ . '/../config.inc.php');

header('Content-type: text/css');
header('X-Content-Type-Options: nosniff'); // because IE is not trusting

?>

body {
	font-family: Helvetica, Arial, sans-serif;
	font-size: 10pt;
	margin: 0;
	padding: 0;
}

pre, code {
	font-family: Inconsolata, 'Courier New', Monaco, Courier, monospace;
	font-size: 10pt;
}

em  {
	font-style: normal;
	color: red;
	text-transform: uppercase;
	font-weight: bold;
}

li {
	margin: 0.5em 0;
}

code, .code {
	font-family: Inconsolata, Monaco, "Courier New", Courier, monospace;
}

dt {
	font-weight: bold;
	font-size: 12pt;
	padding-top: 1em;
}

dd {
	margin-left: 2em;
	padding-top: 0.25em;
}

.caption {
	font-style: italic;
}

.error {
	background-color: #ffd8d8;
	border-radius: 10px;
	padding: 10px;
	border: 2px solid red;
}
/* image placement boxes with captions and headers */
.image-placement {
	display: block;
	border-radius: 10px;
	padding: 5px 1em;;
	background-color: #eee;
	margin: 1em;
}

.image-placement h4 {
	padding: 0;
	margin: 0;
}

.image-placement p.caption {
	margin: 0.25em inherit 0;
}

.image-placement a {
	text-decoration: none;
	color: black;
}

.image-placement a p.caption:after {
	content: " (Click to zoom in)"
}

.image-placement img {
	margin: 0;
	padding: 0;
	border: 1px solid #ddd;
	border-radius: 5px;
}

/* Forms formatting */
label, input[type=file] {
	display: block;
	margin-top: 10px;
}

label .comment {
	font-size: 8pt;
	display: block;
}

input {
	display: block;
}

input[type=file] {
	width: 100%;
	font-size: 8pt;
	font-style: italic;
}

input[type=text] {
	width: 100%;
}

input[type=radio], input[type=checkbox] {
	display: inline;
}

/* content wrapper section */
#content-wrapper {
	text-align: center;
	padding: 0 40px;
}

#content h1, #content h2 {
	font-family: Helvetica, Arial, sans-serif;
	color: black;
	background-color: white;
	text-transform: none;
	white-space: normal;
	min-width: intrinsic;
	padding: 20px 0;
}

#content {
	margin: 0 auto;
	max-width: 7in;
	text-align: left;
}

.page-section h1 {
	font-size: 20pt;
	padding: 0 0 1em;
}

.page-section h2 {
	font-size: 18pt;
	padding: 0 0 0.5em;
}

.page-section h3 {
	font-size: 14pt;
	padding: 0 0 0.25em;
}

.page-section h4 {
	font-size: 12pt;
	padding: 0 0 0.1em;
}

/* Masthead at the top of the page */
#masthead h1, #masthead h2 {
	margin: 0;
	padding: 0 20px;
	background-color: <?= SCHOOL_COLOR_DARK ?>;
	color: <?= SCHOOL_COLOR_LIGHT ?>;
	font-family: Garamond, 'Adobe Garamond', 'Garamond Pro', Palatino, 'Times New Roman', serif;
	min-width: 600px;
	white-space: nowrap;
}

#masthead h1 {
	padding-top: 10px;
	font-size: 24pt;
}

#masthead h2 {
	padding-bottom: 10px;
	font-size: 12pt;
	text-transform: uppercase;
}

/* header with "Start Over" and footer with server info */
#header, #footer {
	padding: 10px;
	margin: 10px;
	background: #eaedef;
	color: <?= SCHOOL_COLOR_DARK ?>;
	border-radius: 10px;
}

#footer {
	text-align: right;
	font-size: 8pt;
}

#header a, #footer a {
	color: <?= SCHOOL_COLOR_DARK ?>;
	text-decoration: none;
	border-bottom: dotted <?= SCHOOL_COLOR_DARK ?> 1px;
}