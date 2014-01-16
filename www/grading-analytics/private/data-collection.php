<?php
require_once(__DIR__ . '/../../config.inc.php');
require_once(__DIR__ . '/../config.inc.php');
require_once(__DIR__ . '/../.ignore.grading-analytics-authentication.inc.php');
require_once(APP_PATH . '/include/canvas-api.inc.php');
require_once(APP_PATH . '/include/mysql.inc.php');


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
				'course[account_id]' => $course['account_id'],
				'gradebook_url' => 'https://' . parse_url(CANVAS_API_URL, PHP_URL_HOST) . "/courses/{$course['id']}/gradebook2",
				'assignments_due_count' => 0,
				'dateless_assignment_count' => 0,
				'gradeable_assignment_count' => 0,
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
			
			// ignore classes with no teachers (how do they even exist? weird.)
			if (count($teacherIds) != 0) {
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
				
				// ignore classes with no students
				if ($statistic['student_count'] != 0) {
					$assignments = $assignmentsApi->get(
						"/courses/{$course['id']}/assignments"
					);
					do {
						$gradedSubmissionsCount = 0;
						$turnAroundTimeTally = 0;
						
						foreach ($assignments as $assignment) {
							
							// check for due dates
							$dueDate = new DateTime($assignment['due_at']);
							if ($timestamp - $dueDate->getTimestamp() > 0) {
								$statistic['assignments_due_count']++;
								
								// ignore ungraded assignments
								if ($assignment['grading_type'] != 'not_graded')
								{
									$statistic['gradeable_assignment_count']++;
									$hasBeenGraded = false;
									
									// ignore (but tally) zero point assignments
									if ($assignment['points_possible'] == '0') {
										$statistic['zero_point_assignment_count']++;
									} else {
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
							} else {
								$statistic['dateless_assignment_count']++;
							}
						}
					} while ($assignments = $assignmentsApi->nextPage());
					
					// calculate average submissions graded per assignment (if non-zero)
					if ($statistic['gradeable_assignment_count'] && $statistic['student_count']) {
						$statistic['average_submissions_graded'] = $gradedSubmissionsCount / $statistic['gradeable_assignment_count'] / $statistic['student_count'];
					}
					
					// calculate average grading turn-around per submission
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
					/* displayError(
						array(
							'gradedSubmissionsCount' => $gradedSubmissionsCount,
							'turnAroundTimeTally' => $turnAroundTimeTally,
							'statistic' => $statistic,
							'query' => $query,
							'result' => $result
						),
						true
					); */
				}
			}
		}
	} while ($courses = $coursesApi->nextPage());
}

debugFlag('START');

// FIXME a more elegant solution would be to query for terms that are currently active and then loop across them
collectStatistics(106);
collectStatistics(107);

/* check to see if this data collection has been scheduled. If it hasn't,
   schedule it to run nightly. */
/* thank you http://stackoverflow.com/a/4421284 ! */
$crontab = DATA_COLLECTION_CRONTAB . ' ' . realpath('.') . '/data-collection.sh';
$crontabs = shell_exec('crontab -l');
if (strpos($crontabs, $crontab) === false) {
	$filename = md5(time()) . '.txt';
	file_put_contents("/tmp/$filename", $crontabs . $crontab . PHP_EOL);
	shell_exec("crontab /tmp/$filename");
	debugFlag("added new scheduled data-collection to crontab");
}

debugFlag('FINISH');

?>