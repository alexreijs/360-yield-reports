<?php

require_once(dirname(__FILE__) . '/config.php');

$token = getToken();
//$requestURI = '/publisher/v1/pricing-control-rules';
$requestURI = '/publisher/v1/pricing-control-rules?limit=1&offset=100';

if ($token)
        echo doCall($requestURI, $token);

?>
