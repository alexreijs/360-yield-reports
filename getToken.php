<?php

function getToken($verbose = true) {

	print("Checking for existing token file\n");

	if (file_exists($_SESSION['tokenFilePath'])) {
		$token = getTokenFromDisk();

		if (time() * 1000 < $token->expires) {
			print("Token found and appears to be valid, returning\n");
			return $token;
		}
		else {
			print("Token found, but was expired. Getting new token\n");
			$token = getTokenFromAPI();

			if (checkTokenObject($token)) {
				print("Successfully got new token, saving it to disk\n");
				saveTokenToDisk($token);
				return $token;
			}
			else {
				print("Could not get new token, please check configuration\n");
				return false;
			}
		}
	}
	else if (!file_exists($_SESSION['tokenFilePath'])) {
		print("Existing token file not found, getting new token\n");
		$token = getTokenFromAPI();

		if (checkTokenObject($token)) {
			print("Successfully got first token, saving it to disk\n");
			saveTokenToDisk($token);
			return $token;
		}
		else {
			print("Could not get first token, please check configuration\n");
			return false;
		}
	}
}

function checkTokenObject($token) {
	return (is_object($token) && isSet($token->token));
}


function saveTokenToDisk($token) {
	$fp = fopen($_SESSION['tokenFilePath'], "w+");
	fwrite($fp, json_encode($token));
	fclose($fp);
}

function getTokenFromDisk() {
	$tokenFile = json_decode(file_get_contents($_SESSION['tokenFilePath']));
	return $tokenFile;
}

function getTokenFromAPI() {

	$baseURL = 'http://sandbox.api.360yield.com';
	$requestURI = '/auth/v1/token';
	$timestamp = time();

	$tokenURL = $baseURL . $requestURI;
	$stringToSign = $tokenURL . $timestamp;

	$hmac = hash_hmac("sha1", $stringToSign, $_SESSION['apiSecretKey'], true);
	$signature = base64_encode($hmac);
	$authorization =  $_SESSION['apiAccessKey'] . ':' . $signature;

	$ch = curl_init(); 

	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
	    'Authorization: ' . $authorization,
	    'x-360-timestamp: ' . $timestamp
	));

	curl_setopt($ch, CURLOPT_URL, $tokenURL);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	$server_output = curl_exec ($ch);
	curl_close ($ch);

	return json_decode($server_output);
}

?>
