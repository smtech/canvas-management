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
				<option value="">All Roles</option>
				<option value="" disabled="disabled"><hr/></option>
				{foreach $roles as $role}
					<option value="{$role['role']}">{$role['label']}s Only</option>
				{/foreach}
			</select>
		</span>
	</div>
	
	<div class="form-group">
		<label for="state" class="control-label col-sm-{$formLabelWidth}">State</label>
		<span class="col-sm-{12 - $formLabelWidth}">
			<select id="state" name="state" class="form-control">
				<option value="">Active and Invited Only</option>
				<option value="" disabled="disabled"><hr/></option>
				{foreach $states as $state}
					<option value="{$state}">{ucwords(str_replace('_', ' ', $state))} Only</option>
				{/foreach}
			</select>
		</span>
	</div>

	{assign var="formButton" value="Delete Enrollments"}

{/block}