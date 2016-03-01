<?php

session_start();
date_default_timezone_set('UTC');

require_once(dirname(__FILE__) . '/getToken.php');
require_once(dirname(__FILE__) . '/doCall.php');
require_once(dirname(__FILE__) . '/credentials.php');

$_SESSION['tokenFilePath'] = './token';

?>
