<?php

require_once(dirname(__FILE__) . '/config.php');

$token = getToken();
$requestURI = '/publisher/v1/sites/zones/placements/63453';

if ($token)
	print_r(doCall($requestURI, $token));

?>
