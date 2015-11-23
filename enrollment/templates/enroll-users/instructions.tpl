{assign var="__DIR__" value=$smarty.current_dir}
{extends file="subpage.tpl"}

{block name="subcontent"}

<div class="container">
	<div class="readable-width">
		<p>Enroll users with a bit more power than usual.</p>
	</div>
	
	{include file="$__DIR__/search-form.tpl"}
</div>

{/block}