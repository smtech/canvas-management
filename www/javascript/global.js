/*jslint browser: true, devel: true, eqeq: true, plusplus: true, sloppy: true, vars: true, white: true */

var STMARKS = {
	waitForDOMDelay: 100 // milliseconds
};

/* wait for a particular DOM element to be ready (i.e. created) and then call
   a function to take the next steps. Used thus:
   
	function test(DOMElement) {
		alert(DOMElement.innerHTML);
	}
	stmarks_waitForDOMById(/.*\/courses\/\d+\/settings/, 'right-side', test);
	
	In this case, the code is waiting for div id="right-side" to be ready, at
	which point a reference to that node will be sent to the test(). Obviously,
	test() could be an inline function instead...
	
	The result of stmarks_waiForDOMById can be tested to see if the page URL
	itself matches. */
function stmarks_waitForDOMById(UrlPattern, nodeId, callback) {
	if (UrlPattern.test(document.location.href)) {
		var DOMElement = document.getElementById(nodeId);
		if (DOMElement !== undefined) {
			callback(DOMElement);
		} else {
			window.setTimeout(
				function() {
					stmarks_waitForDOMById(UrlPattern, nodeId, callback);
				},
				STMARKS.waitForDOMDelay
			);
		}
		return true;
	}
	return false;
}

function stmarks_waitForDOMByClassName(UrlPattern, className, callback) {
	if (UrlPattern.test(document.location.href)) {
		var DOMElements = document.getElementsByClassName(className);
		if (DOMElements !== undefined) {
			callback(DOMElements);
		} else {
			window.setTimeout(
				function() {
					stmarks_waitForDOMByClassName(UrlPattern, className, callback);
				}, 
				STMARKS.waitForDOMDelay
			);
		}
		return true;
	}
	return false;
}

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
	
	// Works, so long as Canvas includes JQuery ahead of us...
	// http://stackoverflow.com/a/950146

	$.getScript(
		pathToScripts + 'resources-menu.js',
		function() {
			stmarks_resourcesMenu();
		}
	);	
	
	$.getScript(
		pathToScripts + 'hide-future-courses.js',
		function() {
			stmarks_hideFutureCourses();
		}
	);
		
	$.getScript(
		pathToScripts + 'hide-page-lists-if-pages-hidden.js',
		function() {
			stmarks_hidePageListsIfPagesHidden();
		}
	);

	$.getScript(
		pathToScripts + 'discussion-permalinks.js',
		function() {
			stmarks_discussionPermalinks();
		}
	);
	
	$.getScript(
		pathToScripts + 'grading-analytics.js',
		function() {
			stmarks_gradingAnalytics();
		}
	);
	
	$.getScript(
		pathToScripts + 'rce-notify-unsaved-changes.js',
		function() {
			stmarks_rceNotifyUnsavedChanges();
		}
	);
}

stmarks_globalJavascript();