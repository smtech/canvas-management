{extends file="subpage.tpl"}

{block name="subcontent"}

	<div class="container">
		<div class="readable-width">
			<p>Archive conversations from a particular user's inbox before a cutoff date.</p>
		</div>
	</div>
	
	{include file="archive-inbox/search.tpl"}
		
{/block}