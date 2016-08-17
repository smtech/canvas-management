{assign var="role" value=$role|default: null}
{extends file="form.tpl"}

{block name="form-content"}

	<div class="form-group">
		<label for="role" class="control-label col-sm-{$formLabelWidth}">Role</label>
		<div class="col-sm-3">
			<select id="role" name="role" class="form-control selectpicker">
				<option value="" disabled="disabled" selected="selected">Select a role</option>
				<option disabled="disabled"></option>
				{foreach $roles as $_role}
					<option value="{$_role['id']}"{if $role == $_role['id']} selected{/if}>{$_role['role']}</option>
				{/foreach}
			</select>
		</div>
	</div>

    {assign var="formButton" value="Assign to Teachers"}

{/block}
