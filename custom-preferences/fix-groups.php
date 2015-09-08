<?php

require_once('common.inc.php');

$response = $customPrefs->query("
	SELECT *
		FROM `users`
		WHERE
			`groups` IS NOT NULL
");
while ($user = $response->fetch_assoc()) {
	foreach (unserialize($user['groups']) as $group) {
		$customPrefs->query("
			INSERT
				INTO `group-memberships`
				(`user`, `group`)
				VALUES
				('{$user['id']}', '{$group}')
		");
	}
}
	
?>