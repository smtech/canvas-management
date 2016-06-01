{extends file="form.tpl"}

{block name="form-content"}

	<div class="form-group">
		<label for="course" class="control-label col-sm-{$formLabelWidth}">Course</label>
		<span class="col-sm-{12 - $formLabelWidth}">
			<select id="course" name="course" class="form-control">
				{foreach $courses as $course}
					<option value="{$course['id']}">{$course['name']}</option>
				{/foreach}
			</select>
		</span>
	</div>
	
	<div class="form-group">
		<label for="role" class="control-label col-sm-{$formLabelWidth}">Role</label>
		<span class="col-sm-{12 - $formLabelWidth}">
			<select id="role" name="role" class="form-control">
				{foreach $roles as $role}
					<option value="{$role['role']}">{$role['label']}</option>
				{/foreach}
			</select>
		</span>
	</div>

	{assign var="formButton" value="Delete Enrollments"}

{/block}