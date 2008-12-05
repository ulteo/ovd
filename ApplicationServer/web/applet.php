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

$vncpass = get_from_file(SESSION_PATH.'/'.$session.'/hexavncpasswd');
$sshuser = get_from_file(SESSION_PATH.'/'.$session.'/sshuser');
$sshpass = get_from_file(SESSION_PATH.'/'.$session.'/hexasshpasswd');
$rfbport = get_from_file(SESSION_PATH.'/'.$session.'/rfbport');

if ($_SESSION['quality'] == 2)
	$eight_bits = 'yes';
else
	$eight_bits = 'no';

switch ($_SESSION['quality']) {
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
?>
<applet width="<?php echo $_SESSION['width']; ?>" height="<?php echo $_SESSION['height']; ?>">
	<param name="name" value="ulteoapplet" />
	<param name="code" value="org.vnc.VncViewer" />
	<param name="codebase" value="<?php echo SESSIONMANAGER_URL; ?>/applet/" />
	<param name="archive" value="ulteo-applet-0.2.3e.jar" />
	<param name="cache_archive" value="ulteo-applet-0.2.3e.jar" />
	<param name="cache_archive_ex" value="ulteo-applet-0.2.3e.jar;preload" />

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
	<param name="Encoding" value="Auto" />

	<?php
		//if (isset($_SESSION['enable_proxy']) && $_SESSION['enable_proxy'] == 1)  {
	?>
	<param name="proxyType" value="<?php if (isset($_SESSION['proxy_type'])) echo $_SESSION['proxy_type']; ?>" />
	<param name="proxyHost" value="<?php if (isset($_SESSION['proxy_host'])) echo $_SESSION['proxy_host']; ?>" />
	<param name="proxyPort" value="<?php if (isset($_SESSION['proxy_port'])) echo $_SESSION['proxy_port']; ?>" />
	<param name="proxyUsername" value="<?php if (isset($_SESSION['proxy_username'])) echo $_SESSION['proxy_username']; ?>" />
	<param name="proxyPassword" value="<?php if (isset($_SESSION['proxy_password'])) echo $_SESSION['proxy_password']; ?>" />
	<?php
		//}
	?>

	<param name="Share desktop" value="<?php echo $_SESSION['share_desktop']; ?>" />
	<param name="View only" value="<?php echo $_SESSION['view_only']; ?>" />
</applet>
