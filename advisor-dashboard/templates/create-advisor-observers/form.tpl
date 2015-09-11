{extends file="form.tpl"}

{block name="form-content"}

	{include file="select-account.tpl"}
	
	{include file="select-term.tpl"}
	
	<div class="form-group">
		<div class="col-sm-offset-{$formLabelWidth}">
			<div class="checkbox">
				<label for="reset_passwords" class="control-label">
				<input type="checkbox" name="reset_passwords" id="reset_passwords" value="true" />
				Reset existing observer passwords
				</label>
			</div>
		</div>
	</div>

	{assign var="formButton" value="Generate"}

{/block}