<?php

require_once 'common.inc.php';

define('STEP_INSTRUCTIONS', 1);
define('STEP_CONFIGURE', 2);
define('STEP_FORCE', 3);

$toolbox->cache_pushKey(basename(__FILE__));

$step = (empty($_REQUEST['step']) ? STEP_INSTRUCTIONS : $_REQUEST['step']);

switch ($step) {
    case STEP_CONFIGURE:
        try {
            $toolbox->cache_pushKey($_REQUEST['account']);
            $users = $toolbox->api_get(
                "accounts/{$_REQUEST['account']}/users",
                [
                    'per_page' => 1
                ]
            );
            $notifications = $toolbox->cache_get('notifications');
            if (empty($notifications)) {
                $notifications = $toolbox->api_get("users/{$users[0]['id']}/communication_channels/email/{$users[0]['email']}/notification_preferences");
                $toolbox->cache_set('notifications', $notifications);
            }
            $response = $customPrefs->query("SELECT `role` FROM `users` GROUP BY `role` ORDER BY `role` ASC");
            $roles = [];
            while ($row = $response->fetch_assoc()) {
                $roles[] = $row['role'];
            }

            $toolbox->smarty_assign([
                'account' => $toolbox->api_get("accounts/{$_REQUEST['account']}"),
                'roles' => $roles,
                'notifications' => $notifications,
                'frequencies' => [
                    'immediately',
                    'daily',
                    'weekly',
                    'never'
                ],
                'formHidden' => [
                    'step' => STEP_FORCE,
                    'account' => $_REQUEST['account']
                ]
            ]);
            $toolbox->smarty_display(basename(__FILE__. '.php') . '/configure.tpl');

            $toolbox->cache_popKey();
        } catch (Exception $e) {
            $toolbox->exceptionErrorMessage($e);
            $step = STEP_INSTRUCTIONS;
        }

        /* flow into STEP_FORCE */

    case STEP_FORCE:

        if ($step == STEP_FORCE) {
            try {
                $affected = 0;
                foreach ($toolbox->api_get("accounts/{$_REQUEST['account']}/users") as $user) {
                    $response = $customPrefs->query("SELECT * FROM `users` WHERE `id` = '{$user['id']}' AND `role` = '{$_REQUEST['role']}'");
                    if ($response && $response->num_rows > 0) {
                        $toolbox->api_put(
                            "users/{$user['id']}/communication_channels/email/{$user['email']}/notification_preferences", [
                            'notification_preferences' => $_REQUEST['notification_preferences']
                        ]);
                        $affected++;
                    }
                }
                $toolbox->smarty_addMessage(
                    'Notification Preferences Updated',
                    "$affected users'  notification preferences were updated."
                );

            } catch (Exception $e) {
                $toolbox->exceptionErrorMessage($e);
            }
        }

        /* flow into STEP_INSTRUCTIONS */

    case STEP_INSTRUCTIONS:
    default:
        $toolbox->smarty_assign([
            'accounts' => $toolbox->getAccountList(),
            'formHidden' => [
                'step' => STEP_CONFIGURE
            ]
        ]);
        $toolbox->smarty_display(basename(__FILE__, '.php') . '/instructions.tpl');
}

$toolbox->cache_popKey();
