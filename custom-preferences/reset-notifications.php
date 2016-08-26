<?php

require_once 'common.inc.php';

use Battis\BootstrapSmarty\NotificationMessage;

define('STEP_INSTRUCTIONS', 1);
define('STEP_RESET', 2);

$step = (empty($_REQUEST['step']) ? STEP_INSTRUCTIONS : $_REQUEST['step']);

switch ($step) {
    case STEP_RESET:
        $counter = 0;
        try {
            foreach ($toolbox->api_get("accounts/{$_REQUEST['account']}/users") as $user) {
                foreach ($toolbox->api_get("users/{$user['id']}/communication_channels") as $channel) {
                    if ($channel['type'] == 'email' && preg_match('/.*@stmarksschool\.org$/', $channel['address'])) {
                        $toolbox->api_put(
                            "/users/self/communication_channels/{$channel['id']}/notification_preferences",
                            [
                                'communication_preferences' => [
                                    'new_announcement' => 'immediately',
                                    'conversation_message' => 'immediately',
                                    'added_to_conversation' => 'immediately'
                                ],
                                'as_user_id' => $user['id']
                            ]
                        );
                        $counter++;
                    }
                }
            }
        } catch (Exception $e) {
            $toolbox->exceptionErrorMessage($e);
        }
        $toolbox->smarty_addMessage(
            "$count Users' Notifications Reset",
            "Notifications for announcements and conversation messages have been set to be sent immediately to each user's St. Mark's email.",
            NotificationMessage::SUCCESS
        );
        $toolbox->smarty_assign([
            'account' => $_REQUEST['account']
        ]);
        /* flow into STEP_INSTRUCTIONS */
    case STEP_INSTRUCTIONS:
        $toolbox->smarty_assign([
            'formHidden' => [
                'step' => STEP_RESET
            ],
            'accounts' => $toolbox->getAccountList()
        ]);
        $toolbox->smarty_display(basename(__FILE__, '.php') . '/instructions.tpl');
}
