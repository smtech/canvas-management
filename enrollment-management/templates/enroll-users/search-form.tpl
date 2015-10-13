{extends file="form.tpl"}

{block name="form-content"}

	<div class="form-group">
		<label for="users" class="control-label col-sm-{$formLabelWidth}">Users</label>
		<div class="col-sm-{12 - $formLabelWidth}">
			<textarea name="users" id="users" class="form-control" placeholder="Comma-separated and/or one user search term per line" autofocus="autofocus" rows="5">{$users|default: ""}</textarea>
		</div>
	</div>

	<div class="form-group">
		<label for="course" class="control-label col-sm-{$formLabelWidth}">Course</label>
		<div class="col-sm-{12 - $formLabelWidth}">
			<input type="text" name="course" id="course" class="form-control" placeholder="Course search term" value="{$course|default: ""}" />
		</div>
	</div>
	
	<div class="form-group">
		<label for="role" class="control-label col-sm-{$formLabelWidth}">Role</label>
		<div class="col-sm-3">
			<select name="role" id="role" class="form-control">
				{foreach $roles as $r}
					<option value="{$r['id']}" {if $r['id'] == $role}selected="selected"{/if}>{$r['label']}</option>
				{/foreach}
			</select>
		</div>
	</div>
	
	<!-- TODO enroll by section as well -->

	{assign var="formButton" value="Search"}

{/block}