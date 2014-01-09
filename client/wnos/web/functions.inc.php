<?php
/**
 * Copyright (C) 2012 Ulteo SAS
 * http://www.ulteo.com
 * Author David PHAM-VAN <d.pham-van@ulteo.com> 2012
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; version 2
 * of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 **/

function add($str) {
	print($str . "\n");
}

function error($msg) {
	add($msg);
	flush();
	sleep(4);
	add('Result=' . $msg);
	die();
}

function query_sm($url, $xml, &$cookies) {
	$socket = curl_init($url);
	curl_setopt($socket, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($socket, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($socket, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($socket, CURLOPT_CONNECTTIMEOUT, 10);
	curl_setopt($socket, CURLOPT_TIMEOUT, (10+5));
	curl_setopt($socket, CURLOPT_FILETIME, true);
	
	foreach($cookies as $k => $v)
		curl_setopt($socket, CURLOPT_COOKIE, $k.'='.$v);
	
	curl_setopt($socket, CURLOPT_HEADER, 1);
		curl_setopt($socket, CURLOPT_POSTFIELDS, $xml);
	curl_setopt($socket, CURLOPT_HTTPHEADER, array('Connection: close', 'Content-Type: text/xml'));
	
	$string = curl_exec($socket);
	$http_code = curl_getinfo($socket, CURLINFO_HTTP_CODE);
	$headers_size = curl_getinfo($socket, CURLINFO_HEADER_SIZE);
	curl_close($socket);
	
	$headers = substr($string, 0, $headers_size);
	$body = substr($string, $headers_size);
	
	if (! in_array($http_code, array(200, 302)))
		return false;
	
	preg_match('@Set-Cookie: (.*)=(.*);@', $headers, $matches);
	if (count($matches) == 3)
		$cookies[$matches[1]] = $matches[2];
	
	if ($http_code == 302) {
		preg_match('@Location: (.*)\n@', $headers, $matches);
		if (count($matches) == 2)
			return array($http_code, $matches[1]);
	}
	
	return $body;
}

function perform_sysinit($tr, $sessionmanager_url) {
	add('Result=OK');
	add('AutoSignoff=yes');
	die();
}

function do_login($tr, $sessionmanager_url) {
	$dom = new DomDocument('1.0', 'utf-8');
	$session_node = $dom->createElement('session');
	$session_node->setAttribute('mode', 'desktop');
	$user_node = $dom->createElement('user');
	$user_node->setAttribute('login', $_GET['username']);
	$user_node->setAttribute('password', $_GET['password']);
	$session_node->appendChild($user_node);
	$dom->appendChild($session_node);
	
	$cookies = array();
	$xml = query_sm($sessionmanager_url.'/start', $dom->saveXML(), $cookies);
	if (! $xml) {
		error($tr['unable_to_reach_sm']);
	}

	$dom = new DomDocument('1.0', 'utf-8');
	$buf = @$dom->loadXML($xml);
	if (! $buf) {
		error($tr['internal_error']);
	}

	if (! $dom->hasChildNodes()) {
		error($tr['internal_error']);
	}

	$response_node = $dom->getElementsByTagName('response')->item(0);
	if (! is_null($response_node)) {
		error($tr[$response_node->getAttribute('code')]);
	}

	$session_node = $dom->getElementsByTagName('session');
	if (count($session_node) != 1) {
		error($tr['internal_error']);
	}
	$session_node = $session_node->item(0);
	if (! is_object($session_node)) {
		error($tr['internal_error']);
	}

	$session_mode = $session_node->getAttribute('mode');

	$user_node = $session_node->getElementsByTagName('user');
	if (count($user_node) != 1) {
		error($tr['internal_error']);
	}
	$user_node = $user_node->item(0);
	if (! is_object($user_node)) {
		error($tr['internal_error']);
	}

	$server_nodes = $session_node->getElementsByTagName('server');
	if (count($server_nodes) != 1) {
		error($tr['internal_error']);
	}
	$server_node = $server_nodes->item(0);
	
	$settings_nodes = $session_node->getElementsByTagName('settings');
	if (count($server_nodes) != 1) {
		error($tr['internal_error']);
	}
	$settings_node = $settings_nodes->item(0);
	
	$setting_nodes = $settings_node->getElementsByTagName('setting');
	if (count($server_nodes) != 1) {
		error($tr['internal_error']);
	}
	$settings = array();
	for ($item=0; $item < $setting_nodes->length ; $item++) {
		$setting_node = $setting_nodes->item($item);
		$settings[$setting_node->getAttribute('name')] = $setting_node->getAttribute('value');
	}
	
	$host = $server_node->getAttribute('fqdn');
	if ($server_node->hasAttribute('port'))
		$host .= ':' . $server_node->getAttribute('port');
	
	return array(
		$cookies['PHPSESSID'],
		array(
			'Connect' => 'RDP',
			'Autoconnect' => '1',
			'Command' => 'OvdDesktop',
			'Description' => '"' . $settings['user_displayname'] . '"',
			'Fullscreen' => 'yes',
			'Host' => $host,
			'Mapdisks' => ($settings['redirect_client_drives'] == 'full' ? 'yes' : 'no'),
			'UniSession' => 'yes', //  a connection will launch only once at a time
			'Username' => $server_node->getAttribute('login'),
			'Password' => $server_node->getAttribute('password'),
			'Colors' => ($settings['rdp_bpp'] == '16' ? 'high' : 'true'),
			'Console' => 'no',
			'Disablesound' => ($settings['multimedia'] == '1' ? 'no' : 'yes'),
			'Domainname' => '$DN',
			'LocalCopy' => 'no',
			'NoReducer' => 'no', // Yes/no option to turn off compression
			'RDPAudioRecord' => 'no',
			'Rdp_No_Animation' => ($settings['multimedia'] == '1' ? 'no' : 'yes'),
			'Rdp_No_Dragging' => ($settings['multimedia'] == '1' ? 'no' : 'yes'),
			'Rdp_No_Fontsmoothing' => ($settings['multimedia'] == '1' ? 'no' : 'yes'),
			'Rdp_No_Theme' => ($settings['multimedia'] == '1' ? 'no' : 'yes'),
			'Rdp_No_Wallpaper' => ($settings['multimedia'] == '1' ? 'no' : 'yes'),
			'Smartcards' => ($settings['redirect_smartcards_readers'] == '1' ? 'yes' : 'no'),
			'UnmapClipboard' => 'yes',
			'UnmapPrinters' => ($settings['redirect_client_printers'] == '1' ? 'no' : 'yes'),
			'UnmapSerials' => 'yes',
			'UnmapUSB' => 'yes',
		)
	);
}

function wait_ready($tr, $sessionmanager_url, $cookie) {
	add($tr['wait_aps']);
	flush();

	$count = 20;
	
	while ($count-- > 0) {
		$cookies = array('PHPSESSID' => $cookie);
		$xml = query_sm($sessionmanager_url.'/session_status', "", $cookies);
		if (! $xml) {
			error($tr['unable_to_reach_sm']);
		}
		
		$dom = new DomDocument('1.0', 'utf-8');
		$buf = @$dom->loadXML($xml);
		if (! $buf) {
			error($tr['internal_error']);
		}
		
		if (! $dom->hasChildNodes()) {
			error($tr['internal_error']);
		}
		
		$session_node = $dom->getElementsByTagName('session');
		if (count($session_node) != 1) {
			error($tr['internal_error']);
		}
		$session_node = $session_node->item(0);
		if (! is_object($session_node)) {
			error($tr['internal_error']);
		}
		
		$status = $session_node->getAttribute('status');
		
		if ($status == 'ready')
			return;
		
		sleep(2);
	}
	
	add('Result=' . $tr['session_end_unexpected']);
	die();
}

function perform_signon($tr, $sessionmanager_url) {
	add($tr['loading_ovd']);
	flush();
	
	list($cookie, $connection) = do_login($tr, $sessionmanager_url);
	wait_ready($tr, $sessionmanager_url, $cookie);
	
	add('Result=OK');
	add('Cookie=' . $cookie);
	$conn_string = array();
	foreach($connection as $k => $v) {
		$conn_string[] = $k . '=' . $v;
	}
	add(implode(" \\\n", $conn_string));
	
	die();
}

function perform_signoff($tr, $sessionmanager_url) {
	global $sessionmanager_url;
	
	$dom = new DomDocument('1.0', 'utf-8');
	$logout_node = $dom->createElement('logout');
	$logout_node->setAttribute('mode', 'logout');
	$dom->appendChild($logout_node);

	$xml = $dom->saveXML();
	$cookies = array('PHPSESSID'=>$_GET['cookie']);
	$sm->query_sm($sessionmanager_url.'/logout', $xml, $cookies);
	
	die();
}

function perform_logout($tr, $sessionmanager_url) {
	die();
}
