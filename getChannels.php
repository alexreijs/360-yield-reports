<?php

require_once(dirname(__FILE__) . '/config.php');

$token = getToken();
$requestURI = '/publisher/v1/channels?limit=1&offset=0';

if ($token) {
	print_r(array_keys(doCall($requestURI, $token)['channels'][0]));
	//print_r(doCall($requestURI, $token));
}

?>
