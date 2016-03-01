<?php

require_once(dirname(__FILE__) . '/config.php');

$token = getToken();
$requestURI = '/publisher/v1/campaigns';

if ($token)
	echo doCall($requestURI, $token);

?>
