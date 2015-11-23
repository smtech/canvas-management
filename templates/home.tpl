{extends file="page.tpl"}
{block name="content"}
	<div class="container">
		<div class="page-header">
			<h1><img src="{$metadata['APP_URL']}/lti/icon.png" style="height: 1em; padding: 0px 0.25em 0.1em 0px; margin: 0px; " />St. Mark&rsquo;s Tools <small>Age quod agis cum ordinatrum</small></h1>
		</div>
	</div>
	<div class="container">
		<div class="readable-width">
		<div class="panel panel-default">
			<div class="panel-heading">
				<h5>Canvas Management</h5>
			</div>
			<div class="panel-body">
				<h4>Courses</h4>
				<p>Tools to facilitate the creation and maintenance of courses. Includes: batch creation from a template, batch SIS ID updates from CSV, batch update from a template, creation of course-level grading periods, normalization of section SIS IDs and names from CSV, and force-publication.</p>
				
				<h4>Enrollment</h4>
				<p>Tools to facilitate user enrollment. Includes: batch enrollment by search.</p>
				<p>Coming soon: multiple enrollment disambiguation, role re-assignment.</p>
			</div>
		</div>
		
		<div class="panel panel-default">
			<div class="panel-body">
				<h4>Custom Preferences <small>CanvasHack</small></h4>
				<p>Tools to update custom preferences for users and courses outside of the Canvas dataset. Includes: user super-role assignments, application of enrollment rules per user super-role and group, application of custom course colors by block color per user</p>
				<p>Coming soon: improved super-role management, group management, enrollment rules management.</p>
			</div>
		</div>
		
		<div class="panel panel-default">
			<div class="panel-body">
				<h4>One Offs</h4>
				<p>Tools that were written for a single use, but <em>may</em> be useful again in the future, if things don't go our way. Includes: re-formatting section SIS IDs to match Blackbaud exports, download <code>users.csv</code> per account (useful for re-uploading to reset passwords), inbox archiving (should be rolled into a CanvasHack).</p>
			</div>
		</div></div>
	</div>
{/block}