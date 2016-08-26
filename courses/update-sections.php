<?php

require_once 'common.inc.php';

use Battis\DataUtilities;
use Battis\BootstrapSmarty\NotificationMessage;

define('STEP_INSTRUCTIONS', 1);
define('STEP_CONFIRM', 2);
define('STEP_UPDATE', 3);

$step = (empty($_REQUEST['step']) ? STEP_INSTRUCTIONS : $_REQUEST['step']);

switch ($step) {
    case STEP_CONFIRM:
        $sections = DataUtilities::loadCsvToArray('csv');

        if (empty($sections)) {
            $step = STEP_INSTRUCTIONS;
            $toolbox->smarty_addMessage(
                'Empty section list',
                'The uploaded CSV file contained no sections.',
                NotificationMessage::WARNING
            );
        }

        if ($step == STEP_CONFIRM) {
            $toolbox->smarty_assign([
                'fields' => array_keys($sections[0]),
                'sections' => $sections,
                'formHidden' => [
                    'step' => STEP_UPDATE,
                    'ignore_course_id' => $_REQUEST['ignore_course_id']
                ]
            ]);
            $toolbox->smarty_display(basename(__FILE__, '.php') . '/confirm.tpl');
            break;
        }

    /* flows into STEP_UPDATE */
    case STEP_UPDATE:
        if ($step == STEP_UPDATE) {
            $links = [];
            $crosslist = [];
            $crosslistFail = [];
            $ignoreCourseId = (empty($_REQUEST['ignore_course_id']) ? false : $_REQUEST['ignore_course_id']);
            foreach ($_REQUEST['sections'] as $section) {
                if (isset($section['batch-include']) && $section['batch-include'] == 'include') {
                    /* build parameter list */
                    $params = [];

                    if (!empty($section['name'])) {
                        $params['name'] = $section['name'];
                    }

                    if (!empty($section['section_id'])) {
                        $params['sis_section_id'] = $section['section_id'];
                    }

                    if (!empty($section['start_at'])) {
                        $params['start_at'] = $section['start_at'];
                    }

                    if (!empty($section['end_at'])) {
                        $params['end_at'] = $section['end_at'];
                    }

                    try {
                        $response = $toolbox->api_put(
                            "sections/sis_section_id%3A{$section['old_section_id']}",
                            array(
                                'course_section' => $params
                            )
                        );
                        $links[] = '<a target="_parent" href="' . $_SESSION[CANVAS_INSTANCE_URL] .
                            "/courses/{$response['course_id']}/sections/{$response['id']}\">{$response['name']}</a>";
                        if (!$ignoreCourseId && $response['sis_course_id'] != $section['course_id']) {
                            $response = $toolbox->api_post(
                                "sections/{$response['id']}/crosslist/sis_course_id%3A{$section['course_id']}"
                            );
                            if ($response['sis_course_id'] == $section['course_id']) {
                                $crosslist[] = '<a target="_parent" href="' . $_SESSION[CANVAS_INSTANCE_URL] .
                                    "/courses/{$response['course_id']}/sections/{$response['id']}\">" .
                                    "{$response['name']}</a>";
                            } else {
                                $crosslistFail[] = '<a target="_parent" href="' . $_SESSION[CANVAS_INSTANCE_URL] .
                                    "/courses/{$response['course_id']}/sections/{$response['id']}\">" .
                                    "{$response['name']}</a>";
                            }
                        }
                    } catch (Exception $e) {
                        $toolbox->exceptionErrorMessage($e);
                    }
                }
            }

            if (!empty($links)) {
                $toolbox->smarty_addMessage(
                    'Update completed',
                    'The following sections have been updated: ' . implode(', ', $links),
                    NotificationMessage::GOOD
                );
            }
            if (!empty($crosslist)) {
                $toolbox->smarty_addMessage(
                    'Crosslisting completed',
                    'The following sections have been crosslisted: ' . implode(', ', $crosslist),
                    NotificationMessage::GOOD
                );
            }
            if (!empty($crosslistFail)) {
                $toolbox->smarty_addMessage(
                    'Crosslisting failed',
                    'The following sections could not be crosslisted: ' . implode(', ', $crosslistFail),
                    NotificationMessage::WARNING
                );
            }
            $toolbox->smarty_assign([
                'ignore_course_id' => $ignoreCourseId
            ]);
        } else {
            $toolbox->smarty_addMessage(
                'Empty section list',
                'The uploaded CSV file contained no sections.',
                NotificationMessage::WARNING
            );
        }
        /* flow into STEP_INSTRUCTIONS */
    case STEP_INSTRUCTIONS:
        $toolbox->smarty_assign([
            'formHidden' => [
                'step' => STEP_CONFIRM
            ]
        ]);
        $toolbox->smarty_display(basename(__FILE__, '.php') . '/instructions.tpl');
}
