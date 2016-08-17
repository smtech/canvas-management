<?php

require_once 'common.inc.php';

define('STEP_INSTRUCTIONS', 1);
define('STEP_ROLE', 2);
define('STEP_ADMINS', 3);

$toolbox->cache_pushKey(basename(__FILE__, '.php'));

$step = (empty($_REQUEST['step']) ? STEP_INSTRUCTIONS : $_REQUEST['step']);

switch ($step) {
    case STEP_ADMINS:
        try {
            /* get the current list of account admins */
            $admins = $toolbox->api_get("accounts/{$_REQUEST['account']}/admins");

            /* build a list of all teachers in the selected terms */
            $teachers = [];
            $terms_selected = unserialize(urldecode($_REQUEST['terms_selected']));
            foreach ($terms_selected as $term) {
                $courses = $toolbox->api_get("accounts/{$_REQUEST['account']}/courses", [
                    'with_enrollments' => true,
                    'enrollment_type' => ['teacher'],
                    'enrollment_term_id' => $term,
                    'include' => ['teachers']
                ]);
                foreach ($courses as $course) {
                    foreach ($course['teachers'] as $teacher) {
                        if (!in_array($teacher['id'], array_keys($teachers))) {
                            $teachers[$teacher['id']] = $teacher;
                        }
                    }
                }
            }

            /* walk through the current list of admins... */
            $removedAdmins = [];
            foreach ($admins as $admin) {
                if ($admin['role_id'] == $_REQUEST['role']) {
                    /* ...remove any admins in the desired role who are not teachers in the selected terms or... */
                    if (!in_array($admin['user']['id'], array_keys($teachers))) {
                        $toolbox->api_delete("accounts/{$_REQUEST['account']}/admins/{$admin['user']['id']}", [
                            'role_id' => $_REQUEST['role']
                        ]);
                        $removedAdmins[] = $admin['user']['name'];
                    /* ...remove any teachers who are already admins from our list */
                    } else {
                        unset($teachers[$admin['user']['id']]);
                    }
                }
            }
            if (count($removedAdmins) > 0) {
                $toolbox->smarty_addMessage(
                    'Removed ' . count($removedAdmins) . ' account admins',
                    implode(', ', $removedAdmins)
                );
            }

            /* add any remaining teachers as admins */
            $addedAdmins = [];
            foreach ($teachers as $teacher) {
                $toolbox->api_post("accounts/{$_REQUEST['account']}/admins", [
                    'user_id' => $teacher['id'],
                    'role_id' => $_REQUEST['role'],
                    'send_confirmation' => ($_REQUEST['confirmation'] ? true : false)
                ]);
                $addedAdmins[] = $teacher['display_name'];
            }
            if (count($addedAdmins) > 0) {
                $toolbox->smarty_addMessage(
                    'Added ' . count($teachers) . ' account admins',
                    implode(', ', $addedAdmins)
                );
            }

            /* carry previous selections over to the form view */
            $toolbox->smarty_assign([
                'account' => $_REQUEST['account'],
                'role' => $_REQUEST['role'],
                'terms_selected' => $terms_selected,
                'confirmation' => $_REQUEST['confirmation']
            ]);
        } catch (Exception $e) {
            $toolbox->exceptionErrorMessage($e);
        }
        /* flow into STEP_ROLE */
    case STEP_ROLE:
        if ($step == STEP_ROLE) {
            try {
                $toolbox->cache_pushKey($_REQUEST['account']);
                $roles = $toolbox->cache_get('roles');
                if (empty($roles)) {
                    $roles = $toolbox->api_get("accounts/{$_REQUEST['account']}/roles", [
                        'show_inherited' => true
                    ]);
                    $toolbox->cache_set('roles', $roles);
                }
                $toolbox->smarty_assign([
                    'formHidden' => [
                        'step' => STEP_ADMINS,
                        'account' => $_REQUEST['account'],
                        'terms_selected' => urlencode(serialize($_REQUEST['terms_selected'])),
                        'confirmation' => (empty($_REQUEST['confirmation']) ? false : $_REQUEST['confirmation'])
                    ],
                    'roles' => $roles
                ]);
                $toolbox->smarty_display(basename(__FILE__, '.php') . '/instructions-role.tpl');
                exit;
            } catch (Exception $e) {
                $toolbox->exceptionErrorMessage($e);
            }
        }
        /* flow into STEP_INSTRUCTIONS */
    case STEP_INSTRUCTIONS:
        $toolbox->smarty_assign([
            'formHidden' => [
                'step' => STEP_ROLE
            ],
            'accounts' => $toolbox->getAccountList(),
            'terms' => $toolbox->getTermList()
        ]);
        $toolbox->smarty_display(basename(__FILE__, '.php') . '/instructions-account.tpl');
}
