{assign var="__DIR__" value=$smarty.current_dir}
{extends file="subpage.tpl"}

{block name="subcontent"}

	<div class="container">
		<div class="readable-width">
			<p>Custom preferences allow you to selectively hide a number of courses from users' course menus. This script will walk through all of the users in a particular account and test their favorites to see if they include these "hidden" courses. If users have not explicitly set their own favorites, then all active courses are implicitly favorites. Implicit favorites cannot be unfavorited. To handle this, the script will explicitly favorite all currently "favorite" courses that are <em>not</em> hidden.</p>
		</div>
	</div>
	
	{include file="$__DIR__/form.tpl"}

{/block}