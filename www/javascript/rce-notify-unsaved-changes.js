// from https://help.instructure.com/entries/20451427-Save-reminder-before-leaving-an-edit-screen?page=1#post_21624684

// will be rendered obsolete https://help.instructure.com/entries/31780970-04-12-14-Canvas-Production-Release-Notes-Featuring-Profile-Picture-Uploader

/* Be Notified when leaving the MCE Editor */

function stmarks_rceNotifyUnsavedChanges() {
	$(document).ready(function(){
		function onElementRendered(selector, cb, _attempts) {
			var el = $(selector + ":visible");
			_attempts = ++_attempts || 1;
			if (el.length) return cb(el);
			if (_attempts == 30000) return;
			setTimeout(
				function() {
					onElementRendered(selector, cb, _attempts);
					},
				250
			);
		}
	 
		//onElementRendered('a[class*="_views_link"]', function(el) {
		onElementRendered(
			'.mceButton.mceButtonEnabled.mce_bold',
			function(el) {
				function setConfirmUnload(on) {
					window.onbeforeunload = (on) ? unloadMessage : null;
				}
			function unloadMessage() {
				return ' If you navigate away from this page without' +
					' first saving your data, the changes will be' +
					' lost.';
			}
			$('*[type="submit"]').bind(
	        	"click", function() {
	        		setConfirmUnload(false);
				}
			);
			setConfirmUnload(true);
		});
	});

}