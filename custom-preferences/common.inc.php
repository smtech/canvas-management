<?php

require_once __DIR__ . '/../common.inc.php';

use Battis\ConfigXML;

$customPrefs = (new ConfigXML(__DIR__ . '/config.xml'))->newInstanceOf(mysqli::class, '/config/mysql');

$toolbox->cache_pushKey(basename(__DIR__));

$toolbox->smarty_prependTemplateDir(__DIR__ . '/templates', basename(__DIR__));
