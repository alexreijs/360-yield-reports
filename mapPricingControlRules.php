<?php

require_once(dirname(__FILE__) . '/config.php');


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

function get360YieldData() {

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
		$placements = json_decode(file_exists($placementsFile) ? file_get_contents($placementsFile) : '{}', true);

		print("Loading existing RTB advertisers from disk..\n");
		$rtbAdvertisersFile = './data/rtb-advertisers.json';
		$rtbAdvertisers = json_decode(file_exists($rtbAdvertisersFile) ? file_get_contents($rtbAdvertisersFile) : '[]', true);



		print("Getting pricing rules and looping over them..\n");
		$pricingRules = doCall('/publisher/v1/pricing-control-rules?limit=1&offset=100', $_SESSION['token']);


		//print_r($pricingRules);

		foreach($pricingRules['pricing_control_rules'] as $pricingRuleIndex => $pricingRule) {
			foreach ($pricingRule['placements'] as $placementIndex => $placementId) {
				if (!isSet($placements[$placementId])) {
					print("Getting placement ID: " . $placementId. "..\n");
					$placements[$placementId] = doCall('/publisher/v1/sites/zones/placements/' . $placementId, $_SESSION['token']);
				}
			}

			if(isSet($pricingRule['rtb_advertisers'])) {
				foreach($pricingRule['rtb_advertisers'] as $rtbAdvertisersIndex => $rtbAdvertiser) {
					if(!in_array($rtbAdvertiser['rtb_advertiser_name'], $rtbAdvertisers))
						array_push($rtbAdvertisers, $rtbAdvertiser['rtb_advertiser_name']);
				}
			}
		}

		saveObjectToDisk($pricingRules, 'pricing-rules');
		saveObjectToDisk($placements, 'placements');
		saveObjectToDisk($rtbAdvertisers, 'rtb-advertisers');
	}

	print("Done!\n");
}

function prepareCypherStatements() {

	$statements = [];

	$pricingRules = json_decode(file_get_contents('./data/pricing-rules.json'), true)['pricing_control_rules'];

	$yieldObjects = array(
		'pricingRules' => array(
			'name' => 'PricingRule',
			'allData' => $pricingRules,
			'statementData' => $pricingRules,
			'fields' => array('id', 'min_ecpm', 'name', 'is_custom')
		),
		'sizes' => array(
			'name' => 'Size',
			'allData' => json_decode(file_get_contents('./data/sizes.json'), true)['sizes'],
			'statementData' => [],
			'fields' => array('id', 'name', 'type')
		),
		'placements' => array(
			'name' => 'Placement',
			'allData' => json_decode(file_get_contents('./data/placements.json'), true),
			'statementData' => [],
			'fields' => array('id', 'name')
		),
		'sites' => array(
			'name' => 'Site',
			'allData' => json_decode(file_get_contents('./data/sites.json'), true)['sites'],
			'statementData' => [],
			'fields' => array('id', 'name', 'url')
		)
	);

	foreach ($pricingRules as $pricingRulesIndex => $pricingRule) {
		foreach($yieldObjects as $yieldObjectsIndex => $yieldObject) {
			if (isSet($pricingRule[$yieldObjectsIndex]) && count($yieldObject['statementData']) == 0) {
				foreach ($pricingRule[$yieldObjectsIndex] as $ruleObjectIndex => $ruleObject) {
						$yieldObjects[$yieldObjectsIndex]['statementData'][$ruleObject] = $yieldObjects[$yieldObjectsIndex]['allData'][$ruleObject];
				}
			}
		}
	}

	foreach($yieldObjects as $objectArrayIndex => $objectArray) {
		foreach($objectArray['statementData'] as $objectDataIndex => $objectData) {
			$fields = [];
			foreach ($objectArray['fields'] as $objectFieldsIndex => $objectField) {
				if (isSet($objectData[$objectField])) {
					$quote = is_numeric($objectData[$objectField]) ? '' : '"';
					array_push($fields, $objectField . ' : ' . $quote . $objectData[$objectField] . $quote);
				}
				else
					print("Could not find index " . $objectField . " for object: " . $objectDataIndex . "\n");
			}

			array_push($statements, "CREATE (" . $objectArrayIndex . "_" .  $objectData['id'] . ":" . $objectArray['name'] . " { " . implode(', ', $fields) . " })");
		}
	}

	print_r($pricingRules);

        foreach ($pricingRules as $pricingRulesIndex => $pricingRule) {
		foreach($yieldObjects as $yieldObjectsIndex => $yieldObject) {
			$yieldObjectData = $yieldObject['statementData'];
			if(isSet($pricingRule[$yieldObjectsIndex])) {
				foreach ($pricingRule[$yieldObjectsIndex] as $pricingRuleObjectsIndex => $pricingRuleObject) {
					array_push($statements, "\tCREATE (pricingRules_" . $pricingRule['id'] . ")-[:APPLIES_TO]->(" . $yieldObjectsIndex . "_" . $pricingRuleObject . ")");
				}
			}
		}
	}
	return $statements;
}


//get360YieldData();
$statements = prepareCypherStatements();

//echo implode("\n", $statements) . "\n";

?>
