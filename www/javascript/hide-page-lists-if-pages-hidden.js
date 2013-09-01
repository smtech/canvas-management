function stmarks_hidePageListsIfPagesHidden() {
	var i;
	
	// get the list of section tabs in the course navigation
	var sectionTabs = document.getElementById('section-tabs').children;
	
	// assume that pages are hidden
	var pagesHidden = true;
	
	// if we find pages in the sections tabs, they're not hidden -- nothing for us to do here!
	for (i = 0; i < sectionTabs.length; i++) {
		if (sectionTabs[i].children[0].innerHTML === 'Pages') {
			pagesHidden = false;
			return;
		}
	}
	
	// pages really are hidden, so remove from the wiki sidebar from the interface (if present)
	// N.B. this _should_ leave the page editing buttons, if the user has permission to edit the page (but not to see the page lists)
	var pageLists = document.getElementById('wiki_show_view_secondary').children[0];
	pageLists.parentNode.removeChild(pageLists);
}