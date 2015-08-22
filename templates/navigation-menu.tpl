{block name="navigation-menu"}
<ul class="nav navbar-nav">
	<li {if $navbarActive == 'canvas-management'}class="active"{/if}}><a href="{$metadata['APP_URL']}/app.php">Home</a></li>
	<li class="dropdown {if $navbarActive == 'custom-preferences'}active{/if}">
		<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false" id="custom-prefs-dropdown">Custom Preferences <span class="caret"></span></a>
		<ul class="dropdown-menu">
			<li><a href="{$metadata['APP_URL']}/custom-preferences/assign-user-roles.php">Assign User Roles</a></li>
			<li><a href="{$metadata['APP_URL']}/custom-preferences/color-blocks.php">Color Blocks</a></li>
		</ul>
	</li>
	<li class="dropdown {if $navbarActive == 'enrollment-management'}active{/if}">
		<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
			Enrollment Management <span class="caret"></span>
		</a>
		<ul class="dropdown-menu">
			<li><a href="{$metadata['APP_URL']}/enrollment-management/create-courses.php">Create Courses</a></li>
			<li><a href="{$metadata['APP_URL']}/enrollment-management/enroll-users.php">Enroll Users</a></li>
		</ul>
	</li>
</ul>
{/block}