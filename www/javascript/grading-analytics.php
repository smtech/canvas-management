<?php

require_once(__DIR__ . '/../config.inc.php');

header('Content-type: text/javascript');

?>

function stmarks_addGradingAnalyticsButton(sidebarButtons) {
	var analyticsUrl = /courses\/\d+\/analytics/;
	var courseAnalyticsButton = null;
	for (var i = 0; i < sidebarButtons.length; i++) { if (analyticsUrl.test(sidebarButtons[i].href)) { courseAnalyticsButton = sidebarButtons[i]; } }
	if (courseAnalyticsButton != null) {
		var courseUrl = /.*\/courses\/(\d+).*/;
		var courseId = document.location.href.match(courseUrl)[1];
		var gradingAnalyticsButton = document.createElement('a');
		gradingAnalyticsButton.target = '_blank';
		gradingAnalyticsButton.href = 'http://<?= APP_URL ?>/grading-analytics/course-summary.php?course_id=' + courseId;
		gradingAnalyticsButton.className = 'btn button-sidebar button-sidebar-wide';
		gradingAnalyticsButton.innerHTML = '<span class="analytics-button-icon">&nbsp;</span> View Grading Analytics';
		courseAnalyticsButton.parentElement.appendChild(gradingAnalyticsButton);
	}
}

function stmarks_gradingAnalytics() {
	stmarks_waitForDOMByClassName(/courses\/\d+/, 'btn button-sidebar button-sidebar-wide', stmarks_addGradingAnalyticsButton);
}
