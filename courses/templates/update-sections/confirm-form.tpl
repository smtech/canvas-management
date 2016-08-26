{extends file="form.tpl"}

{block name="form-content"}

<table class="table table-bordered table-hover table-striped">
	<thead>
		<tr>
			{foreach $fields as $field}
				<th>{$field}</th>
			{/foreach}
			<th>Include</th>
		</tr>
	</thead>
	<tbody>
		{foreach $sections as $i => $section}
			<tr>
				{foreach $fields as $field}
					<td{if $formHidden['ignore_course_id'] && $field == 'course_id'} style="color: gray; text-decoration: line-through;"{/if}>
						{$section[$field]}
						<input name="sections[{$i}][{$field}]" value="{$section[$field]}" type="hidden" />
					</td>
				{/foreach}
				<td>
					<div class="checkbox">
						<label for="section-{$i}-include">
							<input id="section-{$i}-include" type="checkbox" value="include" name="sections[{$i}][batch-include]" checked="checked" />
							Include</label>
					</div>
				</td>
			</tr>
		{/foreach}
	</tbody>
</table>

{assign var="formButton" value="Update"}

{/block}
