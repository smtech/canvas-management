{assign var="__DIR__" value=$smarty.current_dir}
{extends file="subpage.tpl"}

{block name="subcontent"}
	
	<div class="container">
		<div class="readable-width">
			<p>Forcibly publish every unpublished course in a given account and enrollment term.</p>
		</div>
	</div>
	
	{include file="$__DIR__/form.tpl"}

{/block}