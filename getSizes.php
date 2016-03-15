<?php

require_once(dirname(__FILE__) . '/config.php');

$token = getToken();
$requestURI = '/common/v1/sizes';

if ($token)
        print_r(doCall($requestURI, $token));

?>

