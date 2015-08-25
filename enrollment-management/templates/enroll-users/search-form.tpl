{extends file="form.tpl"}

{block name="form-content"}

	<div class="form-group">
		<label for="users" class="control-label col-sm-2">Users</label>
		<div class="col-sm-10">
			<textarea name="users" id="users" class="form-control" placeholder="Comma-separated and/or one user search term per line" autofocus="autofocus" rows="5">{$users|default: ""}</textarea>
		</div>
	</div>

	<div class="form-group">
		<label for="course" class="control-label col-sm-2">Course</label>
		<div class="col-sm-10">
			<input type="text" name="course" id="course" class="form-control" placeholder="Course search term" value="{$course|default: ""}" />
		</div>
	</div>

	{assign var="formButton" value="Search"}

{/block}