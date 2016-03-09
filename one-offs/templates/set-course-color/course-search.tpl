{extends file="form.tpl"}

{block name="form-content"}

	<div class="form-group">
		<label for="course" class="control-label col-sm-{$formLabelWidth}">Course</label>
		<span class="col-sm-{12 - $formLabelWidth}">
			<input type="text" id="course" name="course" class="form-control" placeholder="Search for a course" value="{$course|default: ""}" />
		</span>
	</div>
	
	{assign var="formButton" value="Search"}

{/block}