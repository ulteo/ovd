<?php
/**
 * Copyright (C) 2008 Ulteo SAS
 * http://www.ulteo.com
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
require_once(dirname(__FILE__).'/includes/core.inc.php');

$server = $_SERVER['SERVER_NAME'];
$session = $_SESSION['session'];

if (!isset($session) || $session == '')
	die('CRITICAL ERROR'); // That's odd !

if (!isset($_SESSION['width']) || !isset($_SESSION['height'])) {
	$_SESSION['width'] = @$_REQUEST['width'];
	$_SESSION['height'] = @$_REQUEST['height'];
}

$vncpass = get_from_file(SESSION_PATH.'/'.$session.'/clients/hexavncpasswd');
$sshuser = get_from_file(SESSION_PATH.'/'.$session.'/clients/ssh_user');
$sshpass = get_from_file(SESSION_PATH.'/'.$session.'/clients/hexasshpasswd');
$rfbport = get_from_file(SESSION_PATH.'/'.$session.'/clients/rfbport');

if ($_SESSION['parameters']['quality'] == 2)
	$eight_bits = 'yes';
else
	$eight_bits = 'no';

switch ($_SESSION['parameters']['quality']) {
	case '2':
		$compress_level = 9;
		$jpeg_quality = 3;
		break;
	case '5':
		$compress_level = 6;
		$jpeg_quality = 5;
		break;
	case '8':
		$compress_level = 3;
		$jpeg_quality = 7;
		break;
	case '9':
		$compress_level = 1;
		$jpeg_quality = 9;
		break;
	default:
		$compress_level = 1;
		$jpeg_quality = 9;
		break;
}

if (isset($_REQUEST['html'])) {
?>
<applet width="<?php echo $_SESSION['width']; ?>" height="<?php echo $_SESSION['height']; ?>">
	<param name="name" value="ulteoapplet" />
	<param name="code" value="org.vnc.VncViewer" />
	<param name="codebase" value="<?php echo $_SESSION['sessionmanager_url']; ?>/applet/" />
	<param name="archive" value="ulteo-applet-0.2.3f.jar" />
	<param name="cache_archive" value="ulteo-applet-0.2.3f.jar" />
	<param name="cache_archive_ex" value="ulteo-applet-0.2.3f.jar;preload" />

	<param name="HOST" value="<?php echo $server; ?>" />
	<param name="PORT" value="<?php echo $rfbport; ?>" />
	<param name="ENCPASSWORD" value="<?php echo $vncpass; ?>" />

	<param name="SSH" value="yes" />
	<param name="ssh.host" value="<?php echo $server; ?>" />
	<param name="ssh.port" value="443,993,995,110,40001" />
	<param name="ssh.user" value="<?php echo $sshuser; ?>" />
	<param name="ssh.password" value="<?php echo $sshpass; ?>" />

	<param name="Compression level" value="<?php echo $compress_level; ?>" />
	<param name="Restricted colors" value="<?php echo $eight_bits; ?>" />
	<param name="JPEG image quality" value="<?php echo $jpeg_quality; ?>" />
	<param name="Encoding" value="Tight" />

	<!-- Caching options -->
	<param name="rfb.cache.enabled" value="true">
	<param name="rfb.cache.ver.major" value="1">
	<param name="rfb.cache.ver.minor" value="0">
	<param name="rfb.cache.size" value="42336000">
	<param name="rfb.cache.alg" value="LRU">
	<param name="rfb.cache.datasize" value="2000000">

	<?php
		//if (isset($_SESSION['enable_proxy']) && $_SESSION['enable_proxy'] == 1)  {
	?>
	<param name="proxyType" value="<?php if (isset($_SESSION['parameters']['proxy_type'])) echo $_SESSION['parameters']['proxy_type']; ?>" />
	<param name="proxyHost" value="<?php if (isset($_SESSION['parameters']['proxy_host'])) echo $_SESSION['parameters']['proxy_host']; ?>" />
	<param name="proxyPort" value="<?php if (isset($_SESSION['parameters']['proxy_port'])) echo $_SESSION['parameters']['proxy_port']; ?>" />
	<param name="proxyUsername" value="<?php if (isset($_SESSION['parameters']['proxy_username'])) echo $_SESSION['parameters']['proxy_username']; ?>" />
	<param name="proxyPassword" value="<?php if (isset($_SESSION['parameters']['proxy_password'])) echo $_SESSION['parameters']['proxy_password']; ?>" />
	<?php
		//}
	?>

	<param name="Share desktop" value="<?php echo $_SESSION['share_desktop']; ?>" />
	<param name="View only" value="<?php echo $_SESSION['parameters']['view_only']; ?>" />
</applet>
<?php
} else {
	header('Content-Type: text/xml; charset=utf-8');

	$dom = new DomDocument();
	$session_node = $dom->createElement('session');
	$dom->appendChild($session_node);

	$ssh_node = $dom->createElement('ssh');
	$ssh_node->setAttribute('host', $server);
	$ssh_node->setAttribute('user', $sshuser);
	$ssh_node->setAttribute('passwd', $sshpass);
	$ports = array(443, 993, 995, 110, 40001);
	foreach ($ports as $port) {
		$port_node = $dom->createElement('port');
		$port_text_node = $dom->createTextNode($port);
		$port_node->appendChild($port_text_node);
		$ssh_node->appendChild($port_node);
	}
	$session_node->appendChild($ssh_node);

	$vnc_node = $dom->createElement('vnc');
	$vnc_node->setAttribute('host', $server);
	$vnc_node->setAttribute('port', $rfbport);
	$vnc_node->setAttribute('passwd', $vncpass);
	$session_node->appendChild($vnc_node);

	$xml = $dom->saveXML();

	echo $xml;

	die();
}
