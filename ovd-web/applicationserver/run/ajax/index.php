<?php
/**
 * Copyright (C) 2008 Ulteo SAS
 * http://www.ulteo.com
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
error_reporting(E_ERROR | E_PARSE);

$ssid = $_REQUEST['s'];
$id = $_REQUEST['id'];
$view_desktop = $_REQUEST['view'];
$quality = $_REQUEST['quality'];
$user_unique_id = $_REQUEST['ui'];
$user_file_server = $_REQUEST['uf'];
$system_code = $_REQUEST['sc'];
$user_nickname = $_REQUEST['un'];
$unix_uid = $_REQUEST['uu'];
$ex_timeout = $_REQUEST['ex'];

$prxnm = $_REQUEST['prxnm'];
$prxpt = $_REQUEST['prxpt'];
$prxun = $_REQUEST['prxun'];
$prxps = $_REQUEST['prxps'];
$runner = $_REQUEST['runner'];
$lss = $_REQUEST['lss'];
$app = $_REQUEST['app'];
$doc = $_REQUEST['doc'];

$os = getenv ("HTTP_USER_AGENT");
$encod = '';
if (strpos($os, 'Mac OS X') !== false) {
        if ((!strpos($os, 'Safari') !== false) && (strpos($os, 'Gecko')!== false)){
                //Mac Gecko browsers don't handle well Tight encoding
                $encod = "Hextile";
        } else {
                // Safari
                $encod = "Auto";
        }

} else {
        //other OSes should use Tight Encoding
        $encod = "Auto";
}


$w = 1024;
$h = 768;

require_once(dirname(__FILE__).'/../../includes/session_path.inc.php');
//define("SESSION_PATH","/home/vulteo/flat/root/vulteo/var/ulteo-sessions/");
define("MAXTIME",180);

$ssid = $_REQUEST['s'];

if (!$ssid) {
	echo 'NO SESSION - EXIT';
	exit();
}

$id = $_REQUEST['id'];

//$share_desktop = $_REQUEST['share'];
$share_desktop = "true";
$view_desktop = $_REQUEST['view'];
$quality = $_REQUEST['quality'];
$user_unique_id = $_REQUEST['ui'];
$user_file_server = $_REQUEST['uf'];
$system_code = $_REQUEST['sc'];

$compresslevel=9;

if ($quality == 2) {
	 $jpegquality="3";
	 $eightbits="yes";
	} else {
	 $eightbits="no";
	}

if ($quality == 9) $compresslevel="2";

if ($quality == 8) $compresslevel="7";

$VNCPASS=file_get_contents(SESSION_PATH."/".$ssid."/"."hexavncpasswd");
$SSHUSER=file_get_contents(SESSION_PATH."/".$ssid."/"."sshuser");
$SSHPASS=file_get_contents(SESSION_PATH."/".$ssid."/"."hexasshpasswd");
$RFBPORT=file_get_contents(SESSION_PATH."/".$ssid."/"."rfbport");
$PORT=(int)trim($RFBPORT)-5900;

if (!$VNCPASS or !$SSHPASS or !$RFBPORT) {
	echo "ERROR - SESSION FILE NOT FOUND";
	exit();
}

// we tell the main daemon that it should start
// a desktop session

// check last time a session was launched and wait a little time
// if too close

$current_date_stamp = (int)date("U");

$date_stamp = 0;

$f = fopen("/tmp/timestamp", "r");
if ($f) {
	$date_stamp = (int)fgets($f, 4096);
	fclose($f);
}

$f = fopen("/tmp/timestamp", "w");

if ($f) {
	fputs($f, $current_date_stamp);
	fclose($f);

	$diff = $current_date_stamp - $date_stamp;

	if (($diff > 0) && ($diff < 15)) {
		sleep (15 - $diff);
		}
	}

function setOption($file,$value) {
	global $ssid;
	$f = fopen(SESSION_PATH."/".$ssid."/".$file, "w");
	if ($f) {
		fputs($f, $value);
		fclose($f);
	} else {
		echo "File cannot be open. Exit";
		exit();
	}
}

setOption("u_uid",$user_unique_id);
setOption("fileserver",$user_file_server);
setOption("uu",$unix_uid);

$date_max = date("U") + base64_decode($ex_timeout);
setOption("ex",$date_max);
if (base64_decode($ex_timeout) < 2400) touch(SESSION_PATH."/".$ssid."/FREE");

setOption("locale",$system_code);
setOption("runasap","1");
setOption("geometry",$w."x".$h);
setOption("user",$id);
setOption("nick",$user_nickname);
setOption("app",$app);
setOption("doc",$doc);
setOption("ajax","TRUE");

// wait for runasap == 2 to start the applet
// MAXTIME seconds max

$count = 0;
$res = 0;
while (++$count < MAXTIME && $res!="2" && $res!="3") {

	sleep(2);

	$f = fopen(SESSION_PATH."/".$ssid."/"."runasap", "r");

	if ($f) {
		$res = (int)fgets($f);
//		echo $res."*";
		fclose($f);
	}


}

if ($res!="2" || $count > MAXTIME || $res=="3")  {
?>
	<script>
		mainMessage.show("Session couldn't be started.");
	</script>
<? } else { ?>
	<applet code="org/vnc/VncViewer.class" archive="SSHVncApplet-signed.jar" codebase="http://ulteo.com/main/ajaxwm/libs/" width="<?=$w?>" height="<?=$h?>" name="vnc" id="vnc">
		<param name="archive" value="SSHVncApplet-signed.jar"/>
        <param name="PORT" value="<?=trim($RFBPORT)?>">
        <param name="HOST" value="localhost">
		<param name="SSH" value="yes">
        <param name="ssh.host" value="<?=$runner?>">
        <param name="ssh.user" value="<?=trim($SSHUSER)?>">
        <param name="ssh.password" value="<?=trim($SSHPASS)?>">
        <param name="ssh.port" value="443,993,995,110,40001">
		<param name="ENCPASSWORD" value="<?=trim($VNCPASS)?>">
        <param name="Compression level" value="<?=$compresslevel?>">
        <param name="Restricted colors" value="<?=$eightbits?>">
        <param name="JPEG image quality" value="<?=$jpegquality?>">
        <param name="proxyHost" value="<?=$prxnm?>">
        <param name="proxyPort" value="<?=$prxpt?>">
        <param name="proxyUsername" value="<?=trim($prxun)?>">
        <param name="proxyPassword" value="<?=trim($prxps)?>">
        <param name="Share desktop" value="<?=$share_desktop?>">
        <param name="View only" value="<?=$view_desktop?>">
		<param name="Encoding" value="<?=$encod?>">
		<param name="afterLoad" value="javascript:mainMessage.show('Establishing SSH connections')">
		<param name="afterSSH" value="javascript:mainMessage.show('Connecting to the VNC Server')">
		<param name="afterConnected" value="javascript:vncConnected('<?=$user_file_server?>')">
		<param name="sshError" value="javascript:mainMessage.show('SSH Connection failed')">
	</applet>
	<script>
			mainMessage.show("Loading Applet");
	</script>
<?
}
?>
