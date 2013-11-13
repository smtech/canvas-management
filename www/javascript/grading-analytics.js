function stmarks_addGradingAnalyticsButton(sidebarButtons) {
	var analyticsUrl = /courses\/\d+\/analytics/;
	var courseAnalyticsButton = null;
	for (var i = 0; i < sidebarButtons.length; i++) { if (analyticsUrl.test(sidebarButtons[i].href)) { courseAnalyticsButton = sidebarButtons[i]; } }
	if (courseAnalyticsButton != null) {
		var courseUrl = /.*\/courses\/(\d+).*/;
		var courseId = document.location.href.match(courseUrl)[1];
		var gradingAnalyticsButton = document.createElement('a');
		gradingAnalyticsButton.target = '_blank';
		gradingAnalyticsButton.href = 'http://area51.stmarksschool.org/project/canvas/dev/grading-analytics/course-summary.php?course_id=' + courseId;
		gradingAnalyticsButton.className = 'btn button-sidebar button-sidebar-wide';
		gradingAnalyticsButton.innerHTML = '<span class="analytics-button-icon">&nbsp;</span> View Grading Analytics';
		courseAnalyticsButton.parentElement.appendChild(gradingAnalyticsButton);
	}
}

function stmarks_gradingAnalytics() {
	var identity = document.getElementById('identity').children[0].children[0].href;
	switch (identity) {
		case 'https://stmarksschool.instructure.com/about/1': //sb
		case 'https://stmarksschool.instructure.com/about/6': //bl
		case 'https://stmarksschool.instructure.com/about/9': //cc
		case 'https://stmarksschool.instructure.com/about/1010': //ls
		case 'https://stmarksschool.instructure.com/about/21': //nw
		case 'https://stmarksschool.instructure.com/about/379': //mw
			stmarks_waitForDOMByClassName(/courses\/\d+/, 'btn button-sidebar button-sidebar-wide', stmarks_addGradingAnalyticsButton);
			return true;
		default:
			return false;
	}
}
