/*jslint browser: true, devel: true, eqeq: true, plusplus: true, sloppy: true, vars: true, white: true */

function stmarks_hideFutureCourses() {
	var i, futureEnrollments;

	// if we don't have userClass or futureEnrollemnts yet, retry soon!
	if (userClass === undefined || document.getElementsByClassName('future_enrollments').length == 0) {
		window.setTimeout(stmarks_hideFutureCourses, 25);
		return;
	}
	
	futureEnrollments = document.getElementsByClassName('future_enrollments')[0];
		
	// remove past courses from future list for faculty
	if (userClass == USER_CLASS_FACULTY) {
		var futurePastEnrollments = futureEnrollments.querySelectorAll('.completed');
		for (i = futurePastEnrollments.length - 1; i >= 0; i--) {
			futurePastEnrollments[i].parentNode.removeChild(futurePastEnrollments[i]);
		}
		
	// hide all future classes from students
	} else {
		futureEnrollments.parentNode.removeChild(futureEnrollments);
		var futureEnrollmentsHeader = document.getElementsByTagName('h2');
		for (i = 0; i < futureEnrollmentsHeader.length; i++) {
			if (futureEnrollmentsHeader[i].innerHTML == 'Future Enrollments') {
				futureEnrollmentsHeader[i].parentNode.removeChild(futureEnrollmentsHeader[i]);
			}
		}
	}
}