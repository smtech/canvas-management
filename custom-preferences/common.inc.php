<?php

require_once __DIR__ . '/../common.inc.php';

use Battis\ConfigXML;

$customprefs = (new ConfigXML(__DIR__ . '/config.xml'))->newInstanceOf(mysqli::class, '/config/mysql');

$toolbox->smarty_prependTemplateDir(__DIR__ . '/templates', basename(__DIR__));
