<?php

require_once(__DIR__ . '/../common.inc.php');

$toolbox->smarty_prependTemplateDir(__DIR__ . '/templates', basename(__DIR__));
$toolbox->smarty_assign([
    'formButton' => 'Punch it, Chewie! <span class="fa fa-rocket"></span>'
]);
