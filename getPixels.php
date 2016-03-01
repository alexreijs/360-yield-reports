<?php

require_once(dirname(__FILE__) . '/config.php');

$token = getToken();
$requestURI = '/publisher/v1/getPixels';

if ($token)
        echo doCall($requestURI, $token);

?>

