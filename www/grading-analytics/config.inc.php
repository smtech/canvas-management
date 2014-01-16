<?php

if (!defined('TOOL_NAME')) {
	define('TOOL_NAME', 'Grading Analytics'); // what the tool calls itself
}

if (!defined('TOOL_START_PAGE')) {
	define('TOOL_START_PAGE', 'index.php');
}

define('DATA_COLLECTION_CRONTAB', '0 0 * * *'); // collect data every night at midnight

/* graph coloring (tied into CSS styling of summary text, so be sure to use
   values supported by PHPGraphLib _and_ CSS!
   http://www.ebrueggeman.com/phpgraphlib/documentation/function-reference#supported_colors */
define('GRAPH_2_WEEK_COLOR', 'red');
define('GRAPH_2_WEEK_STYLE', 'solid');
define('GRAPH_1_WEEK_COLOR', 'lime');
define('GRAPH_1_WEEK_STYLE', 'solid');
define('GRAPH_AVERAGE_COLOR', 'blue');
define('GRAPH_AVERAGE_STYLE', 'dashed');
define('GRAPH_HIGHLIGHT_COLOR', 'red');
define('GRAPH_DATA_COLOR', 'silver');

define('GRAPH_MIN_WIDTH', 1000); // the smallest a graph should be allowed to be in pixels
define('GRAPH_BAR_WIDTH', 10); // how many pixels to allocate per data point in a bar graph
define('GRAPH_ASPECT_RATIO', 0.375); // generic aspect ratio for all graphs
define('GRAPH_INSET_WIDTH', '40%'); // width of the inset departmental graphs in the column

?>