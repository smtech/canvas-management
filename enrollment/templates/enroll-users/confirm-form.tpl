{extends file="form.tpl"}

{block name="form-content"}

	<div class="panel panel-default">
		<div class="panel-heading">
			<h3 class="panel-title">Course</h3>
		</div>
		<div class="panel-body>">
			<div class="form-group">
				<label for="course" class="control-label col-sm-{$formLabelWidth}">Course</label>
				<div class="col-sm-6">
					<select id="course" name="course" class="form-control">
						<option value="" disabled="disabled" selected="selected">Choose the desired course</option>
						<option disabled="disabled"></option>
						{foreach $sections as $section}
							{if empty($sections['section'])}
								<optgroup label="{$section['course']['name']}">
									<option disabled="disabled">{if !empty($section['course']['sis_course_id'])}{$section['course']['sis_course_id']}{else}No Course SIS ID{/if}</option>
									<option value="{$section['course']['id']}">{$section['course']['name']} (Default Section)</option>
								</optgroup>
							{/if}
						{/foreach}
					</select>
				</div>
			</div>
			<div class="form-group">
				<label for="section" class="control-label col-sm-{$formLabelWidth}">Section</label>
				<div class="col-sm-6">
					<select id="section" name="section" class="form-control">
						<option value="" disabled="disabled" selected="selected">Choose the desired course</option>
						<option disabled="disabled"></option>
						{foreach $sections as $section}
							{if !empty($section['section'])}
								<optgroup label="{$section['course']['name']}">
									<option disabled="disabled">{if !empty($section['course']['sis_course_id'])}{$section['course']['sis_course_id']}{else}No Course SIS ID{/if}</option>
									<option value="{$section['section']['id']}">{$section['section']['name']}</option>
									<option disabled="disabled">{if !empty($section['section']['sis_section_id'])}{$section['section']['sis_section_id']}{else}No Section SIS ID{/if}</option>
									<option disabled="disabled">{$terms[$section['course']['enrollment_term_id']]['name']}</option>
								</optgroup>
							{/if}
						{/foreach}
					</select>
					<p class="help-block">If selected, will take precedence over the course selection.</p>
				</div>
			</div>
		</div>
	</div>

	<div class="panel panel-default">
		<div class="panel-heading">
			<h3 class="panel-title">Users</h3>
		</div>
		{counter start=0 assign="i" print=false}
		{foreach $confirm as $term => $search}
			<div class="form-group">
				<label for="user-{$i}-name" class="control-label col-sm-{$formLabelWidth}">{$term}</label>
				<input type="hidden" name="users[{$i}][term]" value="{$term}" />
				<div class="col-sm-3">
					<select id="user-{$i}-name" name="users[{$i}][id]" class="form-control selectpicker">
						{foreach $search as $user}
							<option value="{$user['id']}">{$user['name']}</option>
						{/foreach}
					</select>
				</div>
				<label for="user-{$i}-role" class="sr-only">Role</label>
				<div class="col-sm-{$formLabelWidth}">
					<select id="user-{$i}-role" name="users[{$i}][role]" class="form-control selectpicker">
						{foreach $roles as $r}
							<option value="{$r['id']}" {if $r['id'] == $role}selected="selected"{/if}>{$r['label']}</option>
						{/foreach}
					</select>
				</div>
				<div class="col-sm-3 checkbox">
					<label for="user-{$i}-notify" class="control-label">
						<input type="checkbox" id="user-{$i}-notify" name="users[{$i}][notify]" value="true" /> Send notification
					</label>
				</div>
			</div>
			{counter assign="i" print=false}
		{/foreach}
	</div>

	{assign var="formButton" value="Enroll"}

{/block}