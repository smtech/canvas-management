{extends file="form.tpl"}

{block name="form-content"}

	<div class="form-group">
		<label for="csv" class="control-label col-sm-{$formLabelWidth}">CSV File</label>
		<div class="col-sm-{12 - $formLabelWidth}">
			<input name="csv" id="csv" type="file" class="form-control" />
			<p span="help-block"><!--Optionally u-->Upload a standard <a target="_parent" href="https://stmarksschool.test.instructure.com/doc/api/file.sis_csv.html">sections.csv</a> file to assign SIS IDs to course sections.</p>
		</div>
	</div>
	
	<div class="form-group">
		<div class="checkbox col-sm-offset-{$formLabelWidth} col-sm-{12 - $formLabelWidth}">
			<label for="rename-singletons" class="control-label">
				<input id="rename-singletons" name="rename-singletons" value="rename" type="checkbox" checked="checked" /> Rename singleton sections to match course name</label>
		</div>
	</div> 
	
	<!--{include file="select-account.tpl"}-->
	
	<!--{include file="select-term.tpl"}-->
	
	{assign var="formButton" value="Normalize"}

{/block}