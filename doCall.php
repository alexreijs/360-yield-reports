<?php

function doCall($requestURI, $token) {

	$baseURL = 'http://sandbox.api.360yield.com';

	$callURL = $baseURL . $requestURI;

	$ch = curl_init(); 

	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: ' . $token->token));
	curl_setopt($ch, CURLOPT_URL, $callURL);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	$server_output = curl_exec ($ch);

	curl_close ($ch);

	return json_decode($server_output, true);

}

if (isSet($argv[1])) {

	require_once(dirname(__FILE__) . '/config.php');

	$token = getToken();
	$requestURI = $argv[1];

	if ($token)
		return doCall($requestURI, $token);

}

?>
