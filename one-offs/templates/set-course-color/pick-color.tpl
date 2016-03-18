{extends file="form.tpl"}

{block name="form-content"}

	<div class="form-group">
		<label for="course" class="control-label col-sm-{$formLabelWidth}">Course</label>
		<span class="col-sm-4">
			<select id="course" name="course" class="form-control">
				{foreach $courses as $course}
					<option value="{$course['id']}">{$course['name']}</option>
				{/foreach}
			</select>
		</span>
	</div>
	
	<div class="form-group">
		<label for="color" class="control-label col-sm-{$formLabelWidth}">Color</label>
		<div class="col-sm-4">
			<div class="input-group color">
				<input type="text" id="color" name="color" class="form-control" placeholder="Pick a color" />
				<span class="input-group-addon"><i></i></span>
			</div>
		</div>
	</div>
	
	{assign var="formButton" value="Set Color"}

{/block}