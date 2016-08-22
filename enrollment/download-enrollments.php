<?php

require_once('common.inc.php');

use Battis\BootstrapSmarty\NotificationMessage;

$cache = new \Battis\HierarchicalSimpleCache($sql, basename(__DIR__));
$cache->pushKey(basename(__FILE__, '.php'));

define('STEP_INSTRUCTIONS', 1);
define('STEP_DOWNLOAD', 2);

$step = (isset($_REQUEST['step']) ? $_REQUEST['step'] : STEP_INSTRUCTIONS);

$accounts = $toolbox->getAccountList();
$terms = $toolbox->getTermList();

switch ($step) {
    case STEP_DOWNLOAD:
        try {
            if (!empty($_REQUEST['term'])) {
                if (!empty($_REQUEST['account'])) {
                    $cache->pushKey($_REQUEST['account']);
                    $cache->pushKey($_REQUEST['term']);
                    $data = $cache->getCache('enrollments');
                    if (empty($data)) {
                        $courses = $toolbox->api_get(
                            "accounts/{$_REQUEST['account']}/courses",
                            array(
                                'with_enrollments' => true,
                                'enrollment_term_id' => $_REQUEST['term']
                            )
                        );

                        foreach ($courses as $course) {
                            $enrollments = $toolbox->api_get(
                                "courses/{$course['id']}/enrollments"
                            );
                            foreach ($enrollments as $enrollment) {
                                $data[] = array(
                                    'user_id' => $enrollment['user_id'],
                                    'sis_user_id' => $enrollment['user']['sis_user_id'],
                                    'user[name]' => $enrollment['user']['name'],
                                    'user[sortable_name]' => $enrollment['user']['sortable_name'],
                                    'course_id' => $course['id'],
                                    'sis_course_id' => $course['sis_course_id'],
                                    'sis_section_id' => $enrollment['sis_section_id'],
                                    'course[name]' => $course['name'],
                                    'enrollment[role]' => $enrollment['role']
                                );
                            }
                        }
                        $cache->setCache('enrollments', $data, 15 * 60);
                    }
                    $toolbox->smarty_assign('account', $_REQUEST['account']);
                    $toolbox->smarty_assign('term', $_REQUEST['term']);
                    $toolbox->smarty_assign('filename', date('Y-m-d_H-i-s') . '_' . preg_replace('/\s/', '_', $accounts[$_REQUEST['account']]['name'] . '_' . $terms[$_REQUEST['term']]['name']) . '_enrollments');
                    $toolbox->smarty_assign('csv', $cache->getHierarchicalKey('enrollments'));
                } else {
                    $toolbox->smarty_addMessage('Account Required', 'You must select an account to download enrollments', NotificationMessage::ERROR);
                }
            } else {
                $toolbox->smarty_addMessage('Term Required', 'You must select a term to download enrollments', NotificationMessage::ERROR);
            }
        } catch (Exception $e) {
            $toolbox->exceptionErrorMessage($e);
        }

        /* flows into STEP_INSTRUCTIONS */

    case STEP_INSTRUCTIONS:
        $toolbox->smarty_assign('formHidden', array('step' => STEP_DOWNLOAD));
        $toolbox->smarty_assign('accounts', $accounts);
        $toolbox->smarty_assign('terms', $terms);
        $toolbox->smarty_display(basename(__FILE__, '.php') . '/instructions.tpl');
}
