{extends file="page.tpl"}
{block name="content"}

<style>
	.unassigned {
		background: yellow;
	}
	
	td {
		white-space: nowrap;
	}
</style>

{if !empty($unassignedUsers)}

<h2>Unassigned Users</h2>

	<form action="{$formAction}" method="post">
		<input type="submit" value="Assign" />
		<table>
			<tr>
				<th>Name</th>
				<th>Role</th>
			</tr>
			{foreach $unassignedUsers as $id => $user}
				<tr class="unassigned">
					<td>
						<a href="{$instance}/accounts/1/users/{}$id}">{$user['user']['sortable_name']}</a>
					</td>
					<td>
						<select name="users[{$id}]">
						{foreach $roles as $role}
							<option value="{$role}"
								{if $role === 'student'}
									selected="selected"
								{/if} />
								{$role}
							</option>
						{/foreach}
						</select>
					</td>
				</tr>
			{/foreach}
		</table>
		<input type="submit" value="Assign" />
	</form>

{/if}


{if !empty($assignedUsers)}

<h2>Assigned Users</h2>

	<form action="{$formAction}" method="post">
		<input type="submit" value="Update" />
		<table>
			<tr>
				<th>Name</th>
				<th>Role</th>
			</tr>
			{foreach $assignedUsers as $id => $user}
				<tr {if empty($user['custom-prefs'])}class="unassigned"{/if}>
					<td>
						<a href="{$instance}/accounts/1/users/{}$id}">{$user['user']['sortable_name']}</a>
					</td>
					<td>
						<select name="users[{$id}]">
							{foreach $roles as $role}
								<option value="{$role}"
									{if $role === $user['custom-prefs']['role']}
										selected="selected"
									{/if} />
									{$role}
								</option>
							{/foreach}
						</select>
					</td>
				</tr>
			{/foreach}
		</table>
		<input type="submit" value="Save" />
	</form>

{/if}

{/block}