{assign var="__DIR__" value=$smarty.current_dir}
{extends file="subpage.tpl"}

{block name="subcontent"}

	<div class="container">
		<div class="readable-width">
			<p>Align canvas section names and SIS IDs with course information.</p>
			
			<p>Why? This allows us to sync enrollments into sections only, which allows us to <code>xlist</code> sections into container courses for teachers who like that&hellip; and not for those who don't.)</p>
		</div>
	</div>
	
	{assign var="formFileUpload" value="true"}
	{include file="$__DIR__/form.tpl"}

{/block}