<?php

require_once('common.inc.php');

use Battis\BootstrapSmarty\NotificationMessage;

function blank($row, $key)
{
    if (empty($row[$key])) {
        return '';
    } else {
        return $row[$key];
    }
}

$toolbox->cache_pushKey(basename(__FILE__, '.php'));

define('STEP_INSTRUCTIONS', 1);
define('STEP_CSV', 2);

$step = (empty($_REQUEST['step']) ? STEP_INSTRUCTIONS : $_REQUEST['step']);

switch ($step) {
    case STEP_CSV:
        try {
            $account = (empty($_REQUEST['account']) ? 1 : $_REQUEST['account']);
            if (empty($_REQUEST['account'])) {
                $toolbox->smarty_addMessage(
                    'No Account',
                    'No account specified, all users included in CSV file.',
                    NotificationMessage::WARNING
                );
            }

            $data = $toolbox->cache_get("$account/users");
            if ($data === false) {
                $users = $toolbox->api_get("accounts/$account/users");
                $data[] = array(
                    'id', 'user_id', 'login_id', 'full_name', 'sortable_name', 'short_name',
                    'email', 'status'
                );
                foreach ($users as $user) {
                    $data[] = array(
                        blank($user, 'id'), blank($user, 'sis_user_id'), blank($user, 'login_id'), blank($user, 'name'),
                        blank($user, 'sortable_name'), blank($user, 'short_name'), blank($user, 'email'), 'active'
                    );
                }
                $toolbox->cache_set("$account/users", $data, 15 * 60);
            }
            $toolbox->smarty_assign('csv', $toolbox->getCache()->getHierarchicalKey("$account/users"));
            $toolbox->smarty_assign('filename', date('Y-m-d_H-i-s') . "_account-{$account}_users");
            $toolbox->smarty_addMessage(
                'Ready for Download',
                '<code>users.csv</code> is ready and download should start automatically in a few seconds. Click the link below if the download does not start automatically.',
                NotificationMessage::GOOD
            );
        } catch (Exception $e) {
            $toolbox->exceptionErrorMessage($e);
        }

        /* flows into STEP_INSTRUCTIONS */

    case STEP_INSTRUCTIONS:
    default:
        $toolbox->smarty_assign('accounts', $toolbox->getAccountList());
        $toolbox->smarty_assign('formHidden', array('step' => STEP_CSV));
        $toolbox->smarty_display(basename(__FILE__, '.php') . '/instructions.tpl');
}
