<?php

require_once 'common.inc.php';

use Battis\BootstrapSmarty\NotificationMessage;

define('STEP_INSTRUCTIONS', 1);
define('STEP_RESET', 2);

$step = (empty($_REQUEST['step']) ? STEP_INSTRUCTIONS : $_REQUEST['step']);

switch ($step) {
    case STEP_RESET:
        try {
            $accounts = $toolbox->getAccountList();
            $users = $toolbox->api_get("accounts/{$_REQUEST['account']}/users");
            foreach ($users as $user) {
                $toolbox->api_delete(
                    'users/self/favorites/courses',
                    [
                        'as_user_id' => $user['id']
                    ]
                );
            }
            $toolbox->smarty_addMessage(
                'Favorites Reset',
                'Favorite courses were reset to the default active courses for ' . $users->count() . ' users in ' . $accounts[$_REQUEST['account']]['name'] . '.',
                NotificationMessage::SUCCESS
            );
        } catch (Exception $e) {
            $toolbox->exceptionErrorMessage($e);
        }

        $toolbox->smarty_assign('account', $_REQUEST['account']);

        /* flow into STEP_INSTRUCTIONS */

    case STEP_INSTRUCTIONS:
    default:
        $toolbox->smarty_assign('accounts', $toolbox->getAccountList());
        $toolbox->smarty_assign('formHidden', ['step' => STEP_RESET]);
        $toolbox->smarty_display(basename(__FILE__, '.php') . '/instructions.tpl');
}
