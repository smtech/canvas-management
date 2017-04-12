<?php

require_once('common.inc.php');

use Battis\BootstrapSmarty\NotificationMessage;

$MANUALLY_CREATED_COURSES_ACCOUNT = 96;
$DEFAULT_TERM = 195;
$CACHE_LIFETIME = 20 * 60; // 20 minutes

$toolbox->cache_pushKey(basename(__FILE__, '.php'));

$STEP_INSTRUCTIONS = 1;
$STEP_CONFIRM = 2;
$STEP_ENROLL = 3;

$step = (empty($_REQUEST['step']) ? $STEP_INSTRUCTIONS : $_REQUEST['step']);

$toolbox->smarty_assign('role', (empty($_REQUEST['role']) ? 218 /* Student */ : $_REQUEST['role']));

try {
    $roles = $toolbox->cache_get('roles');
    if ($roles === false) {
        $roles = $toolbox->api_get('accounts/1/roles'); // TODO handle specific accounts
        $toolbox->cache_set('roles', $roles);
    }
} catch (Pest_Exception $e) {
    $toolbox->exceptionErrorMessage($e);
    $toolbox->smarty_display();
    exit;
}

switch ($step) {
    case $STEP_CONFIRM:
        try {
            $users = $toolbox->explodeCommaAndNewlines($_REQUEST['users']);

            if (empty($_REQUEST['course'])) {
                $toolbox->smarty_addMessage(
                    'Course',
                    'was not selected, so no enrollments can happen',
                    NotificationMessage::ERROR
                );
                $step = $STEP_INSTRUCTIONS;
            } else {
                $sections = $toolbox->cache_get("courses/{$_REQUEST['course']}");
                if (empty($sections)) {
                    $section = array();
                    $courses = $toolbox->api_get(
                        'accounts/1/courses',
                        array(
                            'search_term' => $_REQUEST['course']
                        )
                    );
                    foreach ($courses as $course) {
                        $courseSections = $toolbox->api_get("courses/{$course['id']}/sections");
                        if ($courseSections->count() == 0) {
                            /* we have only the "magic" default section */
                            $sections[] = array('course' => $course);
                        } else {
                            foreach ($courseSections as $section) {
                                $sections[] = array(
                                    'course' => $course,
                                    'section' => $section
                                );
                            }
                        }
                    }
                    $toolbox->cache_set("courses/{$_REQUEST['course']}", $sections, $CACHE_LIFETIME);
                }

                if (empty($sections)) {
                    $toolbox->smarty_addMessage(
                        'No Courses',
                        "matched your search term '{$_REQUEST['course']}'.",
                        NotificationMessage::WARNING
                    );
                    $step = $STEP_INSTRUCTIONS;
                }
            }

            if ($step == $STEP_CONFIRM) {
                if (!empty($users)) {
                    $confirm = array();
                    foreach ($users as $term) {
                        $confirm[$term] = $toolbox->cache_get("users/$term");
                        if ($confirm[$term] === false) {
                            $found = $toolbox->api_get(
                                'accounts/1/users',
                                [
                                    'search_term' => $term,
                                    'include[]' => 'term'
                                ]
                            );
                            foreach ($found as $user) {
                                if (!stripos($user['sis_user_id'], '-advisor')) {
                                    $confirm[$term][] = $user;
                                }
                            }
                            $toolbox->cache_set("users/$term", $confirm[$term], $CACHE_LIFETIME);
                        }
                    }

                    $toolbox->smarty_assign([
                        'sections' => $sections,
                        'terms' => $toolbox->getTermList(),
                        'accounts' => $toolbox->getAccountList(),
                        'confirm' => $confirm,
                        'roles' => $toolbox->api_get('accounts/1/roles'), // TODO make this account-specific
                        'formHidden'=> [
                            'step' => $STEP_ENROLL
                        ]
                    ]);
                    $toolbox->smarty_display(basename(__FILE__, '.php') . '/confirm.tpl');
                    break;
                } else {
                    $toolbox->smarty_addMessage(
                        'Users',
                        'were not selected, so no enrollments can happen.',
                        NotificationMessage::ERROR
                    );
                    $step = $STEP_INSTRUCTIONS;
                }
            }
        } catch (Pest_Exception $e) {
            $toolbox->exceptionErrorMessage($e);
        }

        /* flow into $STEP_ENROLL (and $STEP_INSTRUCTIONS) */

    case $STEP_ENROLL:
        try {
            if ($step == $STEP_ENROLL) {
                $courseEnrollment = false;
                if (empty($_REQUEST['section'])) {
                    if (!empty($_REQUEST['course'])) {
                        $courseEnrollment = true;
                    } else {
                        $toolbox->smarty_addMessage(
                            'Course or Section',
                            'Missing from enrollment request.',
                            NotificationMessage::ERROR
                        );
                        $step = $STEP_INSTRUCTIONS;
                    }
                }

                if (empty($_REQUEST['users'])) {
                    $toolbox->smarty_addMessage(
                        'Users',
                        'missing from enrollment request.',
                        NotificationMessage::ERROR
                    );
                } elseif ($step == $STEP_ENROLL) {
                    $count = 0;
                    foreach ($_REQUEST['users'] as $user) {
                        $enrollment = $toolbox->api_post(
                            (
                                $courseEnrollment ?
                                "/courses/{$_REQUEST['course']}/enrollments" :
                                "/sections/{$_REQUEST['section']}/enrollments"
                            ),
                            array(
                                'enrollment[user_id]' => $user['id'],
                                'enrollment[role_id]' => $user['role'],
                                'enrollment[enrollment_state]' => 'active',
                                'enrollment[notify]' => (empty($user['notify']) ? 'false' : $user['notify'])
                            )
                        );
                        if (!empty($enrollment['id'])) {
                            $count++;
                        } // FIXME should really list errors, no?
                    }

                    if ($courseEnrollment) {
                        $course = $_REQUEST['course'];
                    } else {
                        $section = $toolbox->api_get("sections/{$_REQUEST['section']}");
                        $course = $section['course_id'];
                    }

                    // FIXME no longer have the course ID… link is broken
                    $toolbox->smarty_addMessage(
                        'Success',
                        "<a target=\"_top\" href=\"{$_SESSION[CANVAS_INSTANCE_URL]}/courses/$course/users\">$count users enrolled</a>",
                        NotificationMessage::GOOD
                    );

                    $_REQUEST = array();
                }
            }
        } catch (Pest_Exception $e) {
            $toolbox->exceptionErrorMessage($e);
        }

        /* fall through to STEP_INSTRUCTION */

    case $STEP_INSTRUCTIONS:
    default:
        if (!empty($_REQUEST['users'])) {
            $toolbox->smarty_assign('users', $_REQUEST['users']);
        }
        if (!empty($_REQUEST['course'])) {
            $toolbox->smarty_assign('course', $_REQUEST['course']);
        }

        $toolbox->smarty_assign('roles', $roles);
        $toolbox->smarty_assign('formHidden', array('step' => $STEP_CONFIRM));
        $toolbox->smarty_display(basename(__FILE__, '.php') . '/instructions.tpl');
}
