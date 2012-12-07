<?php
define('LOGIN', 'admin');
define('PASSWORD', 'admin');
define('SM_HOST', '127.0.0.1');
define('WSDL_PATH', 'ovd_admin.wsdl');

try {
	$service = new SoapClient(WSDL_PATH, array(
		'login' => LOGIN,
		'password' => PASSWORD,
		'location' => 'https://'.SM_HOST.'/ovd/service/admin',
	));
}
catch (Exception $e) {
	  die($e);
}

$r = $service->publication_add('static_2', '2');
var_dump($r);
die();
