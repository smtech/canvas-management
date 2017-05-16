<?php

require_once('common.inc.php');

use Battis\BootstrapSmarty\NotificationMessage;

$STEP_INSTRUCTIONS = 1;
$STEP_RENAME = 2;

$step = (empty($_REQUEST['step']) ? $STEP_INSTRUCTIONS : $_REQUEST['step']);

switch ($step) {
    case $STEP_RENAME:
        if (empty($_REQUEST['account'])) {
            $toolbox->smarty_addMessage(
                'No account specified',
                'You must choose an account on which to perform this renaming action!'
            );
        } else {
            $updated = [];
            foreach ($toolbox->api_get("accounts/{$_REQUEST['account']}/courses") as $course) {
                if ($course['course_code'] != $course['name']) {
                    $toolbox->api_put(
                        "courses/{$course['id']}",
                        [
                            'course[course_code]' => $course['name']
                        ]
                    );
                    $updated[] = "<a target=\"_top\" href=\"{$_SESSION[CANVAS_INSTANCE_URL]}/courses/{$course['id']}/settings\">{$course['name']}</a>";
                }
            }
            $toolbox->smarty_addMessage(
                count($updated) . ' courses updated',
                implode(', ', $updated),
                NotificationMessage::SUCCESS
            );
        }

        /* flows into STEP_INSTRUCTIONS */
    case $STEP_INSTRUCTIONS:
    default:
        $toolbox->smarty_assign([
            'accounts' => $toolbox->getAccountList(),
            'formHidden' => [
                'step' => $STEP_RENAME
            ]
        ]);
        $toolbox->smarty_display(basename(__FILE__, '.php') . '/instructions.tpl');
}
