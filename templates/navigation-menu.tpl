{block name="navigation-menu"}
<div class="container-fluid">
<ul class="nav navbar-nav">
	<li {if $navbarActive == 'canvas-management'}class="active"{/if}}><a href="{$metadata['APP_URL']}/app.php">Home</a></li>
	<li class="dropdown {if $navbarActive == 'courses'}active{/if}">
		<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
			Courses <span class="caret"></span>
		</a>
		<ul class="dropdown-menu">
			<li><a href="{$metadata['APP_URL']}/courses/create-courses.php">Create Courses</a></li>
			<li><a href="{$metadata['APP_URL']}/courses/update-courses.php">Update Courses</a></li>
			<li><a href="{$metadata['APP_URL']}/courses/template-courses.php">Template Courses</a></li>
			<li><a href="{$metadata['APP_URL']}/courses/course-level-grading-periods.php">Course-level Grading Periods</a></li>
			<li><a href="{$metadata['APP_URL']}/courses/normalize-sections.php">Normalize Sections</a></li>
			<li><a href="{$metadata['APP_URL']}/courses/publish-courses.php">Publish Courses</a></li>
		</ul>
	</li>

	<li class="dropdown {if $navbarActive == 'enrollment'}active{/if}">
		<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
			Enrollment <span class="caret"></span>
		</a>
		<ul class="dropdown-menu">
			<li><a href="{$metadata['APP_URL']}/enrollment/enroll-users.php">Enroll Users</a></li>
			<li><a href="{$metadata['APP_URL']}/enrollment/download-enrollments.php">Download Enrollments</a></li>
		</ul>
	</li>
</ul>
<ul class="nav navbar-nav">
	<li class="dropdown {if $navbarActive == 'custom-preferences'}active{/if}">
		<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false" id="custom-prefs-dropdown">Custom Preferences <span class="caret"></span></a>
		<ul class="dropdown-menu">
			<li><a href="{$metadata['APP_URL']}/custom-preferences/assign-user-roles.php">Assign User Roles</a></li>
			<li><a href="{$metadata['APP_URL']}/custom-preferences/apply-enrollment-rules.php">Apply Enrollment Rules</a></li>
			<li><a href="{$metadata['APP_URL']}/custom-preferences/color-blocks.php">Color Blocks</a></li>
		</ul>
	</li>

	<li style="min-width: 1em; max-width: 100%; width: auto;"><a href="#" class="disabled"></a></li>
</ul>

<ul class="nav navbar-nav pull-right">
	<li class="dropdown {if $navbarActive == 'one-offs'}active{/if}">
		<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
			One Offs <span class="caret"></span>
		</a>
		<ul class="dropdown-menu pull-right">
			<li><a href="{$metadata['APP_URL']}/one-offs/fix-section-sis-ids.php">Fix Section SIS IDs</a></li>
			<li><a href="{$metadata['APP_URL']}/one-offs/download-users-csv.php">Download Users.csv</a></li>
			<li><a href="{$metadata['APP_URL']}/one-offs/archive-inbox.php">Archive Inbox</a></li>
		</ul>
	</li>
</ul>
</div>
{/block}