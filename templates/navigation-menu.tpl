{function menuItem}
    <li><a href="{$data['url']}">{$data['title']}</a></li>
{/function}

{function menuDropdown navbar=true}
    <li class="dropdown {if $navbarActive == $key}active{/if}">
        <a href="#"{if $navbar} class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"{/if}>
            {$data['title']}{if $navbar} <span class="caret"></span>{/if}
        </a>
        <ul{if $navbar} class="dropdown-menu"{/if}>
            {foreach $data['submenu'] as $submenuData}
                {if (array_key_exists('submenu', $submenuData))}
                    {menuDropdown key=$submenuData@key data=$submenuData navbar=false}
                {else}
                    {menuItem data=$submenuData}
                {/if}
            {/foreach}
        </ul>
    </li>
{/function}

{block name="navigation-menu"}
<div class="container-fluid">
<ul class="nav navbar-nav">
    <li {if $navbarActive == 'canvas-management'}class="active"{/if}}><a href="{$APP_URL}/">Home</a></li>

    {foreach $menuItems as $data}
        {menuDropdown key=$data@key data=$data}
    {/foreach}
</ul>
</div>
{/block}
