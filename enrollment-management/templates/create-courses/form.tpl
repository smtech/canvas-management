{extends file="form.tpl"}

{block name="form-content"}

	<div class="form-group">
		<label for="courses" class="control-label col-sm-{$formLabelWidth}">Course Names</label>
		<div class="col-sm-{12 - $formLabelWidth}">
			<textarea name="courses" id="courses" class="form-control" placeholder="One course per line" autofocus="autofocus" rows="5"></textarea>
		</div>
	</div>
	
	<div class="form-group">
		<label for="csv" class="control-label col-sm-{$formLabelWidth}">CSV File</label>
		<div class="col-sm-{12 - $formLabelWidth}">
			<input type="file" id="csv" name="csv" class="form-control" />
			<p class="help-block">If a CSV file is uploaded, the manually-entered course names above will be pre-pended to the CSV file. The CSV file should follow the usual <a href="https://canvas.instructure.com/doc/api/file.sis_csv.html">courses.csv</a> format.</p>
		</div>
	</div>

	<div class="form-group">
		<label for="template" class="control-label col-sm-{$formLabelWidth}">Template</label>
		<div class="col-sm-10">
			<input type="text" name="template" id="template" class="form-control" placeholder="Canvas or SIS ID (leave blank for none)" value="course-template"/>
		</div>
	</div>
	
	{include file="select-account.tpl"}
	
	{include file="select-term.tpl"}
	
	<div class="form-group">
		<label for="prefix" class="control-label col-sm-{$formLabelWidth}">SIS ID prefix</label>
		<div class="col-sm-3">
			<input type="text" id="prefix" name="prefix" class="form-control " placeholder="2015-2016" />
		</div>
	</div>
	
	<div class="form-group">
		<label for="suffix" class="control-label col-sm-{$formLabelWidth}">SIS ID suffix</label>
		<div class="col-sm-3">
			<input type="text" id="suffix" name="suffix" class="form-control" placeholder="abracadabra" />
		</div>
	</div>

	<div class="form-group">
		<div class="checkbox col-sm-offset-{$formLabelWidth} col-sm-2">
			<label for="unique" class="control-label">
				<input type="checkbox" id="unique" name="unique" value="true"> Unique SIS IDs</label>
		</div>
	</div>
	
	{assign var="formButton" value="Create"}

{/block}