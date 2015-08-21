{extends file="subpage.tpl"}
{block name="subcontent"}

<div class="container">
	Assign roles to users external to Canvas (e.g. faculty or student), for use by custom-prefs-reliant tools to generate things like custom navigation menus based on those roles.
</div>

{include file="assign-user-roles/form.tpl"}

{/block}