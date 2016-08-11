{assign var="__DIR__" value=$smarty.current_dir}
{assign var="csv" value=$csv|default:false}
{extends file="subpage.tpl"}

{block name="subcontent"}

<div class="container">
	<div class="readable-width">
		<p>Download a CSV file of all users enrolled in a particular subaccount in a particular term, with their associated enrollments listed.</p>

		{if $csv}<p>If the download does not begin automatically, <a href="{$APP_URL}/generate-csv.php?data={$csv}&filename={$filename}">click here.</a><iframe src="{$APP_URL}/generate-csv.php?data={$csv}&filename={$filename}" style="display: none;"></iframe></p>{/if}
	</div>
</div>

{include file="$__DIR__/form.tpl"}

{/block}
