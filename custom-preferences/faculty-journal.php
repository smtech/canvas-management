<?php

require_once 'common.inc.php';

$faculty = $customPrefs->query("SELECT * FROM `users` WHERE `role` = 'faculty'");

$csv[] = [
    'user[id]',
    'user[name]',
    'course',
    'view[url]',
    'view[created_at]',
    'date',
    'time'
];
while ($teacher = $faculty->fetch_assoc()) {
    $user = $toolbox->api_get("users/{$teacher['id']}");
    $views = $toolbox->api_get(
        "users/{$teacher['id']}/page_views",
        [
            'start_time' => $_REQUEST['start'],
            'end_time' => $_REQUEST['end']
        ]
    );
    if ($views->count() == 0) {
        $csv[] = [
            $user['id'],
            $user['name']
        ];
    } else {
        foreach($views as $view) {
            if (preg_match('%.*/user_notes\??.*%', $view['url']) && !preg_match('/Advisory%20Group/', $view['url'])) {
                preg_match('/.*course_name=(.*)/', $view['url'], $match);
                $timestamp = new DateTime($view['created_at'], new DateTimeZone('America/New_York'));
                $csv[] = [
                    $user['id'],
                    $user['name'],
                    (isset($match[1]) ? urldecode($match[1]) : ''),
                    $view['url'],
                    $view['created_at'],
                    $timestamp->format('n/j/Y'),
                    $timestamp->format('H:i:s')
                ];
            }
        }
    }
}

$key = md5(time());
$toolbox->cache_set($key, $csv);
$key = $toolbox->getCache()->getHierarchicalKey($key);
header("Location: ../generate-csv.php?data=$key&filename=faculty-journal.csv");
exit;
