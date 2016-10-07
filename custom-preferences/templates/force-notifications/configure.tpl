{assign var="__DIR__" value=$smarty.current_dir}
{extends file="subpage.tpl"}

{block name="subcontent"}

    <div class="container">
        <div class="readable-width">
            <p>Configure notification preferences for users in the {$account['name']} sub-account.</p>
        </div>
    </div>

    {include file="$__DIR__/configure-form.tpl"}

{/block}
