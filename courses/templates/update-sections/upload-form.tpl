{assign var="ignore_course_id" value=$ignore_course_id|default: true}
{extends file="form.tpl"}

{block name="form-content"}

    <div class="form-group">
    	<label for="csv" class="control-label col-sm-{$formLabelWidth}">CSV File</label>
    	<div class="col-sm-{12 - $formLabelWidth}">
    		<input name="csv" id="csv" type="file" class="form-control" />
    	</div>
    </div>

    <div class="form-group">
        <div class="checkbox col-sm-offset-{$formLabelWidth}">
            <label for="ignore_course_id" class="control-label">
                <input id="ignore_course_id" name="ignore_course_id" type="checkbox" {if $ignore_course_id}checked {/if}/>
                Ignore <code>course_id</code>
            </label>
            <p class="help-block">Check this box if sections have already been merged into courses and you do not wish to override those merges.</p>
        </div>
    </div>

    {assign var="formButton" value="Upload"}

{/block}
