{assign var="__DIR__" value=$smarty.current_dir}
{extends file="subpage.tpl"}

{block name="subcontent"}

	<div class="container">
		<div class="readable-width">
			<p>Delete enrollments by role from a particular course</p>
		</div>
	</div>
	
	{include file="$__DIR__/search.tpl"}

{/block}