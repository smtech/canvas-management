{assign var="__DIR__" value=$smarty.current_dir}
{extends file="subpage.tpl"}

{block name="subcontent"}

<div class="container">
    <p>ARchive all of the courses in a particular account and term. Archived courses will be renamed to match the pattern:</p>
    <p><code>{literal}{year} {title} ([{Fall|Winter|Spring}, ][{Color}, ]{Teachers}){/literal}</code></p>

    {include file="$__DIR__/form.tpl"}
{/block}
