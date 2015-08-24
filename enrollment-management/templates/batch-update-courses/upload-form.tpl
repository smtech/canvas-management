<div class="container">
	<form action="{$formAction}" method="post" class="form-horizontal" role="form" enctype="multipart/form-data">

		{if !empty($formHidden)}
			{foreach $formHidden as $formHiddenName => $formHiddenValue}
				<input type="hidden" type="hidden" name="{$formHiddenName}" value="{$formHiddenValue}" />
			{/foreach}
		{/if}

		{block name="form-content"}
		
		<div class="form-group">
			<label for="csv" class="control-label col-sm-2">CSV File</label>
			<div class="col-sm-10">
				<input name="csv" id="csv" type="file" class="form-control" />
			</div>
		</div>
		<div class="form-group">
			<div class="col-sm-offset-2 col-sm-10">
				<button type="submit" class="btn btn-default">Upload</button>
			</div>	
		</div>
		
		{/block}

	</form>
</div>