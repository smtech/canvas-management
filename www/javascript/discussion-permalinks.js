/* Canvas loads discussion entries using AJAX, so we have to wait a moment
   for the entries to actually be, y'know... _there_. */
// TODO: add tests for content availability, as in https://github.com/smtech/canvas/commit/38955ef47b33478d0549712689844734dc66001d
var delay = 1000; // 1 second = 1000 milliseconds

function stmarks_addPermalinks() {
	// TODO: check URL before trying to do anything, as in https://github.com/smtech/canvas/blob/master/www/javascript/faculty-journal.js
	/* make sure that we are actually messing with a discussion page... */
	if (document.getElementById('discussion_container')) {
		/* run through all of the <li> elements looking for entry IDs */
		var entries = document.getElementById('discussion_subentries').childNodes[0].getElementsByTagName('li');
		for (var i = 0; i < entries.length; ++i) {
			var id = entries[i].getAttribute('id');
			/* if we find an entry id, slap the permalink after the post date (why
			   the post date? because it's easy to find and at the top of the entry) */
			if (id !== null && id.substring(0, 6) === 'entry-') {
				var pubdate = entries[i].getElementsByTagName('time')[0];
				permalink = pubdate.innerHTML + ' &bull; <a target="_top" href="#' + id + '">permalink</a>';
				pubdate.innerHTML = permalink;
			}
		}
	}
}

function stmarks_discussionPermalinks() {
	window.setTimeout(addPermalinks, delay);
} 
