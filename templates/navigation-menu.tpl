{block name="navigation-menu"}
<ul class="nav navbar-nav">
	<li {if $navbarActive == 'canvas-management'}class="active"{/if}}><a href="{$metadata['APP_URL']}/app.php">Home</a></li>
	<li class="dropdown {if $navbarActive == 'custom-preferences'}active{/if}">
		<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false" id="custom-prefs-dropdown">Custom Preferences <span class="caret"></span></a>
		<ul class="dropdown-menu">
			<li><a href="{$metadata['APP_URL']}/custom-preferences/assign-user-roles.php">Assign User Roles</a></li>
			<li><a href="{$metadata['APP_URL']}/custom-preferences/apply-enrollment-rules.php">Apply Enrollment Rules</a></li>
			<li><a href="{$metadata['APP_URL']}/custom-preferences/color-blocks.php">Color Blocks</a></li>
		</ul>
	</li>
	<li class="dropdown {if $navbarActive == 'enrollment-management'}active{/if}">
		<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
			Enrollment Management <span class="caret"></span>
		</a>
		<ul class="dropdown-menu">
			<li><a href="{$metadata['APP_URL']}/enrollment-management/create-courses.php">Create Courses</a></li>
			<li><a href="{$metadata['APP_URL']}/enrollment-management/update-courses.php">Update Courses</a></li>
			<li><a href="{$metadata['APP_URL']}/enrollment-management/template-courses.php">Template Courses</a></li>
			<li><a href="{$metadata['APP_URL']}/enrollment-management/normalize-sections.php">Normalize Sections</a></li>
			<li><a href="{$metadata['APP_URL']}/enrollment-management/enroll-users.php">Enroll Users</a></li>
		</ul>
	</li>
	<li class="dropdown {if $navbarActive == 'one-offs'}active{/if}">
		<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
			One Offs <span class="caret"></span>
		</a>
		<ul class="dropdown-menu">
			<li><a href="{$metadata['APP_URL']}/one-offs/course-level-grading-periods.php">Course-level Grading Periods</a></li>
			<li><a href="{$metadata['APP_URL']}/one-offs/fix-section-sis-ids.php">Fix Section SIS IDs</a></li>
			<li><a href="{$metadata['APP_URL']}/one-offs/download-users-csv.php">Download Users.csv</a></li>
			<li><a href="{$metadata['APP_URL']}/one-offs/publish-courses.php">Publish Courses</a></li>
			<li><a href="{$metadata['APP_URL']}/one-offs/archive-inbox.php">Archive Inbox</a></li>
		</ul>
	</li>
</ul>
{/block}