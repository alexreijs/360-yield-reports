<?php

require_once(dirname(__FILE__) . '/config.php');

$token = getToken();
//$requestURI = '/publisher/v1/pricing-control-rules';
//$requestURI = '/publisher/v1/pricing-control-rules?limit=5&offset=107';
$requestURI = '/publisher/v1/pricing-control-rules';


if ($token) {

	$fn = 'pricing-rules.csv';
	$fp = fopen($fn, 'w');

	fwrite($fp, implode(',', array(
		'id',
		'status',
		'min_ecpm',
		'is_custom',
		'rule_type',
		'name',
		'advertisers',
		'agencies',
		'buyers',
		'line_item_types',
		'placements',
		'rtb_advertisers',
		'sizes'
	)) . "\n");

	$pricingRules = doCall($requestURI, $token);

	//print_r(array_keys($pricingRules['pricing_control_rules'][0]));
	//print_r($pricingRules['pricing_control_rules']);


	foreach($pricingRules['pricing_control_rules'] as $prIndex => $pricingRule) {

		$rtbAdvertisers = array();
		$buyers = array();

		foreach($pricingRule['rtb_advertisers'] as $rtbId => $rtbAdvertiser) {
			if (!in_array($rtbAdvertiser['rtb_advertiser_name'], $rtbAdvertisers))
				array_push($rtbAdvertisers, $rtbAdvertiser['rtb_advertiser_name']);
		}
		foreach($pricingRule['buyers'] as $buyerId => $buyer) {
			if (!in_array($buyers['buyer_id'], $buyers))
				array_push($buyers, $buyer['buyer_id']);
		}

	        $columns = array(
	                $pricingRule['id'],
	                //'"' . str_replace('"', '""', $pricingRule['name']) . '"',
			$pricingRule['status'],
			$pricingRule['min_ecpm'],
			$pricingRule['is_custom'],
			$pricingRule['rule_type'],
			$pricingRule['name'],
			implode('|', $pricingRule['advertisers']),
			implode('|', $pricingRule['agencies']),
			implode('|', $buyers),
			implode('|', $pricingRule['line_item_types']),
			implode('|', $pricingRule['placements']),
			implode('|', $rtbAdvertisers),
			implode('|', $pricingRule['sizes'])

	        );
	        foreach ($columns as $i => $column) {
	                $line = $column . ($i == count($columns) - 1 ? "\n" : ",");
	                fwrite($fp, $line);
	        }


	}

	fclose($fp);


	printf("Uploading to Google Storage\n");
	$shell = shell_exec("/usr/local/bin/gsutil cp pricing-rules*.csv gs://api-hub-output/360yield-api/pricing-rules/");
	//printf("Deleting downloaded files\n");
	//shell_exec("rm pricing-rules*.csv");


}


?>
