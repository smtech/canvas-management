{assign var="formLabelWidth" value=$formLabelWidth|default: 2}
{assign var="accounts" value=$accounts|default: null}
{assign var="account" value=$account|default: null}
{block name="select-account"}

	<div class="form-group">
		<label for="account" class="control-label col-sm-{$formLabelWidth}">Account</label>
		<div class="col-sm-3">
			<select id="account" name="account" class="form-control">
				<option value="" disabled="disabled" selected="selected">Select an account</option>
				<option disabled="disabled"></option>
				{foreach $accounts as $_account}
					<option value="{$_account['id']}"{if $account == $_account['id']} selected{/if}>{$_account['name']}</option>
				{/foreach}
			</select>
		</div>
	</div>

{/block}
