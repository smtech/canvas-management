{extends file="form.tpl"}

{block name="form-content"}

	<div class="form-group">
		<label for="user" class="control-label col-sm-{$formLabelWidth}">User</label>
		<div class="col-sm-{12 - $formLabelWidth}">
			<input id="user" name="user" type="text" class="form-control" placeholder="Joseph Burnett" value="{$user|default:''}" />
		</div>
	</div>
	
	{assign var="formButton" value="Search"}

{/block}