/*jslint browser: true, devel: true, eqeq: true, plusplus: true, sloppy: true, todo: true, vars: true, white: true */

// types of user
var USER_CLASS_STUDENT = 'student';
var USER_CLASS_FACULTY = 'faculty';
var USER_CLASS_NO_MENU = 'no-menu';

var USER_DEPARTMENT_HISTORY = '/accounts/79';
var USER_DEPARTMENT_MODERN_LANGUAGE = '/accounts/83';
var USER_DEPARTMENT_ACADEMIC_TECHNOLOGY = 'academic-technology';

// default to hiding the menu
var userClass = USER_CLASS_NO_MENU;
var userDepartments = [];

/* define your menu here

   Some design thoughts:
   		* More than about a dozen items is too tall for older screens
   		* More than one column is often too wide for older screens
   		* Subtitles are nice... but hard to read and make entries taller
*/
var colorStripe = ''; //'background: #fffffe; border-bottom: #9FA7AF solid 1px; border-right: #9FA7AF solid 1px; border-top: #ffffff solid 1px; border-left: #ffffff solid 1px;';
var resources = {
	title: 'Resources', // required
	// url: optional,
	// target: optional,
	// an array of columns to display (don't go overboard here!)
	columns: [
		{
			// columns can be divided into titled sections
			sections: [
				{
					title: 'General',
					// style: colorStripe,
					userClass: [USER_CLASS_FACULTY],
					items: [
						{
							title: 'Faculty Resources',
							// subtitle: 'Calendars, Forms, Policies, Guides',
							url: '/courses/97'
						},
						{
							title: 'Global Citizenship',
							url: '/courses/672',
						},
						{
							title: 'Academic Technology',
							subtitle: 'Shared files',
							target: '_blank',
							url: 'https://drive.google.com/a/stmarksschool.org/?tab=co#folders/0Bx1atGpuKjk9UHJ3MU5nRms5Zms',
							userDepartments: [USER_DEPARTMENT_ACADEMIC_TECHNOLOGY]
						},
						{
							title: 'History Dept.',
							subtitle: 'Shared files',
							target: '_blank',
							url: 'https://drive.google.com/a/stmarksschool.org/?tab=mo#folders/0Bxkl1PbtN3mKa1B0Ym5nSXoxU2M',
							userDepartments: [USER_DEPARTMENT_HISTORY]
						},
						{
							title: 'Modern Language Dept.',
							url: '/courses/1294',
							userDepartments: [USER_DEPARTMENT_MODERN_LANGUAGE]
						}
					]
				},
				{
					// title, target, url are all properties of columns
					title: 'The Center for Innovation<br />in Teaching and Learning',
					items: [
						// each item can have title, subtitle, target and url
						{
							title: 'Information for Students',
							subtitle: 'Student Enrichment &amp; Academic Support',
							url: '/courses/491'
						},
						{
							title: 'Information for Faculty',
							subtitle: 'Profesional Development &amp; Academic Support',
							url: '/courses/97/wiki/the-center-for-innovation-in-teaching-and-learning',
							userClass: [USER_CLASS_FACULTY]
						},
						{
							title: 'Writing Lab',
							url: '/courses/491/wiki/writing-lab'
						},
						{
							title: 'Mathematics Lab',
							url: '/courses/491/wiki/mathematics-lab'
						}
					]
				},
				{
					title: 'Research &amp; Writing',
					// style: colorStripe,
					items: [
						{
							title: 'Library',
							url: 'http://library.stmarksschool.org',
							subtitle: 'Catalog, Online Resources, References',
							target: '_blank'
						},
						{
							title: 'Writing Manual',
							subtitle: 'All the steps you need to write',
							target: '_blank',
							url: 'https://drive.google.com/a/stmarksschool.org/folderview?id=0ByGbqFAT3Vy1aXdRY2hoNlY4WjA&usp=sharing'
						}
					]
				},
				{
					title: 'On Campus',
					// style: colorStripe,
					items: [
						{
							title: 'All School Information',
							// subtitle: 'Information, Organizations, Calendar',
							url: '/courses/1277'
						},
						{
							title: 'Weekend Activities Sign-ups',
							// subtitle: 'from the Dean of Students&rsquo; Office',
							target: '_blank',
							url: 'http://www2.stmarksschool.org',
							userClass: [USER_CLASS_STUDENT]
						},
						{
							title: 'FLIK Menu',
							// subtitle: 'Dining Services',
							target: '_blank',
							url: 'http://www.myschooldining.com/SMS/?cmd=menus'
						},
						{
							title: 'Athletics',
							// subtitle: 'Schedules, Scores and News',
							target: '_blank',
							url: 'http://www.stmarksschool.org/athletics/teamlisting.aspx'
						}
					]
				}
			]
		}
	]
};

var lionHub = {
	title: 'Lion Hub',
	columns: [
		{
			// style: 'optional CSS goes here',
			sections: [
				{
					title: 'Training &amp; Support',
					items: [
						{
							title: 'Canvas Training',
							// subtitle: 'What you need to know',
							url: '/courses/489',
							userClass: [USER_CLASS_FACULTY]
						},
						{
							title: 'Lynda.com',
							// subtitle: 'Software Training &amp; Tutorials',
							target: '_blank',
							url: 'http://iplogin.lynda.com'
						},
						{
							title: 'SMS',
							// subtitle: 'Ye Olde Portal',
							target: '_blank',
							url: 'http://sms.stmarksschool.org'
						},
						{
							title: 'Tech Support Documents',
							// subtitle: 'Directions for connections, on Lion Hub',
							target: '_blank',
							url: 'http://www.stmarksschool.org/academics/technology/Tech-Docs/index.aspx',
							userClass: [USER_CLASS_FACULTY]
						}/*,
						{
							title: 'Human Resource Documents',
							subtitle: 'Family & Medical Leave Act, on Lion Hub',
							target: '_blank',
							url: 'https://lionhub.stmarksschool.org/pages/human-resource-documents',
							userClass: [USER_CLASS_FACULTY]
						}*/
					]
				},
				{
					title: 'Communication &amp; Storage',
					//style: 'optional CSS goes here',
					items: [
						{
							title: 'Gmail',
							// subtitle: 'Google Apps for Education',
							target: '_blank',
							// style: 'optional CSS goes here',
							url: 'http://mail.stmarksschool.org'
						},
						{
							title: 'Google Drive',
							// subtitle: 'Google Apps for Education',
							target: '_blank',
							url: 'http://drive.google.com/a/stmarksschool.org/#my-drive'
						},
						{
							title: 'Minerva Web Access',
							// subtitle: 'Home Directories and Shared Files',
							target: '_blank',
							url: 'http://minerva.stmarksschool.org/',
							userClass: [USER_CLASS_FACULTY]
						},
						{
							title: 'Athena Web Access',
							// subtitle: 'Home Directories and Shared Files',
							target: '_blank',
							url: 'http://athena.stmarksschoo.org/',
							userClass: [USER_CLASS_STUDENT]
						}
					]
				},
				{
					title: 'Academics Office',
					items: [
						{
							title: 'Curricuplan',
							subtitle: 'Curriculum mapping',
							target: '_blank',
							url: 'http://hosting.curricuplan.com',
							userClass: [USER_CLASS_FACULTY]
						},
						{
							title: 'FAWeb',
							subtitle: 'Window Grades &amp; Comments',
							target: '_blank',
							url: 'http://faweb.stmarksschool.org',
							userClass: [USER_CLASS_FACULTY]
						},
						{
							title: 'NetClassroom',
							subtitle: 'Course Registration',
							target: '_blank',
							url: 'http://netclassroom.stmarksschool.org'
						}
					]
				},
				{
					title: 'Service Desks',
					userClass: [USER_CLASS_FACULTY],
					// style: colorStripe,
					items: [
						{
							title: ' Technology Help Desk',
							// subtitle: 'Technology Issues',
							target: '_blank',
							url: 'http://helpdesk.stmarksschool.org'
						},
						{
							title: 'School Dude',
							// subtitle: 'Facilities Requests (School ID 615666807)',
							target: '_blank',
							url: 'http://www.myschoolbuilding.com/myschoolbuilding/msbdefault_email.asp?frompage=myrequest.asp'
						},
						{
							title: 'Communications Request',
							// subtitle: 'Publications, Branded Items, Website Updates',
							target: '_blank',
							url: 'http://www.stmarksschool.org/about-st-marks/communications-department/index.aspx'
						}
					]
				}
			]
		}
	]
};

// if the Faculty Resources item is in the Courses menu, this person is faculty and should get faculty-specific resources
var facultyCourseId = '97';

var deptAcademicTechnology = [
	'Seth Battis',
	'Brian Lester'
];

function stmarks_setUserClass() {
	var i;
	
	// check user name to Academic Technology
	var userName = document.getElementsByClassName('user_long_name')[0].innerText;
	// if not an individually allowed user, don't process them!
	if (deptAcademicTechnology.indexOf(userName) !== -1) {
		userDepartments.push(USER_DEPARTMENT_ACADEMIC_TECHNOLOGY);
	}
	
	userClass = USER_CLASS_STUDENT;
	
	// check for membership in specific departments
	var coursesMenu = document.getElementById('menu_enrollments').parentNode;
	// check if the user is enrolled in more than courses
	if (coursesMenu.children.length > 1) {
		var accountsList = coursesMenu.children[coursesMenu.children.length - 1].children[1].children;
		// skip the "View all accounts" link: length - 1
		for (i = 0; i < accountsList.length - 1; i++) {
			// TODO: USER_DEPARTMENTS could be an array and this wouldn't even need to be a switch statement
			switch (accountsList[i].children[0].getAttribute('href')) {
				case USER_DEPARTMENT_HISTORY:
					userDepartments.push(USER_DEPARTMENT_HISTORY);
					break;
				case USER_DEPARTMENT_MODERN_LANGUAGE:
					userDepartments.push(USER_DEPARTMENT_MODERN_LANGUAGE);
					break;
			}
		}
	}
	
	// check for Faculty Resources course to identify USER_CLASS_FACULTY
	var coursesList = coursesMenu.children[0].children[2].children;
	// skip the "View all courses" link: length - 1
	for (i = 0; i < coursesList.length - 1; i++) {
		if (coursesList[i].getAttribute('data-id') === '97') {
			userClass = USER_CLASS_FACULTY;
			return;
		}
	}
}

function stmarks_userClassVisible(menuObject) {
	return !menuObject.userClass || menuObject.userClass.indexOf(userClass) > -1;
}

function stmarks_userDepartmentsVisible(menuObject) {
	var i;
	
	// if no departmental permissions are set, it's fine
	if (!menuObject.userDepartments) {
		return true;
	}
	
	// if one of the user's departments matches the departmental permissions, it's fine
	for (i = 0; i < userDepartments.length; i++) {
		if (menuObject.userDepartments.indexOf(userDepartments[i]) > -1) {
			return true;
		}
	}
	
	// not visible
	return false;
}

// courses that (if they exist in Courses) are replicated in the Resources menu
var coursesToHide = [
	'97', // Faculty Resources
	'497', // All School '12-'13
	'1277', // All School '13-'14
	'489', // Canvas Training
	'672', // Global Citizenship
	'1294' // Modern Language Department
];

// remove courses from the Courses menu that have been replicated in custom menus
function stmarks_hideCourses(courses) {
	var i;
	var coursesList = document.getElementById('menu_enrollments').children[2].children;
	for (i = 1; i < coursesList.length; i += 1) {
		if (courses.indexOf(coursesList[i].getAttribute('data-id')) > -1) {
			coursesList[i].parentNode.removeChild(coursesList[i]);
			i = 0; // start at the beginning again... eventually we'll hide them all and escape!
		}
	}
}

// parse the array/object structure above into the HTML that represents a dropdown menu and add it to the right of the existing menubar
function stmarks_appendMenu(m) {
	var i, j, k;

	var navigationMenu = document.getElementById("menu");
	var menu = document.createElement('li');
	menu.setAttribute('class', 'menu-item');
	var html = '<a class="menu-item-title"' + (m.url !== undefined ? ' href="' + m.url + '"' : '') + (m.target !== undefined ? ' target="' + m.target + '"' : '') + '>' + m.title + '<span class="menu-item-title-icon"/> <i class="icon-mini-arrow-down"/></a>';

	html += '<div class="menu-item-drop"><table cellspacing="0"><tr>';

	for(i = 0; i < m.columns.length; i++) {
		if (
			stmarks_userClassVisible(m.columns[i]) &&
			stmarks_userDepartmentsVisible(m.columns[i])
		) {
			html += '<td class="menu-item-drop-column"' + (m.columns[i].style !== undefined ? ' style="' + m.columns[i].style + '"': '') + '>';
			for (j = 0; j < m.columns[i].sections.length; j++) {
				if (
					stmarks_userClassVisible(m.columns[i].sections[j]) &&
					stmarks_userDepartmentsVisible(m.columns[i].sections[j])
				) {
					html += (m.columns[i].sections[j].title !== undefined ? '<span class="menu-item-heading"' + (m.columns[i].sections[j].style !== undefined ? ' style="' + m.columns[i].sections[j].style + '"' : '') + '>' + m.columns[i].sections[j].title + '</span>' : '');
					html += '<ul class="menu-item-drop-column-list"' + (m.columns[i].sections[j].style !== undefined ? ' style="' + m.columns[i].sections[j].style + '"' : '') + '>';
		
					for (k = 0; k < m.columns[i].sections[j].items.length; k++) {
						if (
							stmarks_userClassVisible(m.columns[i].sections[j].items[k]) &&
							stmarks_userDepartmentsVisible(m.columns[i].sections[j].items[k])
						) {
							html += '<li' + (m.columns[i].sections[j].items[k].style !== undefined ? ' style="' + m.columns[i].sections[j].items[k].style + '"' : '') + '><a' + (m.columns[i].sections[j].items[k].target !== undefined ? ' target="' + m.columns[i].sections[j].items[k].target + '"' : '') + (m.columns[i].sections[j].items[k].url !== undefined ? ' href="' + m.columns[i].sections[j].items[k].url + '"' : '') + '><span class="name ellipsis">' + m.columns[i].sections[j].items[k].title + '</span>' + (m.columns[i].sections[j].items[k].subtitle !== undefined ? '<span class="subtitle">' + m.columns[i].sections[j].items[k].subtitle + '</span>' : '') + '</a></li>';
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

function stmarks_resourcesMenu() {
	// add the custom menu to the menubar
	// if you wanted to add more menus, define another menu structure like resources and call appendMenu() with it as a parameter (menus would be added in the order that the appendMenu() calls occur)
	stmarks_setUserClass();
	if (userClass != USER_CLASS_NO_MENU) {
		stmarks_appendMenu(resources);
		stmarks_appendMenu(lionHub);
		
		// hide courses last, since some userClass identification is based on course enrollments!
		stmarks_hideCourses(coursesToHide);
	}
}

