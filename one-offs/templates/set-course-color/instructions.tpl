{assign var="__DIR__" value=$smarty.current_dir}
{extends file="subpage.tpl"}

{block name="subcontent"}

	<div class="container">
		<div class="readable-width">
			<p>Force the custom color of a specific course to a specific color for all enrolled users of that course. (N.B. this is course-specific and does not handle sections.)</p>
		</div>
	</div>
	
	{include file="$__DIR__/course-search.tpl"}

{/block}