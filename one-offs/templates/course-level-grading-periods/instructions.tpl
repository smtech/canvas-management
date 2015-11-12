{extends file="subpage.tpl"}

{block name="subcontent"}

	<div class="container">
		<div class="readable-width">
			<p>Assign course-level grading periods to all courses in a particular account and term. This is (currently) preferable to account-level grading periods because teachers are able to edit grades in past grading periods if they are defined at the course-level, but are locked out of editing past grading periods if they are defined at the account-level.</p>
		</div>
	</div>
	
	{include file="course-level-grading-periods/form.tpl"}

{/block}