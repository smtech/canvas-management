# Management Scripts

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

## Known Issues

The issues tracking hasn't yet been pulled over to this repo and still lives in [our original Canvas repo](https://github.com/smtech/canvas/issues?milestone=8)