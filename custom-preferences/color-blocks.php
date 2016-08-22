<?php

require_once 'common.inc.php';

use smtech\StMarksColors as sm;
use Battis\BootstrapSmarty\NotificationMessage;

$toolbox->cache_pushKey(basename(__FILE__, '.php'));

define('STEP_INSTRUCTIONS', 1);
define('STEP_RESULT', 2);

$step = (empty($_REQUEST['step']) ? STEP_INSTRUCTIONS : $_REQUEST['step']);

switch ($step) {
    case STEP_RESULT:
        /* make a list of colors */
        $colors = implode('|', sm::all());

        $account = (empty($_REQUEST['account']) ? 1 : $_REQUEST['account']);
        if (empty($_REQUEST['account'])) {
            $toolbox->smarty_addMessage(
                'No Account',
                'Defaulting to the main account. (This may make things a bit slower.)',
                NotificationMessage::WARNING
            );
        }

        if (empty($_REQUEST['term'])) {
            $toolbox->smarty_addMessage(
                'No Term',
                'Please select a term for which color blocks should be assigned.',
                NotificationMessage::ERROR
            );
            $step = STEP_INSTRUCTIONS;
        } else {
            $affected = array();
            $unaffected = array();

            $parentCourses = $toolbox->cache_get('parent courses');
            if (!$parentCourses) {
                $parentCourses = [];
            }

            $colorAssignments = $toolbox->cache_get('color assignments');
            if (!$colorAssignments) {
                $colorAssignments = [];
            }

            try {
                $courses = $toolbox->api_get(
                    "accounts/$account/courses",
                    array(
                        'enrollment_term_id' => $_REQUEST['term']
                    )
                );

                /* ...and get their sections... */
                foreach ($courses as $course) {
                    /* ...and figure out their _original_ course SIS ID... */
                    $sections = $toolbox->api_get("courses/{$course['id']}/sections");
                    foreach ($sections as $section) {
                        $sis_course_id = (isset($parentCourses[$section['sis_section_id']]) ?
                                $parentCourses[$section['sis_section_id']] :
                                false
                            );
                        if ($sis_course_id === false) {
                            $parentCourse = $course;
                            if (!empty($section['nonxlist_course_id'])) {
                                $parentCourse = $toolbox->api_get("courses/{$section['nonxlist_course_id']}");
                            }
                            $sis_course_id = $parentCourse['sis_course_id'];
                            $parentCourses[$section['sis_section_id']] = $sis_course_id;
                        }

                        /* ...figure out the proper block color... */
                        if (preg_match("/($colors)/i", $sis_course_id, $match)) {
                            $color = sm::get(strtolower($match[1]))->dark()->value();

                            /* ...and set it for all enrolled users. */
                            $enrollments = $toolbox->api_get("sections/{$section['id']}/enrollments", array('state' => 'active'));
                            $users = 0;
                            foreach ($enrollments as $enrollment) {
                                if ($enrollment['user']['name'] !== 'Test Student' && !isset($colorAssignments[$enrollment['user']['id']][$course['id']])) {
                                    $response = $toolbox->api_put(
                                        "users/{$enrollment['user']['id']}/colors/course_{$course['id']}",
                                        array(
                                            'hexcode' => $color
                                        )
                                    );
                                    $colorAssignments[$enrollment['user']['id']][$course['id']] = $response['hexcode'];
                                    $users++;
                                }
                            }
                            $notification = "<a href=\"{$_SESSION[CANVAS_INSTANCE_URL]}/courses/{$course['id']}/sections/{$section['id']}\">{$section['name']}</a> <span style=\"color: #$color;\"><span class=\"glyphicon glyphicon-calendar\"></span></span> ($users / " . $enrollments->count() . " users)";
                            if ($users > 0) {
                                $affected[] = $notification;
                            } else {
                                $unaffected[] = $notification;
                            }
                        } else {
                            $unaffected[] = "<a href=\"{$_SESSION[CANVAS_INSTANCE_URL]}/courses/{$course['id']}/sections/{$section['id']}\">{$section['name']}</a> (Not assigned to a color block)";
                        }
                    }
                }
            } catch (Exception $e) {
                $toolbox->exceptionErrorMessage($e);
            }

            $toolbox->cache_set('parent courses', $parentCourses, \Battis\HierarchicalSimpleCache::IMMORTAL_LIFETIME);
            $toolbox->cache_set('color assignments', $colorAssignments, \Battis\HierarchicalSimpleCache::IMMORTAL_LIFETIME);

            $toolbox->smarty_addMessage(
                count($affected) . ' Color Blocks Assigned',
                '<dl>' . (count($unaffected) > 0 ? '<dt>No changes made to&#8230;</dt><dd><ol><li>' . implode('</li><li>', $unaffected) . '</li></ol></dd>' : '') .
                (count($affected) > 0 ? '<dt>Colors assigned for&#8230;</dt><dd><ol><li>' . implode('</li><li>', $affected) . '</li></ol></dd>' : '') . '</dl>',
                NotificationMessage::GOOD
            );
        }
        $toolbox->smarty_assign([
            'account' => $_REQUEST['account'],
            'term' => $_REQUEST['term']
        ]);

        /* flows into STEP_INSTRUCTIONS */

    case STEP_INSTRUCTIONS:
    default:
        $toolbox->smarty_assign('terms', $toolbox->getTermList());
        $toolbox->smarty_assign('accounts', $toolbox->getAccountList());
        $toolbox->smarty_assign('formHidden', array('step' => STEP_RESULT));
        $toolbox->smarty_display(basename(__FILE__, '.php') . '/instructions.tpl');
}
