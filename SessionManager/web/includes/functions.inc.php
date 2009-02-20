<?php
/**
 * Copyright (C) 2008,2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com>
 * Author Laurent CLOUET <laurent@ulteo.com>
 * Author Jeremy DESVAGES <jeremy@ulteo.com>
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
function redirect($url_=NULL) {
	if (is_null($url_))
		$url_ = $_SERVER['HTTP_REFERER'];

	header('Location: '.$url_);
	die();
}

function die_error($error_=false, $file_=NULL, $line_=NULL) {
// 	if (!in_admin() && isset($_SESSION['login']))
// 		unset($_SESSION['login']);

	$file_ = substr(str_replace(SESSIONMANAGER_ROOT, '', $file_), 1);

	Logger::error('main', 'die_error() called with message \''.$error_.'\' in '.$file_.':'.$line_);

	header('HTTP/1.1 500 Internal Server Error');

	if (in_admin())
		header_static(DEFAULT_PAGE_TITLE.' - '._('Error'));
	else
		header_static(DEFAULT_PAGE_TITLE);

	if (in_admin()) {
		echo '<h2 class="centered">'._('Error').'</h2>';
		echo '&nbsp;<p class="msg_error centered">'.$error_.'</p>&nbsp;';
	} else
		echo '&nbsp;<p class="msg_error centered">'.$error_.'</p>&nbsp;';
		//echo '<p class="msg_error centered">'._('The service is not available, please try again later').'</p>';

	footer_static();

	die();
}

function in_admin() {
	$buf = basename(dirname('http://'.$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF']));

	if ($buf == 'admin')
		return true;

	return false;
}

function query_url_no_error($url_) {
	Logger::debug('main', 'HTTP Query: "'.$url_.'"');

	$socket = curl_init($url_);
	curl_setopt($socket, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($socket, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($socket, CURLOPT_CONNECTTIMEOUT, DEFAULT_REQUEST_TIMEOUT);
	curl_setopt($socket, CURLOPT_TIMEOUT, (DEFAULT_REQUEST_TIMEOUT+5));
	$string = curl_exec($socket);
	curl_close($socket);

	Logger::debug('main', 'HTTP returntext: "'.$string.'"');

	return $string;
}

function query_url($url_) {
	Logger::debug('main', 'HTTP query: "'.$url_.'"');

	$socket = curl_init($url_);
	curl_setopt($socket, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($socket, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($socket, CURLOPT_CONNECTTIMEOUT, DEFAULT_REQUEST_TIMEOUT);
	curl_setopt($socket, CURLOPT_TIMEOUT, (DEFAULT_REQUEST_TIMEOUT+5));
	$string = curl_exec($socket);
	$buf = curl_getinfo($socket, CURLINFO_HTTP_CODE);
	curl_close($socket);

	if ($buf != 200) {
		Logger::debug('main', 'HTTP returncode: "'.$buf.'"');
		return false;
	}

	Logger::debug('main', 'HTTP returntext: "'.$string.'"');

	return $string;
}

function check_folder($folder_) {
	if (!is_dir($folder_))
		if (! @mkdir($folder_, 0750))
			return false;

	return true;
}

// Waiting for removal...
/*function check_ip($fqdn_) {
	$prefs = Preferences::getInstance();
	if (! $prefs) {
		return_error();
		die();
	}

	$buf = $prefs->get('general', 'application_server_settings');
	$authorized_fqdn = $buf['authorized_fqdn'];
	$fqdn_private_address = $buf['fqdn_private_address'];
	$disable_fqdn_check = $buf['disable_fqdn_check'];

	$address = $_SERVER['REMOTE_ADDR'];
	$name = $fqdn_;

	$buf = false;
	foreach ($authorized_fqdn as $fqdn) {
		$fqdn = str_replace('*', '.*', str_replace('.', '\.', $fqdn));

		if (preg_match('/'.$fqdn.'/', $name))
			$buf = true;
	}

	if (! $buf)
		return false;

	if (preg_match('/[0-9]{1,3}(\.[0-9]{1,3}){3}/', $name))
		return ($name == $address);

	if ($disable_fqdn_check == 1)
		return true;

	$reverse = @gethostbyaddr($address);
	if (($reverse == $name) || (isset($fqdn_private_address[$name]) && $fqdn_private_address[$name] == $address))
		return true;

	return false;
}*/

function sendamail($to_, $subject_, $message_) {
	require_once('Mail.php');

	$prefs = Preferences::getInstance();
	if (! $prefs)
		die_error('get Preferences failed',__FILE__,__LINE__);

	$title = $prefs->get('general', 'main_title');
	$buf = $prefs->get('general', 'mails_settings');

	$method = $buf['send_type'];
	$from = $buf['send_from'];
	$host = $buf['send_host'];
	$localhost = '['.$_SERVER['SERVER_ADDR'].']';
	$auth = false;
	if ($buf['send_auth'] == '1')
		$auth = true;
	$username = $buf['send_username'];
	$password = $buf['send_password'];

	$to = $to_;
	$subject = $subject_;
	$message = wordwrap($message_, 72);
	$headers = array(
		'From'		=>	$title. '<'.$from.'>',
		'To'			=>	$to,
		'Subject'		=>	$subject,
		'Content-Type'		=>	'text/plain; charset=UTF-8',
		'X-Mailer'	=>	'PHP/'.phpversion()
	);

	$api_mail = Mail::factory(
		$method,
		array (
			'host'		=>	$host,
			'localhost'	=>	$localhost,
			'auth'		=>	$auth,
			'username'	=>	$username,
			'password'	=>	$password
		)
	);

	return $api_mail->send($to, $headers, $message);
}

function send_alert_mail($subject_, $message_) {
	$prefs = Preferences::getInstance();
	if (! $prefs)
		die_error('get Preferences failed',__FILE__,__LINE__);

	$tos = $prefs->get('events', 'mail_to');
	foreach ($tos as $to) {
		sendamail($to, $subject_, $message_);
	}
}

function str2num($str_) {
	// hoho
	$num = 0;
	$str = md5($str_);

	for ($i=0; $i < strlen($str); $i++)
		$num += ($i+1)*ord($str[$i]);

	return $num;
}

function var_dump2($content_) {
	var_dump($content_);
	echo '<hr />';
}

function print_array($data_, $n_=0) {
	if (is_array($data_)) {
		foreach ($data_ as $k => $v) {
			for ($i = 0; $i < $n_; $i++)
				echo '.';
			echo $k.' => { ';
			if (is_array($v)) {
				echo '<br />';
				print_array($v,$n_+4);
				for ($i = 0; $i < $n_; $i++)
					echo '.';
				echo ' }<br />';
			} else
				echo $v.' }<br />';
		}
	} else {
		for ($i = 0; $i < $n_; $i++)
			echo '.';
		echo $data_.'<br>';
	}
}

function is_writable2($filename) {
	return ((is_file($filename) && is_writable($filename)) || (is_dir($filename) && is_writable($filename)) || is_writable(dirname($filename)));
}

function locale2unix($locale_) {
	$locales = array(
		'fr'	=>	'fr_FR',
		'en'	=>	'en_US',
		'de'	=>	'de_DE',
		'es'	=>	'es_ES',
		'pt'	=>	'pt_PT',
		'it'	=>	'it_IT'
	);

	if (!preg_match('/[a-zA-Z-_]/', $locale_))
		$locale_ = $locales['en'];

	if (strlen($locale_) == 2)
		$locale_ = $locales[$locale_];
	elseif (strlen($locale_) == 5)
		$locale_ = substr($locale_, 0, 2).'_'.strtoupper(substr($locale_, -2));

	$locale_ .= '.UTF-8';

	return $locale_;
}

function plugin_error($errno_, $errstr_, $errfile_, $errline_, $errcontext_) {
	Logger::error('plugins', $errstr_.' in '.$errfile_.' line '.$errline_);
}

function return_ok($msg_=false) {
// 	Logger::debug('main', 'return_ok()');

	if ($msg_ !== false)
		Logger::info('main', $msg_);

	header('HTTP/1.1 200 OK');
	return true;
}

function return_error($msg_=false) {
// 	Logger::debug('main', 'return_error()');

	if ($msg_ !== false)
		Logger::error('main', $msg_);

	header('HTTP/1.1 404 Not Found');
	return false;
}

function header_static($title_=false) {
	global $base_url;

	$prefs = Preferences::getInstance();
	if (! $prefs) {
		$title_ = DEFAULT_PAGE_TITLE;
		$logo_url = DEFAULT_LOGO_URL;
	} else {
		$title_ = $prefs->get('general', 'main_title');
		$logo_url = $prefs->get('general', 'logo_url');
	}

echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>';
		if (in_admin())
			echo $title_.' - '._('Administration');
		else
			echo $title_;
		echo '</title>

		';/*<meta http-equiv="Content-Type" content="application/xhtml+xml; charset=utf-8" />*/echo '
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

		<link rel="shortcut icon" type="image/png" href="'.$base_url.'media/image/favicon.ico" />

		<link rel="stylesheet" type="text/css" href="'.$base_url.'media/style/common.css" />
		<link rel="stylesheet" type="text/css" href="'.$base_url.'admin/media/style/common.css" />

		<link rel="stylesheet" type="text/css" href="'.$base_url.'media/script/lib/nifty/niftyCorners.css" />
		<script type="text/javascript" src="'.$base_url.'media/script/lib/nifty/niftyCorners.js" charset="utf-8"></script>
		<script type="text/javascript" charset="utf-8">
			NiftyLoad = function() {
				Nifty(\'div.rounded\');
			}
		</script>

		<script type="text/javascript" src="'.$base_url.'media/script/lib/prototype/prototype.js" charset="utf-8"></script>
		<script type="text/javascript" src="'.$base_url.'media/script/lib/scriptaculous/scriptaculous.js" charset="utf-8"></script>
		<script type="text/javascript" src="'.$base_url.'media/script/lib/scriptaculous/slider.js" charset="utf-8"></script>

		<script type="text/javascript" src="'.$base_url.'media/script/common.js" charset="utf-8"></script>

		<script type="text/javascript" src="'.$base_url.'media/script/sortable.js" charset="utf-8"></script>

		<script type="text/javascript" src="'.$base_url.'admin/media/script/common.js" charset="utf-8"></script>
		<script type="text/javascript" src="'.$base_url.'admin/media/script/ajax/configuration.js" charset="utf-8"></script>
	</head>

	<body>
		<div id="mainWrap">
			<div id="headerWrap" style="border-bottom: 1px solid #ccc;">
				<table style="width: 100%;" border="0" cellspacing="0" cellpadding="0">
					<tr>
						<td style="text-align: left" class="logo">';
							if (isset($logo_url) && $logo_url != '')
								echo '<a href="index.php"><img src="'.$logo_url.'" alt="'.$title_.'" title="'.$title_.'" /></a>';
						echo '</td>
						<td style="text-align: center; width: 100%;" class="title centered">
							<h1 class="centered">';
							if (in_admin())
								echo $title_.' - '._('Administration');
							else
								echo $title_;
							echo '</h1>
						</td>
					</tr>
				</table>
			</div>

			<div class="spacer"></div>

			<div id="pageWrap">';
					if (isset($_GET['error']) && $_GET['error'] != '')
						echo '<p class="msg_error">'.$_GET['error'].'</p><br /><br  />';
}

function footer_static() {
	global $base_url;

echo '		</div>

			<div class="spacer"></div>

			<div id="footerWrap">
				'._('powered by').' <a href="http://www.ulteo.com"><img src="'.$base_url.'media/image/ulteo.png" width="22" height="22" alt="Ulteo" title="Ulteo" /> Ulteo</a>&nbsp;&nbsp;&nbsp;
			</div>
		</div>
	</body>
</html>';
}

function conf_is_valid() {
	if (! defined('SESSIONMANAGER_SPOOL'))
		return _('SESSIONMANAGER_SPOOL is not defined');

	if (! defined('SESSIONMANAGER_LOGS'))
		return _('SESSIONMANAGER_LOGS is not defined');

	if (! defined('SESSIONMANAGER_CONFFILE_SERIALIZED'))
		return _('SESSIONMANAGER_CONFFILE_SERIALIZED is not defined');

	if (! defined('SESSIONMANAGER_ADMIN_LOGIN'))
		return _('SESSIONMANAGER_ADMIN_LOGIN is not defined');

	if (! defined('SESSIONMANAGER_ADMIN_PASSWORD'))
		return _('SESSIONMANAGER_ADMIN_PASSWORD is not defined');

	if (! is_writable2(SESSIONMANAGER_SPOOL))
		return _('SESSIONMANAGER_SPOOL is not writable').' : '.SESSIONMANAGER_SPOOL;

	if (! is_writable2(SESSIONMANAGER_LOGS))
		return _('SESSIONMANAGER_LOGS is not writable').' : '.SESSIONMANAGER_LOGS;

	if (! is_writable2(SESSIONMANAGER_CONFFILE_SERIALIZED))
		return _('SESSIONMANAGER_CONFFILE_SERIALIZED is not writable').' : '.SESSIONMANAGER_CONFFILE_SERIALIZED;

	return true;
}

function pathinfo_filename($path_) {
	if (version_compare(phpversion(), '5.2.0', '<')) {
		$temp = pathinfo($path_);
		if ($temp['extension'])
			$temp['filename'] = substr($temp['basename'], 0, (strlen($temp['basename'])-strlen($temp['extension'])-1));
		return $temp;
	}

	return pathinfo($path_);
}

function get_classes_startwith($start_name) {
	$classes_name = get_declared_classes();

	$ret = array();
	foreach ($classes_name as $name)
		if (substr($name, 0, strlen($start_name)) == $start_name)
			$ret[] = $name;

	return $ret;
}

function str_startswith($string_, $search_) {
	return (substr($string_, 0, strlen($search_)) == $search_);
}

function str_endswith($string_, $search_) {
	return (substr($string_, (strlen($search_)*-1)) == $search_);
}

function gen_string($nc, $st='abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789') {
	$len = strlen($st)-1;

	$ret = '';
	while ($nc-- > 0)
		$ret .= $st{mt_rand(0, $len)};

	return $ret;
}

function popup_error($msg_) {
	if (! isset($_SESSION['errormsg']))
		$_SESSION['errormsg'] = array();

	if (is_array($msg_))
		foreach ($msg_ as $errormsg)
			$_SESSION['errormsg'][] = $errormsg;
	else
		$_SESSION['errormsg'][] = $msg_;

	return true;
}

function get_all_usergroups(){
	Logger::debug('main', 'get_all_usergroups');

	$prefs = Preferences::getInstance();
	if (! $prefs) {
		Logger::error('main', 'get_all_usergroups get Preferences failed');
		return NULL;
	}

	$mods_enable = $prefs->get('general','module_enable');
	if (! is_array($mods_enable) || ! in_array('UserGroupDB', $mods_enable)) {
		Logger::error('main', 'get_all_usergroups Module UserGroupDB must be enabled');
		return NULL;
	}

	$mod_usergroup_name = 'UserGroupDB_'.$prefs->get('UserGroupDB','enable');
	$UserGroupDB = new $mod_usergroup_name();

	return $UserGroupDB->getList();
}

function get_needed_attributes_user_from_module_plugin() {
	$prefs = Preferences::getInstance();
	if (! $prefs)
		die_error('get Preferences failed',__FILE__,__LINE__);
	$plugs = new Plugins();
	$plugins = $plugs->getAvailablePlugins();
	$plugins['plugin_enable'] = $plugins['plugins'];
	unset($plugins['plugins']);
	$attributes = array();
	foreach ($plugins as $k1 => $v1) {
		$plugins_enable = $prefs->get('plugins', $k1);
		foreach ($v1 as $k2 => $v2) {
			if (isset($v2['UserDB']) && ($k2 == $plugins_enable))
				$attributes = array_merge($attributes,$v2['UserDB']);
		}
	}
	return $attributes;
}

function user_cmp($o1, $o2) {
	return strcmp($o1->getAttribute('login'), $o2->getAttribute('login'));
}

function application_cmp($o1, $o2) {
	return strcmp($o1->getAttribute('name'), $o2->getAttribute('name'));
}
