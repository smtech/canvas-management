{assign var="__DIR__" value=$smarty.current_dir}
{extends file="subpage.tpl"}
{block name="subcontent"}

<div class="container">
	<div class="readable-width">
		<p>Assign "meaningful" default colors to courses (i.e. colors that match the named color block) for all users in the courses of a particular account and term.</p>
	</div>
</div>

{include file="$__DIR__/form.tpl"}

{/block}