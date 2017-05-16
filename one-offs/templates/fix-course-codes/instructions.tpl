{assign var="__DIR__" value=$smarty.current_dir}
{extends file="subpage.tpl"}

{block name="subcontent"}

	<div class="container">
		<div class="readable-width">
			<p>An older version of the <a href="../courses/archive-and-rename-courses.php">Archive and Rename Courses</a> script did <em>not</em> rename course codes (since it cluttered up the UI to no apparent purpose). Turns out there is a purpose: when you import a rubric, the list of courses shows course codes instead of course names.</p>
            <p>This changes all of the course codes in an account to match the longer course name.</p>
		</div>
	</div>

	{include file="$__DIR__/form.tpl"}

{/block}
