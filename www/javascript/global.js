/*jslint browser: true, devel: true, eqeq: true, plusplus: true, sloppy: true, vars: true, white: true */

function stmarks_globalJavascript() {
	// modeled on http://stackoverflow.com/a/984656, which I found to be unreliable
	var i;
	var scripts = document.getElementsByTagName('script');
	var pathToScripts;
	for (i = 0; i < scripts.length; i++) {
		if (scripts[i].src.indexOf('stmarksschool.org') != -1) {
			pathToScripts = scripts[i].src.substr(0, scripts[i].src.indexOf('global.js'));
			break;
		}
	}
	
	// Works, so long as Canvas includs JQuery ahead of us...
	// http://stackoverflow.com/a/950146
	$.getScript(
		pathToScripts + 'hide-future-courses.js',
		function() {
			stmarks_hideFutureCourses();
		}
	);
	
	$.getScript(
		pathToScripts + 'resources-menu.js',
		function() {
			stmarks_resourcesMenu();
		}
	);	
}

stmarks_globalJavascript();