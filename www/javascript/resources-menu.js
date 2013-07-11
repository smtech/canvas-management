// define your menu here
var resources = {
	// menu title
	// can also have a url property, so clicking the menu title sends you somewhere
	// can also have a target property, so clicking the menu title opens in a specific frame/window/tab
	title: 'Resources',
	// an array of columns to display (don't go overboard here!)
	columns: [
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
						{title: 'Resources for Students', url: '/courses/491'}
					]
				},
				{
					title: 'Research',
					items: [
						{title: 'Library', url: 'http://library.stmarksschool.org', target: '_blank'},
						{title: 'Writing Manual'}
					]
				}
			]
		}
	]
};

// parse the array/object structure above into the HTML that represents a dropdown menu and add it to the right of the existing menubar
function appendMenu(m) {
	var i, j, k;

	var navigationMenu = document.getElementById("menu");
	var menu = document.createElement('li');
	menu.setAttribute('class', 'menu-item');
	var html = '<a class="menu-item-title"' + (typeof m.url !== 'undefined' ? ' href="' + m.url + '"' : '') + (typeof m.target !== 'undefined' ? ' target="' + m.target + '"' : '') + '>' + m.title + '<span class="menu-item-title-icon"/> <i class="icon-mini-arrow-down"/></a>';

	html += '<div class="menu-item-drop"><table cellspacing="0"><tr>';

	for(i = 0; i < m.columns.length; i++) {
		html += '<td class="menu-item-drop-column">';
		for (j = 0; j < m.columns[i].sections.length; j++) {
			html += (typeof m.columns[i].sections[j].title !== 'undefined' ? '<span class="menu-item-heading">' + m.columns[i].sections[j].title + '</span>' : '');
			html += '<ul class="menu-item-drop-column-list">';

			for (k = 0; k < m.columns[i].sections[j].items.length; k++) {
				html += '<li><a' + (typeof m.columns[i].sections[j].items[k].target !== 'undefined' ? ' target="' + m.columns[i].sections[j].items[k].target + '"' : '') + (typeof m.columns[i].sections[j].items[k].url !== 'undefined' ? ' href="' + m.columns[i].sections[j].items[k].url + '"' : '') + '><span class="name ellipsis">' + m.columns[i].sections[j].items[k].title + '</span>' + (typeof m.columns[i].sections[j].items[k].subtitle !== 'undefined' ? '<span class="subtitle">' + m.columns[i].sections[j].items[k].subtitle + '</span>' : '') + '</a></li>';
			}
			html += '</ul>';
		}
		html += '</td>';
	}
	html += '</tr></table></div>';
	menu.innerHTML = html;
	navigationMenu.appendChild(menu);
}

// add the resources menu to the menubar
// if you wanted to add more menus, define another menu structure like resources and call appendMenu() with it as a parameter (menus would be added in the order that the appendMenu() calls occur)
appendMenu(resources);