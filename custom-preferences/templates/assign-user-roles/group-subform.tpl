{extends file="form.tpl"}

{block name="form-content"}

	{foreach $usersGroup as $role => $users}

		<h4>{$role}</h4>

		{foreach $users as $id => $user}

			<div class="form-group">
				<input type="hidden" name="users[{$id}][dirty]" value="0" id="user-{$id}-changed" />
				<label for="input1" class="control-label col-sm-4" style="text-align: left;"><a target="_parent" href="{$CANVAS_INSTANCE_URL}/accounts/1/users/{$id}">{$user['user']['sortable_name']}</a></label>
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
				<div class="col-sm-6">
					<p>
						{foreach $user['custom-prefs']['groups'] as $group}
							<span class="label label-default">{$group['name']}</span>
						{/foreach}
					</p>
				</div>
			</div>

		{/foreach}
	{/foreach}

	{assign var="formButton" value="Assign"}

{/block}
