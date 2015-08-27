{extends file="subpage.tpl"}

{block name="subcontent"}

<div class="container">
	<div class="readable-width">
		<p>Force Section IDs in 2015-2016 courses that match the Blackbaud Import ID pattern to be capitalized to match Blackbaud's output.</p>
	</div>
</div>

{include file="fix-section-sis-ids/form.tpl"}

{/block}