{extends file="form.tpl"}

{block name="form-content"}

	{include file="select-account.tpl"}
	
	{include file="select-term.tpl"}
	
	<div class="panel panel-default">
		<div class="panel-heading">
			<h4>Grading Period</h4>
		</div>
		<div class="panel-body">			
			
			<div class="form-group">
				<label for="period-title" class="control-label col-sm-{$formLabelWidth}">Title</label>
				<div class="col-sm-{12 - $formLabelWidth}">
					<input id="period-title" name="period[title]" type="text" class="form-control"  placeholder="Title shown in gradebooks" value="{$period['title']}" />
				</div>
			</div>
			
			<div class="form-group">
				<label for="period-start_date" class="control-label col-sm-{$formLabelWidth}">Start Date</label>
				<div class="col-sm-4">
					<div class="input-group date">
						<input type="text" class="form-control" name="period[start_date]" id="period-start_date" value="{$period['start_date']}" /><span class="input-group-addon"><i class="glyphicon glyphicon-calendar"></i></span>
					</div>
					<p class="help-block">The grading period will start at 12:00am on this date.</p>
				</div>
			</div>

			<div class="form-group">
				<label for="period-end_date" class="control-label col-sm-{$formLabelWidth}">End Date</label>
				<div class="col-sm-4">
					<div class="input-group date">
						<input type="text" class="form-control" name="period[end_date]" id="period-end_date" value="{$period['end_date']}" /><span class="input-group-addon"><i class="glyphicon glyphicon-calendar"></i></span>
					</div>
					<p class="help-block">The grading period will end at 12:00am on this date (so you probably want to pick the day <em>after</em> the grading period ends).</p>
				</div>
			</div>
			
		</div>
	</div>
	
	{assign var="formButton" value="So mote it be"}
	
{/block}