<?php

require_once('common.inc.php');

use Battis\DataUtilities;
use Battis\BootstrapSmarty\NotificationMessage;

define('STEP_INSTRUCTIONS', 1);
define('STEP_NORMALIZE', 2);

$step = (empty($_REQUEST['step']) ? STEP_INSTRUCTIONS : $_REQUEST['step']);

switch ($step) {
    case STEP_NORMALIZE:
        $sections = DataUtilities::loadCsvToArray('csv');
        $account = (empty($_REQUEST['account']) ? false : $_REQUEST['account']);
        $term = (empty($_REQUEST['term']) ? false : $_REQUEST['term']);

        if ($sections || $account || $term) {
            if ($sections) {
                $links = "";
                foreach ($sections as $section) {
                    try {
                        $course = $toolbox->api_get("/courses/sis_course_id:{$section['course_id']}");
                        $courseSections = $toolbox->api_get("/courses/sis_course_id:{$section['course_id']}/sections");

                        /* do we have a singleton to rename? */
                        if ($courseSections->count() <= 1) {
                            $params = array();

                            $_section = false;
                            if ($courseSections->count() == 1) {
                                $_section = $courseSections[0];
                            }

                            if ($_section && $section['section_id'] != $_section['sis_section_id']) {
                                $params['sis_section_id'] = $section['section_id'];
                            }

                            if ($_section && $course['name'] != $_section['name']) {
                                $params['name'] = $course['name'];
                            }

                            if ($_section) {
                                $response = $toolbox->api_put(
                                    "sections/{$_section['id']}",
                                    array(
                                        'course_section' => $params
                                    )
                                );
                            } else {
                                $response = $toolbox->api_post(
                                    "courses/{$course['id']}/sections",
                                    array(
                                        'course_section[name]' => $course['name'],
                                        'course_section[sis_section_id]' => $section['section_id']
                                    )
                                );
                            }

                            if (!empty($links)) {
                                $links .= ', ';
                            }
                            $links .= "<a target=\"_parent\" href=\"{$_SESSION[CANVAS_INSTANCE_URL]}/courses/{$course['id']}/sections/{$response['id']}\">{$response['name']}</a>";

                        /* too many sections to (easily) normalize */
                        } else {
                            $toolbox->smarty_addMessage(
                                'Multiple Sections',
                                "<a target=\"_parent\" href=\"{$_SESSION[CANVAS_INSTANCE_URL]}/courses/{$course['id']}/settings\">{$course['name']}</a> has more than one section, which means that standard singleton-section normalization does not apply.",
                                NotificationMessage::WARNING
                            );
                        }
                    } catch (Exception $e) {
                        $toolbox->exceptionErrorMessage($e);
                    }
                }
                $toolbox->smarty_addMessage(
                    'Sections Normalized',
                    'The following singleton course sections have been normalized to match their parent course title: ' . $links,
                    NotificationMessage::GOOD
                );
            } else {
                $toolbox->smarty_addMessage(
                    'Missing Constraint',
                    'You must either upload a list of courses and sections, or select an account and/or term within which to normalize sections.',
                    NotificationMessage::ERROR
                );
            }
        }

        /* flow into STEP_INSTRUCTIONS */

    case STEP_INSTRUCTIONS:
    default:
        $toolbox->smarty_assign('accounts', $toolbox->getAccountList());
        $toolbox->smarty_assign('terms', $toolbox->getTermList());

        $toolbox->smarty_assign('formHidden', array('step' => STEP_NORMALIZE));
        $toolbox->smarty_display(basename(__FILE__, '.php') . '/instructions.tpl');
}
