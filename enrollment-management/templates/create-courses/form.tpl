{extends file="form.tpl"}
{block name="form-content"}

	<div class="form-group">
		<label for="courses" class="control-label col-sm-2">Course Names</label>
		<div class="col-sm-10">
			<textarea name="courses" id="courses" class="form-control" placeholder="Comma-separated and/or one course per line" autofocus="autofocus" rows="5"></textarea>
		</div>
	</div>

	<div class="form-group">
		<label for="template" class="control-label col-sm-2">Template</label>
		<div class="col-sm-10">
			<input type="text" name="template" id="template" class="form-control" placeholder="Canvas or SIS ID (leave blank for none)" value="course-template"/>
		</div>
	</div>
	
	<div class="form-group">
		<label for="account" class="control-label col-sm-2">Account</label>
		<div class="col-sm-3">
			<select id="account" name="account" class="form-control">
				<option value="" disabled="disabled" selected="selected">Select an account</option>
				<option disabled="disabled"></option>
				{foreach $accounts as $account}
					<option value="{$account['id']}">{$account['name']}</option>
				{/foreach}
			</select>
		</div>
	</div>

	<div class="form-group">
		<label for="term" class="control-label col-sm-2">Term</label>
		<div class="col-sm-3">
			<select id="term" name="term" class="form-control selectpicker">
				<option value="" disabled="disabled" selected="selected">Select a term</option>
				<option disabled="disabled"></option>
				{foreach $terms as $term}
					<option value="{$term['id']}">{$term['name']}</option>
				{/foreach}
			</select>
		</div>
	</div>

	<div class="form-group">
		<div class="col-sm-offset-2 col-sm-10">
			<button type="submit" class="btn btn-default">Create</button>
		</div>	
	</div>

{/block}