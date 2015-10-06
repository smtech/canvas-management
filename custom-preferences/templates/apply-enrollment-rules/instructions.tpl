{extends file="subpage.tpl"}

{block name="subcontent"}

<div class="container">
	<div class="readable-width">
		<p>Apply pre-defined enrollment rules (based on a user's custom prefs user role or group membership) to enroll users in the courses in which these policies say they should be enrolled, and to unenroll users whose policies do not specify that they should be enrolled.</p>
		<p>A quick, static summary of these rules:</p>
		<table class="table table-bordered table-hover table-striped">
			<tbody>
				<tr>
					<td>Role: Faculty</td>
					<td>Enroll in Faculty Resources, Student Resources, Canvas Training, Library</td>
				</tr>
				<tr>
					<td>Role: Staff</td>
					<td>Enroll in Faculty Resources, Student Resources, Canvas Training, Library</td>
				</tr>
				<tr>
					<td>Group: Current Students</td>
					<td>Enroll in Student Resources, Library</td>
				</tr>
				<tr>
					<td>Group: Class of 20XX</td>
					<td>Enroll in Form XX class</td>
				</tr>
				<tr>
					<td>Group: Current VI Form Class</td>
					<td>Enroll in Yearbook</td>
				</tr>
			</tbody>
		</table>
	</div>
</div>
					

{include file="apply-enrollment-rules/form.tpl"}

{/block}