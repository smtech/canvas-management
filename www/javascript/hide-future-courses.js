/*jslint browser: true, devel: true, eqeq: true, plusplus: true, sloppy: true, vars: true, white: true */

function stmarks_hideFutureCourses() {
	var i;
	var isFaculty = false;
	
	// check for Faculty Resources course to identify USER_CLASS_FACULTY
	var coursesList = document.getElementById('menu_enrollments').children[1].children;
	// skip the "View all courses" link: length - 1
	for (i = 0; i < coursesList.length - 1; i++) {
		if (coursesList[i].getAttribute('data-id') === '97') {
			isFaculty = true;
		}
	}
	

	// remove past courses from future list for faculty
	if (isFaculty) {
		var futureEnrollments = document.getElementsByClassName('future_enrollments')[0];
		var futurePastEnrollments = futureEnrollments.querySelectorAll('.completed');
		for (i = futurePastEnrollments.length - 1; i >= 0; i--) {
			futurePastEnrollments[i].parentNode.removeChild(futurePastEnrollments[i]);
		}
		
	// hide all future classes from students
	} else {
		var futureEnrollments = document.getElementsByClassName('future_enrollments')[0];
		futureEnrollments.parentNode.removeChild(futureEnrollments);
		var futureEnrollmentsHeader = document.getElementsByTagName('h2');
		for (i = 0; i < futureEnrollmentsHeader.length; i++) {
			if (futureEnrollmentsHeader[i].innerHTML == 'Future Enrollments') {
				futureEnrollmentsHeader[i].parentNode.removeChild(futureEnrollmentsHeader[i]);
			}
		}
	}
}