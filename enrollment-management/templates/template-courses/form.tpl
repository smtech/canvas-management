{extends file="form.tpl"}

{block name="form-content"}

	<div class="container">
		<div class="readable-width">
			<p>Apply a course template to existing courses. Upload a <a target="_parent" href="https://canvas.instructure.com/doc/api/file.sis_csv.html">courses.csv</a> file.</p>
		</div>
	</div>

	<div class="form-group">
		<label for="template" class="control-label col-sm-{$formLabelWidth}">Template</label>
		<div class="col-sm-10">
			<input type="text" name="template" id="template" class="form-control" placeholder="Canvas or SIS ID (leave blank for none)" value="course-template"/>
		</div>
	</div>
	
	
	<div class="form-group">
		<label for="csv" class="control-label col-sm-{$formLabelWidth}">CSV File</label>
		<div class="col-sm-4">
			<input type="file" id="csv" name="csv" class="form-control" />
		</div>
	</div>
	
	{assign var="formButton" value="Search"}

{/block}