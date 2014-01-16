# St. Mark's Canvas Customizations

A collection of customizations to the our [Canvas instance](http://stmarksschool.instructure.org), many of which are running off of our [development LAMP server](http://area51.stmarksschool.org)

### master

Ready for prime time, live and in use!

### dev-*

Features we're working on, forked from the most recent master/develop branch as appropriate. One feature per branch, no foolin'!



## Merged Forks

Forks that are merged into the master tree. The dev-* version of any fork is still the most current code for that fork, but the current live version is maintained in the master tree.

### [dev-blackboard-import](http://github.com/smtech/canvas/tree/dev-blackboard-import/www/api/blackboard-import)

Import Blackboard ExportFiles and ArchiveFiles as Canvas courses. The current model is that a single API user (the Blackboard Import API Process) creates all of the course components via the API. The Blackboard export Zip archive and an import receipt XML file are also uploaded to the created course's files. The tool can also import the Blackboard export into an existing course, if the API user has full access to that course. I've configured our instance so that there is a Blackboard Import sub-account, over which the API process is an admin, and courses are move in and out of the sub-account by hand (it's an easy way to track where things are in the flow).

[Known Issues](http://github.com/smtech/canvas/issues?milestone=4)

### [dev-branding](http://github.com/smtech/canvas/tree/dev-branding/www/branding)

Apply St. Mark's branding to our Canvas instance.

[Known Issues](http://github.com/smtech/canvas/issues?milestone=10)

### [dev-calendar-ics](http://github.com/smtech/canvas/tree/dev-calendar-ics/www/api/calendar-ics)

A pair (trio?) of tools for working with Canvas and ICS feeds. There is an [export tool](http://github.com/smtech/canvas/tree/dev-calendar-ics/www/api/calendar-ics/export.php) that exposes the pre-existing ICS feed for course calendars and there is an [import tool](http://github.com/smtech/canvas/tree/dev-calendar-ics/www/api/calendar-ics/import.php) that pairs an ICS feed with (theoretically) a course, group or user in Canvas and imports all of the ICS events into that calendar, deleting any residual events created by prior imports of that pairing. The quasi-third tool, a [sync tool](http://github.com/smtech/canvas/tree/dev-calendar-ics/www/api/calendar-ics/sync.php), is really just a wrapper for using crontab to trigger regular re-imports of an ICS feed pairing.

Some care has been taken to protect privacy by not caching the actual calendar events in our MySQL database cache of ICS/Canvas pairings, but, of course, potentially private information is passing through third party hands, etc., etc.

This would benefit from an OAuth setup, so that individual users could set up their own pairings. However, at the moment, it requires administrative intervention and relies on a single API user, Calendar API Process, to handle all imports. The API user is an admin on our main account.

[Known Issues](http://github.com/smtech/canvas/issues?milestone=6)

### [dev-support](http://github.com/smtech/canvas/tree/dev-support/www/include) (Formerly [dev-canvas-api](http://github.com/smtech/canvas/tree/dev-canvas-api))

This branch contains code for working with the Canvas API, generating HTML pages (with supporting JavaScript, CSS and images). Basically, all the code that everything else depends on.

[Known Issues](https://github.com/smtech/canvas/issues?milestone=12)

### [dev-faculty-journal](http://github.com/smtech/canvas/tree/dev-faculty-journal/www/javascript/faculty-journal.js)

Allow teachers to open a scrollable faculty journal page from their People listing for a course. Inserts a menu and next/prev buttons into the resulting faculty journal page to let teachers scroll through each student in their course, from within the faculty journal.

[Known Issues](http://github.com/smtech/canvas/issues?milestone=11)

### [dev-javascript](http://github.com/smtech/canvas/tree/dev-javascript/www/javascript)

Miscellaneous one-off JavaScript add-ons (see [below](#dev-javascript-1) for unmerged scripts):

  - [hide-future-courses](http://github.com/smtech/canvas/tree/dev-javascript/www/javascript/hide-future-courses.js) removes unpublished courses that occurred in the past from "future" enrollments (a logical error, per [this ticket](https://help.instructure.com/requests/173156)).

  - [hide-page-lists-if-pages-hidden](http://github.com/smtech/canvas/tree/dev-javascript/www/javascript/hide-page-lists-if-pages-hidden.js) removes the lists of recently modified and all pages from the right sidebar if the Pages link is not available in the left sidebar (so user's without permission to browse Pages cannot browse pages, per [this feature request](https://help.instructure.com/entries/21511835-Hide-All-Pages-List-when-Pages-is-Removed-from-Navigation))

[Known Issues](http://github.com/smtech/canvas/issues?milestone=3)

### [dev-resources-menu](http://github.com/smtech/canvas/tree/dev-resources-menu/www/javascript/resources-menu.js)

A global JavaScript add-on to insert an additional menu into the main navigation toolbar across the top of the screen. The contents of the menu can be customized in the javascript file.

[Known Issues](http://github.com/smtech/canvas/issues?milestone=2)



## Development Forks

Forks that are still under development, that aren't yet ready for prime time.

### [dev-archive-discussions](http://github.com/smtech/canvas/tree/dev-archive-discussions/www/www/api/archive-discussions)

A tool to generate a JSON export of a course or group's discussions. Particularly useful for account-level groups that lack any meaningful copy/archive capabilities out of the box. Currently, this tool is works fine... so long as you have fewer than 50 discussions, each with fewer than 50 topic entries (because I haven't bothered to deal with the pagination in the Canvas API's responses -- I didn't need to for the one thing that I needed to back up).

[Known Issues](http://github.com/smtech/canvas/issues?milestone=5)

### [dev-embedded-documentation](http://github.com/smtech/canvas/tree/dev-grading-scheme/www/javascript/embedded-documentation.js)

Wouldn't it be nice if there were reminders _in Canvas_ with links to appropriate documentation for certain features? Let's add them in where they belong.

### [dev-javascript](http://github.com/smtech/canvas/tree/dev-javascript/www/javascript)

Miscellaneous one-off JavaScript add-ons (see [above](#dev-javascript) for merged scripts):

  - [discussion-permalinks](http://github.com/smtech/canvas/tree/dev-javascript/www/javascript/discussion-permalinks.js) reveals the permalinks to individual replies in Canvas discussions and announcements.

[Known Issues](http://github.com/smtech/canvas/issues?milestone=3)

### [dev-grading-analytics](https://github.com/smtech/canvas/tree/dev-grading-analytics/www/api/grading-analytics)

Generate some reporting on faculty grading practices.

[Known Issues](http://github.com/smtech/canvas/issues?milestone=13)

### [dev-grading-scheme](http://github.com/smtech/canvas/tree/dev-grading-scheme/www/api/grading-scheme)

For whatever reason, Canvas defaults to its own grading scheme, rather than a custom grading scheme. Once courses have been created, this will run through the directory and update the course grading schemes to a specific custom grading scheme. This also includes a crontab-scheduled job to run through recent assignments and update them to a custom grading scheme if they are set to a Letter Grade and are using the default grading scheme.

[Known Issues](http://github.com/smtech/canvas/issues?milestone=9)

### [dev-scripts](http://github.com/smtech/canvas/tree/dev-scripts/www/api/scripts)

Individual scripts to do specific things, but that also shouldn't [just](https://github.com/smtech/canvas/commit/88b77a269063a342808443256f2f173ddf5881b5) [be](https://github.com/smtech/canvas/commit/a22552daa520f73cfb75b3f0ae93d1b8a08438af) [committed](https://github.com/smtech/canvas/commit/b51f50b579a7dcb54f6934ae9dd0a3523415ad5a) to the master fork without testing.

  - [assignments-due-on-a-day](http://github.com/smtech/canvas/tree/dev-scripts/www/api/scripts/assignments-due-on-a-day.php) is a quick script for field trip planning: list all of the assignments due, optionally filtering by course code (color block) or due date.


  - [clean-ap-bio](http://github.com/smtech/canvas/tree/dev-scripts/www/api/scripts/clean-ap-bio.php) is a quick script to move a list of files matching a particular naming pattern into a particular folder in a course. This wa the result of Kim Berndt's experience with the AP Bio coursepack that just dumped hundreds of individual files into her main Course Files folder, with the result that she couldn't actually link to any files (because the file list was too long to load).

  - [courses-in-term-with-id](http://github.com/smtech/canvas/tree/dev-scripts/www/api/scripts/courses-in-term-with-id.php) generates a tab-separated-values list of all of the courses in a particular term (GET parameter enrollment_term_id) with both their Canvas ID and their SIS ID.
  
  - [courses-with-a-single-assignment-group](http://github.com/smtech/canvas/tree/dev-scripts/www/api/scripts/courses-with-a-single-assignment-group.php) lists all courses (in the current term(s) -- hard-coded!) with only a single assignment group. This is an early warning flag that folks are not prepared for the second window and will not be able to compute their second window grades.

  - [generate-course-and-section-sis_id](http://github.com/smtech/canvas/tree/dev-scripts/www/api/scripts/generate-course-and-section-sis_id.php) generates unique (MD5 hash-based) SIS IDs for all courses and sections in the Canvas instance that do not already have SIS IDs. Quite useful for doing SIS export reports from Canvas that include _everything_.

  - [list-teachers-of-courses](http://github.com/smtech/canvas/tree/dev-scripts/www/api/scripts/list-teachers-of-courses.php) lists all of teachers of courses in a particular enrollment_term.

  - [list-users-enrolled-in-term](http://github.com/smtech/canvas/tree/dev-scripts/www/api/scripts/list-users-enrolled-in-term.php) generates a TSV list of all users enrolled in courses/sections in a particular term (GET parameter term_id).

  - [list-users-with-non-blackbaud-sis_id](http://github.com/smtech/canvas/tree/dev-scripts/www/api/scripts/list-users-with-non-blackbaud-sis_id.php) generates a TSV list of all users in the instance whose SIS IDs do not match the general observed pattern of Blackbaud Import IDs (and are, therefore, most likely hand-generated and/or erroneous -- or both!).

  - [list-users-without-sis_id](http://github.com/smtech/canvas/blob/dev-scripts/www/api/scripts/list-users-without-sis_id.php) generates a TSV list of all users who do not have SIS IDs (and therefore don't show up in Canvas SIS export reports).	

  - [students-as-teachers-audit](http://github.com/smtech/canvas/blob/dev-scripts/www/api/scripts/students-as-teachers-audit.php) generates a TSV list of all users who are not enrolled in our Faculty Resources course (and therefore identifiable as a faculty member) but who _are_ enrolled as teachers elsewhere (giving them potentially inappropriate access to the faculty journal for other students).

[Known Issues](http://github.com/smtech/canvas/issues?milestone=8)