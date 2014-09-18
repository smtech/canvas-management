<?php

require_once('.ignore.live-authentication.inc.php');
define ('TOOL_NAME', "Turn Off Advisor Notifications");
require_once('config.inc.php');

debugFlag('START');

$advisorsApi = new CanvasApiProcess(CANVAS_API_URL, CANVAS_API_TOKEN);
$api = new CanvasApiProcess(CANVAS_API_URL, CANVAS_API_TOKEN);

$advisors = $advisorsApi->get('accounts/1/users',
	array(
		'search_term' => 'advisor'
	)
);
do {
	foreach($advisors as $advisor) {
		$communicationChannels = $api->get("users/{$advisor['id']}/communication_channels");
		$notificationPreferences = $api->get("users/{$advisor['id']}/communication_channels/{$communicationChannels[0]['id']}/notification_preferences");
		$newPrefs = array();
		foreach ($notificationPreferences['notification_preferences'] as $pref) {
			if (($pref['frequency'] != 'never') && ($pref['notification'] != 'confirm_sms_communication_channel')) {
				$newPrefs["notification_preferences[{$pref['notification']}][frequency]"] = 'never';
			}
		}
		if (count($newPrefs)) {
			$newPrefs['as_user_id'] = $advisor['id'];
			$api->put("users/self/communication_channels/{$communicationChannels[0]['id']}/notification_preferences", $newPrefs);
		}
	}
} while($advisors = $advisorsApi->nextPage());

?>