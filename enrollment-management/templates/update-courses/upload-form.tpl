{extends file="form.tpl"}

{block name="form-content"}

<div class="form-group">
	<label for="csv" class="control-label col-sm-2">CSV File</label>
	<div class="col-sm-10">
		<input name="csv" id="csv" type="file" class="form-control" />
	</div>
</div>

{assign var="formButton" value="Upload"}

{/block}