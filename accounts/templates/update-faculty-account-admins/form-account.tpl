{assign var="terms_selected" value=$terms_selected|default: []}
{extends file="form.tpl"}

{block name="form-content"}

    {include file="select-account.tpl"}

    <div class="form-group">
        <label class="control-label col-sm-{$formLabelWidth}">Terms</label>
        <span class="col-sm-{12 - $formLabelWidth}">
            {foreach $terms as $term}
                <label class="checkbox-inline col-xs-3" for="term-{$term['id']}">
                    <input type="checkbox" id="term-{$term['id']}" name="terms_selected[{$term['id']}]" value="{$term['id']}" {if in_array($term['id'], $terms_selected)}checked{/if} /> {$term['name']}
                </label>
            {/foreach}
        </span>
    </div>

    <div class="form-group">
        <div class="checkbox">
            <label>
                <input type="checkbox" name="confirmation" value="true" /> Send confirmations
            </label>
        </div>
    </div>

    {assign var="formButton" value="Choose Role"}

{/block}
