<?php

require_once(dirname(__FILE__) . '/config.php');
$_SESSION['token'] = getToken($verbose = false);

if (!file_exists('./data/'))
	mkdir('./data');

if ($_SESSION['token']) {

	cache360YieldObject('advertises', '/publisher/v1/advertisers');
	cache360YieldObject('agencies', '/publisher/v1/agencies');
	cache360YieldObject('channels', '/publisher/v1/channels');
	cache360YieldObject('sites', '/publisher/v1/sites');
	cache360YieldObject('sizes', '/common/v1/sizes');
	cache360YieldObject('subpublishers', '/publisher/v1/subpublishers');

	print("Loading existing placements from disk..\n");
	$placementsFile = './data/placements.json';
	$placements = json_decode(file_exists($placementsFile) ? file_get_contents($placementsFile) : '{}');

	print("Loading existing RTB advertisers from disk..\n");
	$rtbAdvertisersFile = './data/rtb-advertisers.json';
	$rtbAdvertisers = json_decode(file_exists($rtbAdvertisersFile) ? file_get_contents($rtbAdvertisersFile) : '[]');



	print("Getting pricing rules and looping over them..\n");
	$pricingRules = doCall('/publisher/v1/pricing-control-rules?limit=1&offset=101', $_SESSION['token']);

	//print_r($pricingRules);

	foreach($pricingRules->pricing_control_rules as $pricingRuleIndex => $pricingRule) {
		foreach ($pricingRule->placements as $placementIndex => $placement) {
			if (!isSet($placements->{$placement}))
				$placements->{$placement} = doCall('/publisher/v1/sites/zones/placements/' . $placement, $_SESSION['token']);
		}

		foreach($pricingRule->rtb_advertisers as $rtbAdvertisersIndex => $rtbAdvertiser) {
			if(!in_array($rtbAdvertiser->rtb_advertiser_name, $rtbAdvertisers))
				array_push($rtbAdvertisers, $rtbAdvertiser->rtb_advertiser_name);
		}
	}

	saveObjectToDisk($placements, 'placements');
	saveObjectToDisk($rtbAdvertisers, 'rtb-advertisers');
}


function saveObjectToDisk($object, $name) {
	$file = fopen('./data/' . $name . '.json', 'w');
	fwrite($file, json_encode($object));
	fclose($file);
}

function cache360YieldObject($objectName, $apiURL) {

	$objectFileName = './data/' . $objectName . '.json';
	if (file_exists($objectFileName))
		print("Loading existing " . $objectName . " from disk..\n");
	else {
	        print("Getting " . $objectName . " and saving to disk..\n");
	        $object = doCall($apiURL, $_SESSION['token']);
	        saveObjectToDisk($object, $objectName);
	}
}

?>
