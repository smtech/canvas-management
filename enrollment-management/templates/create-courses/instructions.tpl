{extends file="subpage.tpl"}

{block name="subcontent"}

<div class="container">
	<div class="readable-width">
		<p>Add courses with a bit more power than usual.</p>
		<p><strong>Caveat emptor:</strong> Courses are created with automatically-generated, intentionally-unique SIS IDs, which means that you can <i>quickly</i> create <i>a lot</i> of courses with the same name if you're not careful.</p>
	</div>
	
	{include file="create-courses/form.tpl"}
</div>

{/block}