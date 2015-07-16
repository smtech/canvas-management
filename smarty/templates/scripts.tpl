{extends file="page.tpl"}
{block name="content"}
	<dl>
		<dt>Advisors as Observers</dt>
			<dd>Generate (and/or update) the advisor users and post their logins as an unpublished wiki page in the advisor course (the script knows who the advisor and advisees are because there are a bunch of advisory courses in a particular sub-account and term).</dd>
			<dd>
				<form action="scripts/advisors-as-observers.php" method="post">
					<input type="submit" value="Reset Advisor Users" />
				</form>
			</dd>

		
		<dt>Sanity Checks</dt>
			<dd>A variety of sanity checks on user information.</dd>
			<dd>
				<dl>
					<dt>List Teachers of Courses</dt>
						<dd>Lists all of teachers of courses in a particular enrollment_term.</dd>
						<dd>
							<form action="scripts/list-teachers-of-courses.php" method="post">
								<label for="enrollment_term">enrollment_term <input id="enrollment_term" name="enrollment_term" type="text" /></label>
								<input type="submit" value="Generate List" />
							</form>
						</dd>
						
					<dt>List Users Enrolled in Term</dt>
						<dd>Generates a TSV list of all users enrolled in courses/sections in a particular term (GET parameter term_id).</dd>
						<dd>
							<form action="scripts/list-users-enrolled-in-term.php" method="get">
								<label for="term_id">term_id <input id="term_id" name="term_id" type="text" /></label>
								<input type="submit" value="Generate List" />
							</form>
						</dd>
						
					<dt>List Users with Non-Blackbaud SIS ID</dt>
						<dd>Generates a TSV list of all users in the instance whose SIS IDs do not match the general observed pattern of Blackbaud Import IDs (and are, therefore, most likely hand-generated and/or erroneous -- or both!).</dd>
						<dd>
							<form action="scripts/list-users-with-non-blackbaud-sis_id.php" method="post">
								<input type="submit" value="Generate List" />
							</form>
						</dd>
						
					<dt>List Users without SIS ID</dt>
						<dd>Generates a TSV list of all users who do not have SIS IDs (and therefore don't show up in Canvas SIS export reports).</dd>
						<dd>
							<form action="scripts/list-users-without-sis_id.php" method="post">
								<input type="submit" value="Generate List" />
							</form>
						</dd>
				</dl>
			</dd>		
	</dl>
{/block}