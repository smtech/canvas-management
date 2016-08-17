{assign var="__DIR__" value=$smarty.current_dir}
{extends file="subpage.tpl"}

{block name="subcontent"}

    {include file="$__DIR__/form-role.tpl"}

{/block}
