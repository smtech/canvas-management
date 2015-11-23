{assign var="__DIR__" value=$smarty.current_dir}
{extends file="subpage.tpl"}

{block name="subcontent"}

	<div class="container">
		<div class="readable-width">
			<p>Archive conversations from a particular user's inbox before a cutoff date.</p>
		</div>
	</div>
	
	{include file="$__DIR__/search.tpl"}
		
{/block}