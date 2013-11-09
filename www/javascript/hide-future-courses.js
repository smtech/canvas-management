/*jslint browser: true, devel: true, eqeq: true, plusplus: true, sloppy: true, vars: true, white: true */

function stmarks_hideFutureCourses() {
	var i, futurePastEnrollments;

	// remove past courses from future list
	futurePastEnrollments = document.getElementsByClassName('future_enrollments')[0].querySelectorAll('.completed');
	for (i = futurePastEnrollments.length - 1; i >= 0; i--) {
		futurePastEnrollments[i].parentNode.removeChild(futurePastEnrollments[i]);
	}
}