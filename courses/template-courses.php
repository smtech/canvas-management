<?php

require_once('common.inc.php');

use Battis\DataUtilities;
use Battis\BootstrapSmarty\NotificationMessage;

define('STEP_INSTRUCTIONS', 1);
define('STEP_CONFIRM', 2);
define('STEP_TEMPLATE', 3);

$step = (empty($_REQUEST['step']) ? STEP_INSTRUCTIONS : $_REQUEST['step']);

switch ($step) {
    case STEP_CONFIRM:
    case STEP_TEMPLATE:
        try {
            $source = array();
            $template = false;
            if (empty($_REQUEST['template'])) {
                $toolbox->smarty_addMessage(
                    'Template',
                    'was not entered, courses are in default configuration.'
                );
                $step = STEP_INSTRUCTIONS;
            } else {
                $templated = true;
                $template = (is_int($_REQUEST['template']) ? $_REQUEST['template'] : "sis_course_id:{$_REQUEST['template']}");

                /* pull course settings as completely as possible */
                $source = $toolbox->api_get("courses/$template/settings");
                $source = array_merge(
                    $source->getArrayCopy(),
                    $toolbox->api_get("courses/$template")->getArrayCopy()
                );

                /* save ID and name to create a nice link later */
                $sourceId = $source['id'];
                $sourceName = $source['name'];

                /* clear settings that are provided form entry */
                unset($source['id']);
                unset($source['sis_course_id']);
                unset($source['integration_id']);
                unset($source['name']);
                unset($source['course_code']);
                unset($source['account_id']);
                unset($source['enrollment_term_id']);
                unset($source['start_at']);
                unset($source['end_at']);
                unset($source['enrollments']);

                /* why nest this, I mean... really? */
                $source = array('course' => $source);

                /* pull course navigation */
                $tabs = $toolbox->api_get("courses/$template/tabs", [
                    'include[]' => 'external'
                ]);
                $navigation = [];
                foreach ($tabs as $tab) {
                    if ($tab['id'] != 'home' && $tab['id'] != 'settings') {
                        $navigation[$tab['id']] = $tab;
                    }
                }
                uasort($navigation, function ($left, $right) {
                    return $left['position'] - $right['position'];
                });
            }

            $courses = DataUtilities::loadCsvToArray('csv');

            if ($step == STEP_CONFIRM) {
                if (empty($courses)) {
                    $toolbox->smarty_addMessage(
                        'Courses',
                        'No courses found in uploaded list.',
                        NotificationMessage::ERROR
                    );
                    $step = STEP_INSTRUCTIONS;
                } else {
                    foreach ($courses as $course) {
                        /* duplicate course settings */
                        $course = $toolbox->api_put("courses/sis_course_id:{$course['course_id']}", $source);

                        /* duplicate course navigation */
                        foreach ($navigation as $id => $tab) {
                            $toolbox->api_put(
                                "courses/{$course['id']}/tabs/$id",
                                [
                                    'position' => $tab['position'],
                                    'visibility' => $tab['visibility']
                                ]
                            );
                        }

                        /* duplicate course content */
                        $migration = $toolbox->api_post(
                            "courses/{$course['id']}/content_migrations",
                            array(
                                'migration_type' => 'course_copy_importer',
                                'settings[source_course_id]' => $template
                            )
                        );

                        $toolbox->smarty_addMessage(
                            "<a target=\"_parent\" href=\"{$_SESSION[CANVAS_INSTANCE_URL]}/courses/{$course['id']}\">{$course['name']}</a>",
                            "has been templated as a clone of <a target=\"_parent\" href=\"{$_SESSION[CANVAS_INSTANCE_URL]}/courses/$sourceId\">$sourceName</a>. Course content is being <a target=\"_parent\" href=\"{$_SESSION[CANVAS_INSTANCE_URL]}/courses/{$course['id']}/content_migrations\">migrated</a> right now.",
                            NotificationMessage::GOOD
                        );
                    }
                }
            }
        } catch (Exception $e) {
            $toolbox->exceptionErrorMessage($e);
        }

        /* flows into STEP_INSTRUCTIONS */

    case STEP_INSTRUCTIONS:
    default:
        $toolbox->smarty_assign('formHidden', array('step' => STEP_CONFIRM));
        $toolbox->smarty_display(basename(__FILE__, '.php') . '/instructions.tpl');
}
