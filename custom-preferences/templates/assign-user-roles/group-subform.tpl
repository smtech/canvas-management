{extends file="form.tpl"}

{block name="form-content"}

	<div class="form-group">
		<button type="submit" class="btn btn-default">Assign</button>
	</div>
	
	{foreach $usersGroup as $id => $user}
	
	<div class="form-group">
		<input type="hidden" name="users[{$id}][dirty]" value="0" id="user-{$id}-changed" />
		<label for="input1" class="control-label col-sm-4" style="text-align: left;"><a href="{$metadata['CANVAS_INSTANCE_URL']}/accounts/1/users/{}$id}">{$user['user']['sortable_name']}</a></label>
		<div class="col-sm-2">
			<select name="users[{$id}][role]" class="form-control" onchange="document.getElementById('user-{$id}-changed').value = 1;">
				{if empty($user['custom-prefs'])}
					<option selected="selected">Select a role</option>
					<option disabled="disabled"></option>
				{/if}
				{foreach $roles as $role}
					<option value="{$role}"
						{if $role === $user['custom-prefs']['role']}
							selected="selected"
						{/if} />
						{$role}
					</option>
				{/foreach}
				</select>
		</div>
	</div>

		<tr class="unassigned">
			<td>
				
			</td>
			<td>
				
			</td>
		</tr>
	{/foreach}

	<div class="form-group">
		<button type="submit" class="btn btn-default">Assign</button>
	</div>

{/block}