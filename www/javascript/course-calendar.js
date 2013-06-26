/* including a pause because the page content is loaded dynamically --
   so it needs to be there before we start messing with it */
var delay = 1000; // 1 second = 1000 milliseconds

var anchorLink = 'Announcements'; // which link is Course Calendar attached to?
// TODO: right now the Course Calendar is added above the anchor link -- should be configurable (per account) as above or below

var linkText = 'Course Calendar'; // text of the Course Calendar link

function addCourseCalendar() {
	var section_tabs = document.getElementById('section-tabs');
	var i;
	var path = document.location.pathname;
	var href = path.replace(new RegExp('.*/courses/(\\d+).*', 'i'), '/calendar2?include_contexts=course_$1');

	if (section_tabs) {
		var done = false;
		var calendar = document.createElement('li');
		calendar.setAttribute('class', 'section');
		var link = document.createElement('a');
		link.setAttribute('target', '_self');
		link.setAttribute('href', href);
		link.innerHTML = linkText;
		calendar.appendChild(link);
		for (i = 0; i < section_tabs.childNodes.length && !done; ++i) {
			if (section_tabs.childNodes[i].childNodes[0].innerHTML == anchorLink) {
				section_tabs.insertBefore(calendar, section_tabs.childNodes[i]);
				done = true;
			}
		}
		if (!done) {
			section_tabs.appendChild(calendar);
		}
	}
}

window.setTimeout(addCourseCalendar, delay);