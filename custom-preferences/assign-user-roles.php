<?php

require_once('common.inc.php');

use Battis\BootstrapSmarty\NotificationMessage;

$cache = new \Battis\HierarchicalSimpleCache($sql, basename(__DIR__));
$cache->pushKey(basename(__FILE__, '.php'));
$cache->setLifetime(60*60);

define('STEP_INSTRUCTIONS', 1);
define('STEP_LISTING', 2);
define('STEP_RESULT', 3);

$step = (empty($_REQUEST['step']) ? STEP_INSTRUCTIONS : $_REQUEST['step']);

switch ($step) {
    case STEP_LISTING:
    case STEP_RESULT:
        try {
            $users = $cache->getCache('users');
            if ($users === false) {
                $users = array();
                $response = $toolbox->api_get('/accounts/1/users');
                foreach ($response as $user) {
                    $users[$user['id']] = $user;
                }
                $cache->setCache('users', $users);
            }
        } catch (Exception $e) {
            $toolbox->exceptionErrorMessage($e);
            $step = STEP_INSTRUCTIONS;
        }

        if ($step == STEP_LISTING || $step == STEP_RESULT) {
            $ROLES = array(
                'faculty',
                'staff',
                'student',
                'advisor',
                'no-menu',
                'alum',
                'departed'
            );

            if ($step == STEP_RESULT) {
                foreach ($_REQUEST['users'] as $id => $data) {
                    if ($data['dirty']) {
                        if (empty($id) || empty($data['role'])) {
                            $toolbox->smarty_addMessage(
                                'Error',
                                "Empty ID ($id) or role ({$data['role']})",
                                NotificationMessage::ERROR
                            );
                        } else {
                            $updated = false;
                            if ($response = $customPrefs->query("
                                SELECT *
                                    FROM `users`
                                    WHERE
                                        `id` = '$id'
                            ")) {
                                if ($response->num_rows > 0) {
                                    $updated = $customPrefs->query("
                                        UPDATE `users`
                                            SET `role` = '{$data['role']}'
                                            WHERE `id` = '$id'
                                    ");
                                } else {
                                    $updated = $customPrefs->query("
                                        INSERT
                                            INTO `users`
                                            (`id`, `role`) VALUES ('$id', '{$data['role']}')
                                    ");
                                }
                            }
                            if ($updated) {
                                $toolbox->smarty_addMessage(
                                    $users[$id]['name'],
                                    "is now {$data['role']}.",
                                    NotificationMessage::GOOD
                                );
                            } else {
                                $toolbox->smarty_addMessage(
                                    $users[$id]['name'],
                                    "was not updated to {$data['role']}. " . $customPrefs->error,
                                    NotificationMessage::WARNING
                                );
                            }
                        }
                    }
                }
            }

            $assignedUsers = array();
            $unassignedUsers = array();

            foreach ($users as $user) {
                if (!empty($user['sis_user_id'])) {
                    if ($response = $customPrefs->query("
                        SELECT *
                            FROM `users`
                            WHERE
                                `id` = '{$user['id']}'
                    ")) {
                        if ($row = $response->fetch_assoc()) {
                            $row['groups'] = unserialize($row['groups']);
                            if (!empty($row['groups'])) {
                                $groupResponse = $customPrefs->query("
                                    SELECT *
                                        FROM `groups`
                                        WHERE
                                            `id` = '" . implode("' OR `id` = '", $row['groups']) . "'
                                ");
                                $groups = array();
                                while ($groupRow = $groupResponse->fetch_assoc()) {
                                    $groups[$groupRow['id']] = $groupRow;
                                }
                                $row['groups'] = $groups;
                            }
                            $assignedUsers[$row['role']][$user['id']]['custom-prefs'] = $row;
                            $assignedUsers[$row['role']][$user['id']]['user'] = $user;
                        } else {
                            $unassignedUsers[0][$user['id']]['user'] = $user;
                        }
                    }
                }
            }

            $toolbox->smarty_assign('roles', $ROLES);
            $toolbox->smarty_assign('assignedUsers', $assignedUsers);
            $toolbox->smarty_assign('unassignedUsers', $unassignedUsers);
            $toolbox->smarty_assign('formHidden', array('step' => STEP_RESULT));
            $toolbox->smarty_display(basename(__FILE__, '.php') . '/listing.tpl');
            break;
        }

        /* continue into STEP_INSTRUCTIONS */

    case STEP_INSTRUCTIONS:
    default:
        $toolbox->smarty_assign('formHidden', array('step' => STEP_LISTING));
        $toolbox->smarty_display(basename(__FILE__, '.php') . '/instructions.tpl');
        exit;
}
