<?php

$sourceUrl = parse_url($_REQUEST['source_url'], PHP_URL_PATH);
$destinationUrl = parse_url($_REQUEST['destination_url'], PHP_URL_PATH);

define ('TOOL_NAME', "Transfer Outcomes from $sourceUrl to $destinationUrl");
require_once(__DIR__ . '/../.ignore.stmarksschool-test-authentication.inc.php');
require_once('config.inc.php');

debugFlag('START');

$lookupApi = new CanvasApiProcess(CANVAS_API_URL, CANVAS_API_TOKEN);
$sourceApi = new CanvasApiProcess(CANVAS_API_URL, CANVAS_API_TOKEN);
$destinationApi = new CanvasApiProcess(CANVAS_API_URL, CANVAS_API_TOKEN);
$destinationRootOutcomeGroup = $destinationApi->get("$destinationUrl/root_outcome_group");
debugFlag(print_r($destinationRootOutcomeGroup, false));

/* create some empty tables to store outcome groups we're working with */
$outcomeGroupConversionTable = array();
$awaitingParents = array();

/* walk through all the source outcome groups and create matching destination outcome groups */
$outcomeGroups = $sourceApi->get("$sourceUrl/outcome_groups");
do {
	foreach($outcomeGroups as $outcomeGroup) {
		/* create the matching outcome group */
		$destinationOutComeGroup = $destinationApi->post(
			"$destinationUrl/outcomes/{$destinationRootOutcomeGroup['id']}/subgroups",
			array(
				'title' => $outcomeGroup['title'],
				'description' => $outcomeGroup['description'],
				'vendor_guid' => $outcomeGroup['vendor_guid']
			)
		);
		
		/* make a note of which new outcome group is equivalent to which old outcome group */
		$outcomeGroupConversionTable[$outcomeGroup['id']] = $destinationOutComeGroup['id'];
		
		/* flag any outcome groups that need to be re-ordered in the hierarchy later */
		if ($outcomeGroup['parent_outcome_group']['id'] != $sourceRoot) {
			$awaitingParents[] = $destinationOutComeGroup;
		}
		
		/* copy over the outcomes from this group */
		$outcomeLinks = $sourceApi->get("$sourceUrl/outcome_groups/{$outcomeGroup['id']}/outcomes");
		do {
			foreach ($outcomeLinks as $outcomeLink) {
				$outcome = $lookupApi->get($outcomeLink['url']);
				unset($outcome['id']);
				$destinationOutcome = $destinationApi->post("$destinationUrl/outcome_groups/{$outcomeGroup['id']}/outcomes", $outcome);
			}
		} while ($outcomeLinks = $sourceApi->nextPage());
	}
} while ($outcomeGroups = $sourceApi->nextPage());

foreach($awaitingParents as $outcomeGroup) {
	$destinationApi->put("$destinationUrl/outcome_groups/{$outcomeGroup['id']}", array('parent_outcome_group_id' => $outcomeGroupConversionTable[$outcomeGroup['parent_outcome_group']['id']]));
}

debugFlag('FINISH');

?>