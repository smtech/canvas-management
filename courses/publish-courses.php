<?php

require_once('common.inc.php');

use Battis\BootstrapSmarty\NotificationMessage;

define('STEP_INSTRUCTIONS', 1);
define('STEP_PUBLISH', 2);

$step = (empty($_REQUEST['step']) ? STEP_INSTRUCTIONS : $_REQUEST['step']);

switch ($step) {
    case STEP_PUBLISH:
        try {
            $courses = $toolbox->api_get(
                "accounts/{$_REQUEST['account']}/courses",
                array(
                    'enrollment_term_id' => $_REQUEST['term'],
                    'published' => 'false'
                )
            );

            $list = array();
            foreach ($courses as $course) {
                $toolbox->api_put(
                    "courses/{$course['id']}",
                    array(
                        'offer' => 'true'
                    )
                );
                $list[] = "<a target=\"_parent\" href=\"{$_SESSION[CANVAS_INSTANCE_URL]}/courses/{$course['id']}\">{$course['name']}</a>";
            }
            $toolbox->smarty_addMessage($courses->count(). ' courses published', implode(', ', $list), NotificationMessage::GOOD);
        } catch (Exception $e) {
            $toolbox->exceptionErrorMessage($e);
        }

        /* flow into STEP_INSTRUCTIONS */

    case STEP_INSTRUCTIONS:
    default:
        $toolbox->smarty_assign('accounts', $toolbox->getAccountList());
        $toolbox->smarty_assign('terms', $toolbox->getTermList());
        $toolbox->smarty_assign('formHidden', array('step' => STEP_PUBLISH));
        $toolbox->smarty_display(basename(__FILE__, '.php') . '/instructions.tpl');
}
