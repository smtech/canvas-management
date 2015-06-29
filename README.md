# Management Scripts

Individual scripts to do specific things, but that also shouldn't [just](https://github.com/smtech/canvas/commit/88b77a269063a342808443256f2f173ddf5881b5) [be](https://github.com/smtech/canvas/commit/a22552daa520f73cfb75b3f0ae93d1b8a08438af) [committed](https://github.com/smtech/canvas/commit/b51f50b579a7dcb54f6934ae9dd0a3523415ad5a) to the master fork without testing.

#####[advisors-as-observers](https://github.com/smtech/smcanvas-scripts/blob/master/advisors-as-observers.php)
Generate (and/or update) the advisor users and post their logins as an unpublished wiki page in the advisor course (the script knows who the advisor and advisees are because there are a bunch of advisory courses in a particular sub-account and term).

#####[assignments-due-on-a-day](http://github.com/smtech/canvas/tree/dev-scripts/www/api/scripts/assignments-due-on-a-day.php)
This is a quick script for field trip planning: list all of the assignments due, optionally filtering by course code (color block) or due date.

#####[clean-ap-bio](http://github.com/smtech/canvas/tree/dev-scripts/www/api/scripts/clean-ap-bio.php)
This is a quick script to move a list of files matching a particular naming pattern into a particular folder in a course. This wa the result of Kim Berndt's experience with the AP Bio coursepack that just dumped hundreds of individual files into her main Course Files folder, with the result that she couldn't actually link to any files (because the file list was too long to load).

#####[courses-in-term-with-id](http://github.com/smtech/canvas/tree/dev-scripts/www/api/scripts/courses-in-term-with-id.php)
This generates a tab-separated-values list of all of the courses in a particular term (GET parameter enrollment_term_id) with both their Canvas ID and their SIS ID.

#####[courses-with-a-single-assignment-group](http://github.com/smtech/canvas/tree/dev-scripts/www/api/scripts/courses-with-a-single-assignment-group.php)
Lists all courses (in the current term(s) -- hard-coded!) with only a single assignment group. This is an early warning flag that folks are not prepared for the second window and will not be able to compute their second window grades.

#####[csv-import-and-clone](https://github.com/smtech/smcanvas-scripts/blob/master/csv-import-and-clone.php) This takes a standard [courses.csv](https://canvas.instructure.com/doc/api/file.sis_csv.html)
Import file with one additional column: template_id, which takes the Canvas ID of the source course to be duplicated into the new course. This duplicates both the contents and all (API-accessible) settings of the original course in the new course.

#####[generate-course-and-section-sis_id](http://github.com/smtech/canvas/tree/dev-scripts/www/api/scripts/generate-course-and-section-sis_id.php)
generates unique (MD5 hash-based) SIS IDs for all courses and sections in the Canvas instance that do not already have SIS IDs. Quite useful for doing SIS export reports from Canvas that include _everything_.
#####[list-teachers-of-courses](http://github.com/smtech/canvas/tree/dev-scripts/www/api/scripts/list-teachers-of-courses.php)
Lists all of teachers of courses in a particular enrollment_term.

#####[list-users-enrolled-in-term](http://github.com/smtech/canvas/tree/dev-scripts/www/api/scripts/list-users-enrolled-in-term.php)
Generates a TSV list of all users enrolled in courses/sections in a particular term (GET parameter term_id).

#####[list-users-with-non-blackbaud-sis_id](http://github.com/smtech/canvas/tree/dev-scripts/www/api/scripts/list-users-with-non-blackbaud-sis_id.php)
Generates a TSV list of all users in the instance whose SIS IDs do not match the general observed pattern of Blackbaud Import IDs (and are, therefore, most likely hand-generated and/or erroneous -- or both!).

#####[list-users-without-sis_id](http://github.com/smtech/canvas/blob/dev-scripts/www/api/scripts/list-users-without-sis_id.php)
Generates a TSV list of all users who do not have SIS IDs (and therefore don't show up in Canvas SIS export reports).	

#####[publish-current-advisory-courses](https://github.com/smtech/smcanvas-scripts/blob/master/publish-current-advisory-courses.php)
Publishes all of the current advisory courses. This only matters if a) you have advisors who might not also currently teach their advisees and b) they will need access to their advisees' faculty journal entries. If the advisory course isn't published in this situation, the advisors will, bizarrely, only be able to see the faculty journals for the advisees that they currently teach, and get permissions errors on the ones that they don't.

#####[students-as-teachers-audit](http://github.com/smtech/canvas/blob/dev-scripts/www/api/scripts/students-as-teachers-audit.php)
Generates a TSV list of all users who are not enrolled in our Faculty Resources course (and therefore identifiable as a faculty member) but who _are_ enrolled as teachers elsewhere (giving them potentially inappropriate access to the faculty journal for other students).

#####[summer-course-merge](https://github.com/smtech/smcanvas-scripts/blob/master/summer-course-merge.php)
We provide our faculty with empty courses representing (a version) of their schedule in the upcoming year at the _start_ of the summer, before the final schedules have been generated. This script is a tool to reconcile those existing courses with the final course and section SIS IDs generated at the end of the summer, in preparation for syncing enrollments out of Blackbaud into Canvas. The script takes as input a merge.csv file with the following columns:
  - summer_sis_course_id - the provisional, original SIS ID of the course
  - modified_sis_course_id - the final, real SIS ID of the course
  - action - Left blank or one of add, rename, delete or modify
    - add - create a new course from this information
    - delete - delete the summer course (it doesn't exist in the real schedule)
    - rename - rename the summer course to reflect updated information from the system
    - modify - also the default case (what happens if you leave it blank), updating all SIS IDs to match the Blackbaud SIS IDs
  - sis_course_id - the "real" SIS ID for this course
  - sis_section_id - the "real" SIS section ID for the quasi-hidden single section of the course
  - long_name, short_name, sis_account_id - as in [courses.csv](https://canvas.instructure.com/doc/api/file.sis_csv.html)
  - sis_term_id - SIS ID for the term in which the course is taking place
  - sis_teacher_id - SIS ID for the teacher actually teaching the course

#####[transfer-outcomes](https://github.com/smtech/smcanvas-scripts/blob/master/transfer-outcomes.php)
Doesn't really do anything. It was started at an idle moment when I really wanted to be able to transfer outcomes from a course to a parent account (or from an account to a parent account). I got distracted and [Canvas seems to be working on the same idea](https://community.canvaslms.com/docs/DOC-2083). We'll see who gets there first.

#####[turn-off-advisor-notifications](https://github.com/smtech/smcanvas-scripts/blob/master/turn-off-advisor-notifications.php)
The advisor accounts default to having some notifications on, most notably course enrollments. This turns them all off, creating an opt-in for notifications. _Nota bene: [Observer observations are not working terribly well in Canvas right now.](https://community.canvaslms.com/ideas/1380#comment-7327)_

## Usage

As an interesting sidenote, in response to a query in the Canvas Community, I slapped together a screencast taking a repository (not this one, sadly) from Github to working in Canvas.

[![Github → Canvas](http://img.youtube.com/vi/eebwBpiJ6S8/0.jpg)](http://www.youtube.com/watch?v=eebwBpiJ6S8)
