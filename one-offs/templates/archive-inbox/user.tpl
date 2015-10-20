{extends file="form.tpl"}

{block name="form-content"}

	<div class="form-group">
		<label for="user" class="control-label col-sm-{$formLabelWidth}">User</label>
		<div class="col-sm-4">
			<select id="user" name="user" class="form-control">
				{foreach $users as $user}
					<option value="{$user['id']}">{$user['name']}</option>
				{/foreach}
			</select>
		</div>
	</div>
	
	<div class="form-group">
		<label for="cutoff" class="control-label col-sm-{$formLabelWidth}">Cutoff</label>
		<div class="col-sm-4">
			<div class="input-group date">
				<input type="text" class="form-control" name="cutoff" id="cutoff" placeholder="Archive conversations before&hellip;" />
				<div class="input-group-addon">
					<span class="glyphicon glyphicon-calendar"></span>
				</div>
			</div>
		</div>
	</div>
	
	{assign var="formButton" value="Archive"}

{/block}