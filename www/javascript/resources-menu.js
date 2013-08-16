/*jslint browser: true, devel: true, eqeq: true, plusplus: true, sloppy: true, vars: true, white: true */

// types of user
var USER_CLASS_STUDENT = 'student';
var USER_CLASS_FACULTY = 'faculty';
var userClass = USER_CLASS_STUDENT;

// define your menu here
var customMenu = {
	// menu title
	// can also have a url property, so clicking the menu title sends you somewhere
	// can also have a target property, so clicking the menu title opens in a specific frame/window/tab
	title: 'Resources',
	// an array of columns to display (don't go overboard here!)
	columns: [
		{
			sections: [
				{
					title: 'Lion Hub',
					items: [
						{
							title: 'Email',
							subtitle: 'Google Apps for Education',
							target: '_blank',
							url: 'http://mail.stmarksschool.org'
						},
						{
							title: 'Weekend Activities Sign-ups',
							target: '_blank',
							url: 'http://www2.stmarksschool.org',
							userClass: [USER_CLASS_STUDENT]
						},
						{
							title: 'FAWeb',
							subtitle: 'Grades &amp; Comments',
							target: '_blank',
							url: 'http://faweb.stmarksschool.org',
							userClass: [USER_CLASS_FACULTY]
						},
						{
							title: 'NetClassroom',
							subtitle: 'Course Registration',
							target: '_blank',
							url: 'http://netclassroom.stmarksschool.org'
						},
						{
							title: 'Help Desk',
							subtitle: 'Technology Issues',
							target: '_blank',
							url: 'http://helpdesk.stmarksschool.org',
							userClass: [USER_CLASS_FACULTY]
						},
						{
							title: 'School Dude',
							subtitle: 'School ID 615666807',
							target: '_blank',
							url: 'http://www.myschoolbuilding.com/myschoolbuilding/msbdefault_email.asp?frompage=myrequest.asp',
							userClass: [USER_CLASS_FACULTY]
						},
						{
							title: 'Communications Request',
							target: '_blank',
							url: 'http://www.stmarksschool.org/about-st-marks/communications-department/index.aspx',
							userClass: [USER_CLASS_FACULTY]
						},
						{
							title: 'Google Drive',
							target: '_blank',
							url: 'http://drive.google.com/a/stmarksschool.org/'
						},
						{
							title: 'Minerva Web Access',
							target: '_blank',
							url: 'http://minerva.stmarksschool.org/',
							userClass: [USER_CLASS_FACULTY]
						},
						{
							title: 'Athena Web Access',
							target: '_blank',
							url: 'http://athena.stmarksschoo.org/',
							userClass: [USER_CLASS_STUDENT]
						},
						{
							title: 'SMS',
							target: '_blank',
							url: 'http://sms.stmarksschool.org'
						},
						{
							title: 'FLIK Menu',
							target: '_blank',
							url: 'http://www.myschooldining.com/SMS/?cmd=menus'
						},
						{
							title: 'Athletics',
							target: '_blank',
							url: 'http://www.stmarksschool.org/athletics/teamlisting.aspx'
						},
						{
							title: 'Lynda.com',
							target: '_blank',
							url: 'http://www.lynda.com'
						},
						{
							title: 'Tech Support Documents',
							target: '_blank',
							url: 'http://www.stmarksschool.org/academics/technology/Tech-Docs/index.aspx'
						},
						{
							title: 'Human Resource Documents',
							target: '_blank',
							url: 'https://lionhub.stmarksschool.org/pages/human-resource-documents',
							userClass: [USER_CLASS_FACULTY]
						}
					]
				}
			]
		},
		{
			// columns can be divided into titled sections
			sections: [
				{
					// title, target, url are all properties of columns
					title: 'The Center',
					items: [
						// each item can have title, subtitle, target and url
						{title: 'Writing Lab', subtitle: 'in The Center', url: '/courses/495'},
						{title: 'Mathematics Lab', subtitle: 'in The Center', url: '/courses/494'},
						{title: 'Resources for Students', subtitle: 'from The Center', url: '/courses/491'},
						{title: 'Resources for Faculty', subtitle: 'from The Center', url: '/courses/492', userClass: [USER_CLASS_FACULTY]}
					]
				},
				{
					title: 'Work Process',
					items: [
						{title: 'Library', url: 'http://library.stmarksschool.org', subtitle: 'Catalog, Online Resources, References', target: '_blank'},
						{title: 'Writing Manual', subtitle: 'Strunk &amp; White @ SM'},
						{title: 'Faculty Resources', subtitle: 'Calendars, Forms, Policies, Guides', url: '/courses/97', userClass: [USER_CLASS_FACULTY]}
					]
				}
			]
		}
	]
};

// if the Faculty Resources item is in the Courses menu, this person is faculty and should get faculty-specific resources
var facultyCourseId = '956'; // 97 on live server, 956 on test (for now)
function setUserClass() {
	var i;
	var coursesMenu = document.getElementById('menu_enrollments').childNodes[3].childNodes;
	for (i = 1; i < coursesMenu.length; i += 2) {
		if (coursesMenu[i] instanceof HTMLLIElement && coursesMenu[i].getAttribute('data-id') === facultyCourseId) {
			userClass = USER_CLASS_FACULTY;
			coursesMenu[i].parentNode.removeChild(coursesMenu[i]);
			return;
		}
	}
}

// parse the array/object structure above into the HTML that represents a dropdown menu and add it to the right of the existing menubar
function appendMenu(m) {
	var i, j, k;

	var navigationMenu = document.getElementById("menu");
	var menu = document.createElement('li');
	menu.setAttribute('class', 'menu-item');
	var html = '<a class="menu-item-title"' + (m.url !== undefined ? ' href="' + m.url + '"' : '') + (m.target !== undefined ? ' target="' + m.target + '"' : '') + '>' + m.title + '<span class="menu-item-title-icon"/> <i class="icon-mini-arrow-down"/></a>';

	html += '<div class="menu-item-drop"><table cellspacing="0"><tr>';

	for(i = 0; i < m.columns.length; i++) {
		if (!m.columns[i].userClass || m.columns[i].userClass.indexOf(userClass) > -1)
		{
			html += '<td class="menu-item-drop-column">';
			for (j = 0; j < m.columns[i].sections.length; j++) {
				if (!m.columns[i].sections[j].userClass || m.columns[i].userClass.indexOf(userClass) > -1) {
					html += (m.columns[i].sections[j].title !== undefined ? '<span class="menu-item-heading">' + m.columns[i].sections[j].title + '</span>' : '');
					html += '<ul class="menu-item-drop-column-list">';
		
					for (k = 0; k < m.columns[i].sections[j].items.length; k++) {
						if (!m.columns[i].sections[j].items[k].userClass || m.columns[i].sections[j].items[k].userClass.indexOf(userClass) > -1) {
							html += '<li><a' + (m.columns[i].sections[j].items[k].target !== undefined ? ' target="' + m.columns[i].sections[j].items[k].target + '"' : '') + (m.columns[i].sections[j].items[k].url !== undefined ? ' href="' + m.columns[i].sections[j].items[k].url + '"' : '') + '><span class="name ellipsis">' + m.columns[i].sections[j].items[k].title + '</span>' + (m.columns[i].sections[j].items[k].subtitle !== undefined ? '<span class="subtitle">' + m.columns[i].sections[j].items[k].subtitle + '</span>' : '') + '</a></li>';
						}
					}
					html += '</ul>';
				}
			}
			html += '</td>';
		}
	}
	html += '</tr></table></div>';
	menu.innerHTML = html;
	navigationMenu.appendChild(menu);
}

// add the custom menu to the menubar
// if you wanted to add more menus, define another menu structure like customMenu and call appendMenu() with it as a parameter (menus would be added in the order that the appendMenu() calls occur)
setUserClass();
appendMenu(customMenu);