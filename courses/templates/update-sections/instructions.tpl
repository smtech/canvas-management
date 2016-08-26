{assign var="__DIR__" value=$smarty.current_dir}
{extends file="subpage.tpl"}

{block name="subcontent"}

<div class="container">
	<p>Upload a CSV file of sections to be updated in the format described below. Columns left blank will not be affected by the batch update.</p>

	{assign var="formFileUpload" value="true"}
	{include file="$__DIR__/upload-form.tpl"}

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
				<td>old_section_id</td>
				<td>text</td>
				<td><strong>Required field.</strong> The unique SIS ID of the section to be updated.</td>
			</tr>
            <tr>
                <td>section_id</td>
                <td>text</td>
                <td>A new unique SIS ID (to replace <code>old_section_id</code>) for the section./td>
            </tr>
            <tr>
                <td>course_id</td>
                <td>text</td>
                <td>The course identifier from <a target="_blank" href="https://canvas.instructure.com/doc/api/file.sis_csv.html">courses.csv</a></td>
            </tr>
            <tr>
                <td>name</td>
                <td>text</td>
                <td>The name of the section</td>
            </tr>
            <tr>
                <td>status</td>
                <td>enum</td>
                <td><code>active</code>, <code>deleted</code></td>
            </tr>
            <tr>
                <td>start_date</td>
                <td>date</td>
                <td>The section start date. The format should be in ISO 8601: <code>YYYY-MM-DDTHH:MM:SSZ</code></td>
            </tr>
            <tr>
                <td>end_date</td>
                <td>date</td>
                <td>The section end date The format should be in ISO 8601: <code>YYYY-MM-DDTHH:MM:SSZ</code></td>
            </tr>
		</tbody>
	</table>

	<p>Sample:</p>
	<pre>old_section_id,section_id,course_id,name,status,start_date,end_date
WGLSQLA312253-12984,WGLSQLA312253-12991,2016-2017-SC-40F-1-Red,Biotechnology (Red),active,,
WGLSQLA312253-13266,WGLSQLA312253-12990,2016-2017-AR-35F-2-Brown,Independent Performance Studio (Brown),active,,
    </pre>

{/block}
