<div class="container">
	<form action="{$formAction}" method="post" class="form-horizontal" role="form" enctype="multipart/form-data">

		{if !empty($formHidden)}
			{foreach $formHidden as $formHiddenName => $formHiddenValue}
				<input type="hidden" type="hidden" name="{$formHiddenName}" value="{$formHiddenValue}" />
			{/foreach}
		{/if}

		{block name="form-content"}
		
			<div class="form-group">
				<label for="courses" class="control-label col-sm-2">Course Names</label>
				<div class="col-sm-10">
					<textarea name="courses" id="courses" class="form-control" placeholder="One course per line" autofocus="autofocus" rows="5"></textarea>
				</div>
			</div>
			
			<div class="form-group">
				<lable for="csv" class="control-label col-sm-2">CSV File</lable>
				<div class="col-sm-10">
					<input type="file" id="csv" name="csv" class="form-control" />
					<p class="help-block">If a CSV file is uploaded, the manually-entered course names above will be pre-pended to the CSV file. The CSV file should follow the usual <a href="https://canvas.instructure.com/doc/api/file.sis_csv.html">courses.csv</a> format.</p>
				</div>
			</div>
		
			<div class="form-group">
				<label for="template" class="control-label col-sm-2">Template</label>
				<div class="col-sm-10">
					<input type="text" name="template" id="template" class="form-control" placeholder="Canvas or SIS ID (leave blank for none)" value="course-template"/>
				</div>
			</div>
			
			<div class="form-group">
				<label for="account" class="control-label col-sm-2">Account</label>
				<div class="col-sm-3">
					<select id="account" name="account" class="form-control">
						<option value="" disabled="disabled" selected="selected">Select an account</option>
						<option disabled="disabled"></option>
						{foreach $accounts as $account}
							<option value="{$account['id']}">{$account['name']}</option>
						{/foreach}
					</select>
				</div>
			</div>
		
			<div class="form-group">
				<label for="term" class="control-label col-sm-2">Term</label>
				<div class="col-sm-3">
					<select id="term" name="term" class="form-control selectpicker">
						<option value="" disabled="disabled" selected="selected">Select a term</option>
						<option disabled="disabled"></option>
						{foreach $terms as $term}
							<option value="{$term['id']}">{$term['name']}</option>
						{/foreach}
					</select>
				</div>
			</div>
			
			<div class="form-group">
				<label for="prefix" class="control-label col-sm-2">SIS ID prefix</label>
				<div class="col-sm-2">
					<input type="text" id="prefix" name="prefix" class="form-control " placeholder="2015-2016" />
				</div>
			</div>
			
			<div class="form-group">
				<label for="suffix" class="control-label col-sm-2">SIS ID suffix</label>
				<div class="col-sm-2">
					<input type="text" id="suffix" name="suffix" class="form-control" placeholder="abracadabra" />
				</div>
			</div>
		
			<div class="form-group">
				<div class="checkbox col-sm-offset-2 col-sm-2">
					<label for="unique" class="control-label">
						<input type="checkbox" id="unique" name="unique" value="true"> Unique SIS IDs</label>
				</div>
			</div>
			
			<div class="form-group">
				<div class="col-sm-offset-2 col-sm-10">
					<button type="submit" class="btn btn-default">Create</button>
				</div>	
			</div>
		
		{/block}

	</form>
</div>