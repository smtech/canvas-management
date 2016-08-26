{assign var="__DIR__" value=$smarty.current_dir}
{extends file="subpage.tpl"}

{block name="subcontent"}

<div class="container">
	<p>Review the changes listed below before updating.</p>
</div>

{include file="$__DIR__/confirm-form.tpl"}

{/block}