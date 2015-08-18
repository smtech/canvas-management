<?php

require_once('../common.inc.php');
use smtech\StMarksColors as col;

define('ACADEMICS_SUBACCOUNT', 132);

$cache = new Battis\HierarchicalSimpleCache($sql, 'color-code-courses');

/* make a list of currently active terms */
$terms = $canvasManagement->api->get('accounts/1/terms', array('workflow_state' => 'active'));
$activeTerms = array();
foreach($terms as $term) {
	if ((empty($term['start_at']) || strtotime($term['start_at']) <= time()) && (empty($term['end_at']) || strtotime($term['end_at']) >= time())) {
		$activeTerms[] = $term;
	}
}

/* make a list of colors */
$colors = implode('|', col::all());

/* get all the academic courses in the active terms... */
foreach($activeTerms as $term) {
	$courses = $canvasManagement->api->get('accounts/' . ACADEMICS_SUBACCOUNT . '/courses', array('enrollment_term_id' => $term['id'], 'state' => 'available'));
	
	/* ...and get their sections... */
	foreach ($courses as $course) {
		
		/* ...and figure out their _original_ course SIS ID... */
		$sections = $canvasManagement->api->get("courses/{$course['id']}/sections");
		foreach($sections as $section) {
			$sis_course_id = $cache->getCache($section['sis_section_id']);
			if ($sis_course_id == false) {
				$parentCourse = $course;
				if (!empty($section['nonxlist_course_id'])) {
					$parentCourse = $canvasManagement->get("courses/{$section['nonxlist_course_id']}");
				}
				$sis_course_id = $parentCourse['sis_course_id'];
				$cache->setCache($section['sis_section_id', $sis_course_id);
			}
			
			/* ...figure out the proper block color... */
			preg_match("/($colors)/i", $sis_course_id, $match);
			$color = col::get($match[1])->value();
			
			/* ...and set it for all enrolled users. */
			$enrollments = $canvasManagement->api->get("sections/{$section['id']}/enrollments", array('state' => 'active'));
			foreach($enrollments as $enrollment) {
				$canvasManagement->api->put("users/{$enrollment['user']['id']}/colors/course_{$course['id']}", array('hexcode', $color));
			}

		}
	}
}
	
?>