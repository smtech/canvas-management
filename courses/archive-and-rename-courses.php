<?php

require_once 'common.inc.php';

use Battis\BootstrapSmarty\NotificationMessage;

define('STEP_INSTRUCTIONS', 1);
define('STEP_CONCLUDE', 2);

$step = (empty($_REQUEST['step']) ? STEP_INSTRUCTIONS : $_REQUEST['step']);

switch ($step) {
    case STEP_CONCLUDE:
        /* calculate the proper term prefix/suffix */
        $term = getTermList()[$_REQUEST['term']];
        $isYear = strpos($term['sis_term_id'], 'year') !== false;
        preg_match(
            '/(\d{4,4}-\d{4,4}) ((St. Mark\'s Saturdays \((Fall|Winter|Spring)\))|' .
                '((Fall|Spring) Semester)|(Full Year))/',
            $term['name'],
            $matches
        );
        $termPrefix = $matches[1];
        $termSuffix = '';
        if (!empty($matches[3])) {
            $termSuffix = $matches[4];
        } elseif (!empty($matches[5])) {
            $termSuffix = $matches[6];
        }

        $coursesRenamed = 0;
        $sectionsRenamed = 0;

        try {
            /* get a list of all courses that match account/term and have enrollments */
            $courses = $api->get("accounts/{$_REQUEST['account']}/courses", [
                'enrollment_term_id' => $_REQUEST['term'],
                'with_enrollments' => true,
                'include' => ['teachers'],
            ]);
            foreach ($courses as $course) {
                /* calculate specific prefix for this course */
                $teachers = [];
                foreach ($course['teachers'] as $teacher) {
                    $teachers[] = $teacher['display_name'];
                }
                $teachers = array_unique($teachers);

                /* calculate archived course name to include term/teacher metadata */
                $originalName = preg_replace('/^(\d{4,4}-\d{4,4} (\((Fall|Spring)\) )?)?(.*)$/', '$4', $course['name']);
                $suffixes = [];
                if (!empty($termSuffix)) {
                    $suffixes[] = $termSuffix;
                }
                if (preg_match('/ \((Red|Orange|Yellow|Green|Blue|Plum|Brown)\)$/', $originalName)) {
                    preg_match('/^(.*) \((Red|Orange|Yellow|Green|Blue|Plum|Brown)\)$/', $originalName, $match);
                    $originalName = $match[1];
                    $suffixes[] = $match[2];
                }
                if (!empty($teachers)) {
                    $suffixes[] = implode(', ', $teachers);
                }

                $concludedName = "$termPrefix $originalName" .
                    (empty($suffixes) ? '' : " (" . implode(', ', $suffixes) . ")");

                /* rename sections to match in single-section courses */
                foreach ($api->get("courses/{$course['id']}/sections") as $section) {
                    if ($section['name'] == $course['name']) {
                        $api->put("sections/{$section['id']}", [
                            'course_section[name]' => $concludedName
                        ]);
                        $sectionsRenamed++;
                    }
                }

                /* rename course */
                $api->put("courses/{$course['id']}", [
                    /* also rename any courses named under previous regime that were missing teachers */
                    'course[name]' => $concludedName
                ]);
                $coursesRenamed++;
            }
        } catch (Exception $e) {
            exceptionErrorMessage($e);
        }

        $smarty->addMessage(
            "{$term['name']} archived and renamed",
            "$coursesRenamed courses and $sectionsRenamed sections were renamed to match standard conventions. See " .
                    "<a target=\"_top\" href=\"{$_SESSION['canvasInstanceUrl']}/accounts/{$_REQUEST['account']}" .
                    "?enrollment_term_id={$_REQUEST['term']}&hide_enrollmentless_courses=1\">" .
                    'list of affected courses</a>.',
            NotificationMessage::GOOD
        );

        /* flows into STEP_INSTRUCTIONS */
    case STEP_INSTRUCTIONS:
    default:
        $smarty->assign([
            'formHidden' => [
                'step' => STEP_CONCLUDE
            ],
            'accounts' => getAccountList(),
            'terms' => getTermList()
        ]);
        $smarty->display(basename(__FILE__, '.php') . '/instructions.tpl');
}
