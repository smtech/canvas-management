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
            {if $notificationCategory !== false}
                 </div></div>
            {/if}
            <div class="panel panel-default"><div class="panel-heading"><p class="panel-title">{titleCase($notification['category'])}</p></div><div class="panel-body">
            {assign var="notificationCategory" value=$notification['category']}
        {/if}
        {if $notification['notification'] != 'confirm_sms_communication_channel'}
            <div class="form-group">
                <label class="control-label col-sm-6">{titleCase($notification['notification'])}</label>
                <span class="col-sm-3">
                    <select class="form-control" name="notification_preferences[{$notification['notification']}][frequency]">
                        {foreach $frequencies as $frequency}
                            <option value="{$frequency}" {if $frequency == 'never'} selected="selected"{/if}>{$frequency|capitalize}</option>
                        {/foreach}
                    </select>
                </span>
                <label for="{$notification['notification']}" class="control-label col-sm-2">
                    <input type="checkbox" id="{$notification['notification']}" name="notification_preferences[{$notification['notification']}][enabled]" />
                    Include
                </label>
            </div>
        {/if}
    {/foreach}
    {if $notificationCategory !== false}
        </div></div>
    {/if}

    {assign var="formButton" value="Force Notifications"}

{/block}
