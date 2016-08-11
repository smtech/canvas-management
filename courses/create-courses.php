<?php

require_once __DIR__ . '/common.inc.php';

use Battis\DataUtilities;
use Battis\BootstrapSmarty\NotificationMessage;

$MANUALLY_CREATED_COURSES_ACCOUNT = 96;
$DEFAULT_TERM = 195;

/**
 * Generate a unique SIS ID
 *
 * @param string $name
 *
 * @return string
 **/
function generateSisId($name)
{
    return strtolower(preg_replace('/[^a-z0-9\-]+/i', '-', $_REQUEST['prefix'] .
        $name . $_REQUEST['suffix']) . (empty($_REQUEST['unique']) ? '' : '.' .
        md5(time())));
}

define('STEP_INSTRUCTIONS', 1);
define('STEP_RESULT', 2);

$step = (empty($_REQUEST['step']) ? STEP_INSTRUCTIONS : $_REQUEST['step']);

switch ($step) {
    case STEP_RESULT:
        // TODO use $toolbox->explodeNewLines()
        $courses = array();
        $courseNames = explode("\n", $_REQUEST['courses']);
        foreach ($courseNames as $name) {
            if (!empty(trim($name))) {
                $courses[]['long_name'] = trim($name);
            }
        }

        if (empty($_REQUEST['account'])) {
            $toolbox->smarty_addMessage(
                'Account',
                'was not selected, defaulting to the <a target=\"_parent\" href="' . $_SESSION[CANVAS_INSTANCE_URL] . '/accounts/' . $MANUALLY_CREATED_COURSES_ACCOUNT . '">Manually-Created Courses</a> account.',
                NotificationMessage::WARNING
            );
            $account = $MANUALLY_CREATED_COURSES_ACCOUNT;
        } else {
            $account = $_REQUEST['account'];
        }

        if (empty($_REQUEST['term'])) {
            $toolbox->smarty_addMessage(
                'Term',
                'was not selected, defaulting to the Default Term.',
                NotificationMessage::WARNING
            );
            $term = $DEFAULT_TERM;
        } else {
            $term = $_REQUEST['term'];
        }

        $templated = false;
        if (empty($_REQUEST['template'])) {
            $toolbox->smarty_addMessage(
                'Template',
                'was not entered, courses are in default configuration.'
            );
        } else {
            $templated = true;
            $template = (is_int($_REQUEST['template']) ? $_REQUEST['template'] : "sis_course_id:{$_REQUEST['template']}");

            /* pull course settings as completely as possible */
            try {
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
            } catch (Exception $e) {
                $toolbox->exceptionErrorMessage($e);
            }
        }

        $csv = DataUtilities::loadCsvToArray('csv');
        if ($csv) {
            $courses = array_merge($courses, $csv);
        }

        if (!empty($courses)) {
            foreach ($courses as $course) {
                /* build parameter list */
                $params = array();

                if (!empty($course['course_id'])) {
                    $params['sis_course_id'] = $course['course_id'];
                } else {
                    $params['sis_course_id'] = generateSisId((empty($course['course_id']) ? $course['long_name'] : $course['course_id']));
                }

                if (!empty($course['long_name'])) {
                    $params['name'] = $course['long_name'];
                }

                if (!empty($course['short_name'])) {
                    $params['course_code'] = $course['short_name'];
                } elseif (!empty($params['name'])) {
                    $params['course_code'] = $params['name'];
                }

                if (!empty($course['account_id'])) {
                    $_account = "sis_account_id:{$course['account_id']}";
                } else {
                    $_account = $account;
                }

                if (!empty($course['term_id'])) {
                    $params['term_id'] = "sis_term_id:{$course['term_id']}";
                } elseif (!empty($term)) {
                    $params['term_id'] = $term;
                }

                if (!empty($course['start_at'])) {
                    $params['start_at'] = $course['start_at'];
                }

                if (!empty($course['end_at'])) {
                    $params['end_at'] = $course['end_at'];
                }

                /* create course */
                try {
                    $course = $toolbox->api_post(
                        "accounts/$_account/courses",
                        array(
                            'course' => $params
                        )
                    );

                    if ($templated) {
                        /* duplicate course settings */
                        $toolbox->api_put("/courses/{$course['id']}", $source);

                        // TODO  nice to figure out navigation settings copy

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
                            "has been created as a clone of <a target=\"_parent\" href=\"{$_SESSION[CANVAS_INSTANCE_URL]}/courses/$sourceId\">$sourceName</a>. Course content is being <a target=\"_parent\" href=\"{$_SESSION[CANVAS_INSTANCE_URL]}/courses/{$course['id']}/content_migrations\">migrated</a> right now.",
                            NotificationMessage::GOOD
                        );
                    } else {
                        $toolbox->smarty_addMessage(
                            "<a target=\"_parent\" href=\"{$_SESSION[CANVAS_INSTANCE_URL]}/courses/{$course['id']}\">{$course['name']}</a>",
                            "has been created.",
                            NotificationMessage::GOOD
                        );
                    }
                } catch (Exception $e) {
                    $toolbox->exceptionErrorMessage($e);
                }
            }
        } else {
            $toolbox->smarty_addMessage(
                'Courses',
                'No course names were entered',
                NotificationMessage::ERROR
            );
        }

        /* flow into STEP_INSTRUCTIONS */

    case STEP_INSTRUCTIONS:
    default:
        $toolbox->smarty_assign('accounts', $toolbox->getAccountList());
        $toolbox->smarty_assign('terms', $toolbox->getTermList());

        $toolbox->smarty_assign('formHidden', array('step' => STEP_RESULT));
        $toolbox->smarty_display(basename(__FILE__, '.php') . '/instructions.tpl');
}
