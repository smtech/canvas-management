{extends file="form.tpl"}

{block name="form-content"}

	<div class="form-group">
		<label for="course" class="control-label col-sm-{$formLabelWidth}">Course</label>
		<span class="col-sm-{12 - $formLabelWidth}">
			<input id="course" name="course" type="text" class="form-control" placeholder="Search for&hellip;" value="{$course}" />
		</span>
	</div>
	
	{assign var="formButton" value="Search"}

{/block}