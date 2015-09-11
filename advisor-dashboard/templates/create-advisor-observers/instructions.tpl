{extends file="subpage.tpl"}

{block name="subcontent"}

	<div class="container">
		<div class="readable-width">
			<p>Generate a matching observer user for every student in every course in a given account and term (presumably the current term and the Advisory Groups account, but hey&hellip; go wild!). Passwords can optionally be reset (and will be cached in MySQL for future recovery and presentation as part of the Advisor Dashboard).</p>
		</div>
		<div class="alert alert-info">
			<p>Existing advisor-observers can only be updated if the <strong>Users can delete their institution-assigned email address</strong> preference is enabled in account settings.</p>
		</div>
	</div>

	{include file="create-advisor-observers/form.tpl"}

{/block}