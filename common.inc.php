<?php

$requestApiDomain = (empty($_REQUEST['custom_canvas_api_domain']) ? false : $_REQUEST['custom_canvas_api_domain']);

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
if (empty($_SESSION[CANVAS_INSTANCE_URL])) {
    if (!empty($requestApiDomain)) {
        $_SESSION[CANVAS_INSTANCE_URL] = "https://{$requestApiDomain}";
    } elseif (!empty($_SESSION[ToolProvider::class]['canvas']['api_domain'])) {
        $_SESSION[CANVAS_INSTANCE_URL] = 'https://' . $_SESSION[ToolProvider::class]['canvas']['api_domain'];
    } else {
        $toolbox->log('Could not detect CANVAS_INSTANCE_URL');
        $_SESSION[CANVAS_INSTANCE_URL] = 'https://example.com';
    }
}

/* cache per-instance */
$toolbox->cache_pushKey(parse_url($_SESSION[CANVAS_INSTANCE_URL], PHP_URL_HOST));

/* Configure smarty templating */
/* FIXME this is sometimes superfluous overhead (e.g. action=config) */
$toolbox->smarty_prependTemplateDir(__DIR__ . '/templates', basename(__DIR__));
$toolbox->getSmarty()->addStylesheet(
    DataUtilities::URLfromPath(__DIR__ . '/css/canvas-management.css'),
    basename(__DIR__)
);

$toolbox->smarty_assign([
    'title' => $toolbox->config('TOOL_NAME'),
    'category' => DataUtilities::titleCase(preg_replace('/[\-_]+/', ' ', basename(__DIR__))),
    'APP_URL' => $toolbox->config('APP_URL'),
    'CANVAS_INSTANCE_URL' => $_SESSION[CANVAS_INSTANCE_URL],
    'navbarActive' => basename(dirname($_SERVER['REQUEST_URI'])),
    'menuItems' => $toolbox->buildMenu(__DIR__, [
        '.git',
        'build',
        'css',
        'docs',
        'examples',
        'images',
        'js',
        'logs',
        'node_modules',
        'src',
        'templates',
        'tests',
        'vendor'
    ])
]);
