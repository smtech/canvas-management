{extends file="subpage.tpl"}
{block name="subcontent"}

<style>
	.unassigned {
		background: yellow;
	}
	
	td {
		white-space: nowrap;
	}
</style>

{if !empty($unassignedUsers)}

<div class="container">
	<h3>Unassigned Users</h3>
	{$usersGroup = $unassignedUsers}
	{include file="assign-user-roles/group-subform.tpl"}
</div>

{/if}


{if !empty($assignedUsers)}

<div class="container">
	<h3>Assigned Users</h3>
	{$usersGroup = $assignedUsers}
	{include file="assign-user-roles/group-subform.tpl"}
</div>

{/if}

{/block}