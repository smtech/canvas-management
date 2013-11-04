function stmarks_gradingAnalytics() {
	// FIXME wait for sidebar buttons to be loaded!
	var sidebarButtons = document.getElementsByClassName('btn button-sidebar button-sidebar-wide');
	var analyticsUrl = /courses\/\d+\/analytics/;
	var courseAnalyticsButton = null;
	for (var i = 0; i < sidebarButtons.length; i++) { if (analyticsUrl.test(sidebarButtons[i].href)) { courseAnalyticsButton = sidebarButtons[i]; } }
	if (courseAnalyticsButton != null) {
		var gradingAnalyticsButton = document.createElement('a');
		gradingAnalyticsButton.href = 'https://area51.stmarksschool.org';
		gradingAnalyticsButton.className = 'btn button-sidebar button-sidebar-wide';
		gradingAnalyticsButton.innerHTML = '<span class="analytics-button-icon">&nbsp;</span> View Grading Analytics';
		courseAnalyticsButton.parentElement.appendChild(gradingAnalyticsButton);
	}
}
