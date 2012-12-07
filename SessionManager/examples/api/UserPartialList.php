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

$r = $service->users_list_partial('c', array('login'));
if ($r === null) {
	die($r);
}

echo "NB result: ".count($r['data'])."\n";
foreach($r['data'] as $login => $user) {
	echo ' * '.$user['login'].' => '.$user['displayname']."\n";
}
