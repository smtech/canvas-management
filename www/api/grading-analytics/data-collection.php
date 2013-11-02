<?php

require_once('config.inc.php');
require_once('.ignore.grading-analytics-authentication.inc.php');
require_once('../canvas-api.inc.php');
require_once('../mysql.inc.php');


function collectStatistics($term) {
	$coursesApi = new CanvasApiProcess(CANVAS_API_URL, CANVAS_API_TOKEN);
	$assignmentsApi = new CanvasApiProcess(CANVAS_API_URL, CANVAS_API_TOKEN);
	$lookupApi = new CanvasApiProcess(CANVAS_API_URL, CANVAS_API_TOKEN);
	
	// TODO make this configurable
	$courses = $coursesApi->get(
		'/accounts/132/courses',
		array(
			'with_enrollments' => 'true',
			'enrollment_term_id' => $term // FIXME: this is only 2013-2014 Full year
		)
	);
	
	// so that everything has a consistent benchmark
	$timestamp = time();
	
	do {
		foreach ($courses as $course) {
			$statistic = array(
				'timestamp' => date(DATE_ISO8601, $timestamp),
				'course[id]' => $course['id'],
				'gradebook_url' => 'https://' . parse_url(CANVAS_API_URL, PHP_URL_HOST) . "/courses/{$course['id']}/gradebook2",
				'assignment_count' => 0,
				'graded_assignment_count' => 0,
				'zero_point_assignment_count' => 0
			);
		
			$teacherIds = array();
			$teachers = $lookupApi->get(
				"/courses/{$course['id']}/enrollments",
				array(
					'type[]' => 'TeacherEnrollment'
				)
			);
			do {
				foreach ($teachers as $teacher) {
					$teacherIds[] = $teacher['id'];
				}
			} while ($teachers = $lookupApi->nextPage());
			$statistic['teacher_ids'] = serialize($teacherIds);
		
			$statistic['student_count'] = 0;
			$students = $lookupApi->get(
				"/courses/{$course['id']}/enrollments",
				array(
					'type[]' => 'StudentEnrollment',
					'per_page' => 50
				)
			);
			do {
				$statistic['student_count'] += count($students);
			} while ($students = $lookupApi->nextPage());
		
			$assignments = $assignmentsApi->get(
				"/courses/{$course['id']}/assignments"
			);
			do {
				$gradedSubmissionsCount = 0;
				$turnAroundTimeTally = 0;
				
				foreach ($assignments as $assignment) {
					
					$dueDate = new DateTime($assignment['due_at']);
					if ($timestamp - $dueDate->getTimestamp() > 0) {
						
						if ($assignment['grading_type'] != 'not_graded')
						{
							$statistic['assignment_count']++;
							$hasBeenGraded = false;
							
							if ($assignment['points_possible'] == '0') {
								$statistic['zero_point_assignment_count']++;
							}
							
							$submissions = $lookupApi->get(
								"/courses/{$course['id']}/assignments/{$assignment['id']}/submissions"
							);
							do {
								foreach ($submissions as $submission) {
									if ($submission['workflow_state'] == 'graded') {
										if ($hasBeenGraded == false) {
											$hasBeenGraded = true;
											$statistic['graded_assignment_count']++;
										}
										$gradedSubmissionsCount++;
										$turnAroundTimeTally += strtotime($submission['graded_at']) - strtotime($assignment['due_at']);
									}
								}
							} while ($submissions = $lookupApi->nextPage());
							
							if (!$hasBeenGraded) {
								if (array_key_exists('oldest_ungraded_assignment_due_date', $statistic)) {
									if (strtotime($assignment['due_at']) < strtotime($statistic['oldest_ungraded_assignment_due_date'])) {
										$statistic['oldest_ungraded_assignment_due_date'] = $assignment['due_at'];
										$statistic['oldest_ungraded_assignment_url'] = $assignment['html_url'];
									}
								} else {
									$statistic['oldest_ungraded_assignment_due_date'] = $assignment['due_at'];
									$statistic['oldest_ungraded_assignment_url'] = $assignment['html_url'];
								}
							}
						}
					}
				}
			} while ($assignments = $assignmentsApi->nextPage());
			
			if ($statistic['assignment_count'] && $statistic['student_count']) {
				$statistic['average_submissions_graded'] = $gradedSubmissionsCount / $statistic['assignment_count'] / $statistic['student_count'];
			}
			
			if ($gradedSubmissionsCount) {
				$statistic['average_grading_turn_around'] = $turnAroundTimeTally / $gradedSubmissionsCount / 60 / 60 / 24;
			}
			
			$query = "INSERT INTO `course_statistics`";
			$fields = array();
			$values = array();
			while (list($field, $value) = each($statistic)) {
				$fields[] = $field;
				$values[] = $value;
			}
			$query .= ' (`' . implode('`, `', $fields) . "`) VALUES ('" . implode("', '", $values) . "')";
			$result = mysqlQuery($query);
			displayError(
				array(
					'gradedSubmissionsCount' => $gradedSubmissionsCount,
					'turnAroundTimeTally' => $turnAroundTimeTally,
					'statistic' => $statistic,
					'query' => $query,
					'result' => $result
				),
				true
			);
		}
	} while ($courses = $coursesApi->nextPage());
}

debugFlag('START');

collectStatistics(106);
collectStatistics(107);

debugFlag('FINISH');

?>