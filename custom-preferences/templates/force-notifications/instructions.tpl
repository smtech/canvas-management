{assign var="__DIR__" value=$smarty.current_dir}
{extends file="subpage.tpl"}

{block name="subcontent"}

    <div class="container">
        <div class="readable-width">
            <p>Force a particular set of notification preferences on to a class of users within an account. For example, disable all notifications for advisor-observers or force announcements on for students, etc.</p>
        </div>
    </div>

    {include file="$__DIR__/form.tpl"}

{/block}
