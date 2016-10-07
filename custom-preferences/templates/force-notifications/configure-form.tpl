{extends file="form.tpl"}

{block name="form-content"}

    <div class="form-group">
        <label class="control-label col-sm-{$formLabelWidth}">Custom Prefs Role</label>
        <span class="col-sm-4">
            <select name="role" class="form-control">
                {foreach $roles as $role}
                    <option value="{$role}">{$role|capitalize}</option>
                {/foreach}
            </select>
        </span>
    </div>

    {assign var="notificationCategory" value=false}
    {foreach $notifications as $notification}
        {if $notificationCategory != $notification['category']}
            <h4>{$notification['category']}</h4>
            {assign var="notificationCategory" value=$notification['category']}
        {/if}
        <div class="form-group">
            <label class="control-label col-sm-{$formLabelWidth}}">{$notification['notification']|capitalize}</label>
            <span class="col-sm-6">
                <select class="form-control" name="notification_preferences[{$notification['notification']}][frequency]">
                    {foreach $frequencies as $frequency}
                        <option value="{$frequency}" {if $frequency == 'never'} selected="selected"{/if}>{$frequency|capitalize}</option>
                    {/foreach}
                </select>
            </span>
        </div>
    {/foreach}

    {assign var="formButton" value="Force Notifications"}

{/block}
