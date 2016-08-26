{assign var="__DIR__" value=$smarty.current_dir}
{extends file="subpage.tpl"}
{block name="subcontent"}

<div class="container">
	<div class="readable-width">
		<p>Reset all users in a particular sub-account to a baseline set of defaults (St. Mark's email notifications for new announcements and inbox messages).</p>
	</div>
</div>

{include file="$__DIR__/form.tpl"}

{/block}
