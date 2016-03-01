<?php

require_once(dirname(__FILE__) . '/config.php');

$token = getToken();
$requestURI = '/publisher/v1/pricing-control-rules?limit=10&offset=100';

if ($token)
        echo doCall($requestURI, $token);

?>
