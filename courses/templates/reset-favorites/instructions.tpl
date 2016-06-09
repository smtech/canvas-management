{assign var="__DIR__" value=$smarty.current_dir}
{extends file="subpage.tpl"}

{block name="subcontent"}

	<div class="container">
		<div class="readable-width">
			<p>Reset the favorites for all users (in an account) back to their defaults.</p>
		</div>
	</div>
	
	{assign var="formButton" value="Reset Favorites"}

	{include file="$__DIR__/form.tpl"}

{/block}