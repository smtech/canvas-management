{assign var="__DIR__" value=$smarty.current_dir}
{extends file="form.tpl"}

{block name="form-content"}

    {include file="select-account.tpl"}

    {assign var="formButton" value="Configure"}

{/block}
