{extends file="subpage.tpl"}

{block name="subcontent"}

<div class="container">
	<p>Upload a CSV file of courses to be updated in the format described below. Columns left blank will not be affected by the batch update.</p>
	<table class="table table-bordered table-hover">
		<thead>
			<tr>
				<th>Field Name</th>
				<th>Data Type</th>
				<th>Description</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td>old_course_id</td>
				<td>text</td>
				<td><strong>Required field.</strong> The unique SIS ID of the course to be updated.</td>
			</tr>
			<tr>
				<td>course_id</td>
				<td>text</td>
				<td>A new unique SIS ID (to replace <code>old_course_id</code>) for the course.</td>
			</tr>
			<tr>
				<td>short_name</td>
				<td>text</td>
				<td>A new short name for the course. Shown at the top of course navigation.</td>
			</tr>
			<tr>
				<td>long_name</td>
				<td>text</td>
				<td>A long name for the course. Shown in menus and listings.</td>
			</tr>
			<tr>
				<td>account_id</td>
				<td>text</td>
				<td>The SIS ID of the new account that will contain the course.</td>
			</tr>
			<tr>
				<td>term_id</td>
				<td>text</td>
				<td>The SIS ID of the enrollment term for the course</td>
			</tr>
			<tr>
				<td>status</td>
				<td>enum</td>
				<td><code>active</code>, <code>deleted</code>, <code>completed</code></td>
			</tr>
			<tr>
				<td>start_date</td>
				<td>date</td>
				<td>The course start date. The format should be in ISO 8601: <code>YYYY-MM-DDTHH:MM:SSZ</code></td>
			</tr>
			<tr>
				<td>end_date</td>
				<td>date</td>
				<td>The coruse end date. The format should be in ISO 8601: <code>YYYY-MM-DDTHH:MM:SSZ</code></td>
			</tr>
		</tbody>
	</table>
	
	<p>Sample:</p>
	<pre>old_course_id, course_id, short_name, long_name, account_id, term_id
2015-summer-AR-46A-1-Putnam, 2015-2016-ar-46a-1-blue, Advanced Art History (Blue), Advanced Art History (Blue), 112, 2015-2016-full-year
2015-summer-EN-45F-1-Eslick, 2015-2016-en-45f-1-brown, "Cold War, Cool Culture (Brown)", "Cold War, Cool Culture (Brown)", 119, 2015-2015-semester-fall</pre>

{include file="batch-update-courses/upload-form.tpl"}

{/block}