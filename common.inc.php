<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/constants.inc.php';

use smtech\CanvasManagement\Toolbox;
use smtech\ReflexiveCanvasLTI\LTI\ToolProvider;
use Battis\DataUtilities;

@session_start(); // TODO suppressing warnings is wrong

/* prepare the toolbox */
if (empty($_SESSION[Toolbox::class])) {
    $_SESSION[Toolbox::class] = Toolbox::fromConfiguration(CONFIG_FILE);
}
$toolbox =& $_SESSION[Toolbox::class];

/* set the Tool Consumer's instance URL, if present */
if (empty($_SESSION[CANVAS_INSTANCE_URL]) &&
    !empty($_SESSION[ToolProvider::class]['canvas']['api_domain'])
) {
    $_SESSION[CANVAS_INSTANCE_URL] = 'https://' . $_SESSION[ToolProvider::class]['canvas']['api_domain'];
}

/* Configure smarty templating */
/* FIXME this is sometimes superfluous overhead (e.g. action=config) */
$toolbox->smarty_prependTemplateDir(__DIR__ . '/templates', basename(__DIR__));
$toolbox->smarty_assign([
    'title' => $toolbox->config('TOOL_NAME'),
    'category' => DataUtilities::titleCase(preg_replace('/[\-_]+/', ' ', basename(__DIR__))),
    'APP_URL' => $toolbox->config('APP_URL'),
    'navbarActive' => basename(dirname($_SERVER['REQUEST_URI']))
]);
