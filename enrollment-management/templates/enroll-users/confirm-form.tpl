{extends file="form.tpl"}

{block name="form-content"}

	<div class="panel panel-default">
		<div class="panel-heading">
			<h3 class="panel-title">Course</h3>
		</div>
		<div class="form-group">
			<label for="course" class="control-label col-sm-2">Course</label>
			<div class="col-sm-3">
				<select id="course" name="course" class="form-control">
					<option value="" disabled="disabled" selected="selected">Choose the desired course</option>
					<option disabled="disabled"></option>
					{foreach $courses as $course}
						<optgroup>
							<option value="{$course['id']}">{$course['name']}</option>
							<option disabled="disabled">{$course['term']['name']}</option>
							<option disabled="disabled">{$course['sis_course_id']}</option>
						</optgroup>
					{/foreach}
				</select>
			</div>
		</div>
	</div>

	<div class="panel panel-default">
		<div class="panel-heading">
			<h3 class="panel-title">Users</h3>
		</div>
		{counter start=0 assign="i" print=false}
		{foreach $confirm as $term => $search}
			<div class="form-group">
				<label for="user-{$i}-name" class="control-label col-sm-2">{$term}</label>
				<input type="hidden" name="users[{$i}][term]" value="{$term}" />
				<div class="col-sm-3">
					<select id="user-{$i}-name" name="users[{$i}][id]" class="form-control selectpicker">
						{foreach $search as $user}
							<option value="{$user['id']}">{$user['name']}</option>
						{/foreach}
					</select>
				</div>
				<label for="user-{$i}-role" class="sr-only">Role</label>
				<div class="col-sm-2">
					<select id="user-{$i}-role" name="users[{$i}][role]" class="form-control selectpicker">
						{foreach $roles as $enrollment => $role}
							<option value="{$enrollment}">{$role}</option>
						{/foreach}
					</select>
				</div>
				<div class="col-sm-3 checkbox">
					<label for="user-{$i}-notify" class="control-label">
						<input type="checkbox" id="user-{$i}-notify" name="users[{$i}][notify]" value="true" /> Send notification
					</label>
				</div>
			</div>
			{counter assign="i" print=false}
		{/foreach}
	</div>

	<div class="form-group">
		<div class="col-sm-offset-2 col-sm-10">
			<button type="button" class="btn btn-default" onclick="history.back();"><span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span> Edit</button>
			<button type="submit" class="btn btn-primary">Enroll</button>
		</div>
	</div>


{/block}