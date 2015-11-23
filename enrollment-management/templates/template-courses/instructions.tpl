{assign var="__DIR__" value=$smarty.current_dir}
{extends file="subpage.tpl"}

{block name="subcontent"}

	{assign var="formFileUpload" value=true}
	{include file="$__DIR__/form.tpl"}
	
{/block}