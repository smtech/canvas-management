/*jslint browser: true, devel: true, eqeq: true, plusplus: true, sloppy: true, vars: true, white: true */

function stmarks_hideFutureCourses() {
	var i;
	var coursesMenu = document.getElementById('menu_enrollments').childNodes[3].childNodes;
	
	// TODO: isFaculty is useful enough to warrant a separate file, no?
	var isFaculty = false;
	for (i = 1; i < coursesMenu.length; i += 2) {
		if (coursesMenu[i] instanceof HTMLLIElement && coursesMenu[i].getAttribute('data-id') === '97') {
			isFaculty = true;
		}
	}
	
	if (isFaculty) {
		return;
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