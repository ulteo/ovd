<?php
/**
 * Copyright (C) 2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com>
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
 
require_once('config.inc.php');
require_once('functions.inc.php');

session_start();

if (isset($_REQUEST['login'])) {
	$_SESSION['login'] = $_REQUEST['login'];
}

if (isset($_SESSION['login']))
	$user = $_SESSION['login'];
else
	$user = ULTEO_OVD_DEFAULT_LOGIN;


$apps = getApplications($user);
$files = getFiles();

foreach ($files as $name => $f) {
	$files[$name]['applications'] = array();
	
	foreach($apps as $application) {
		if (in_array($f['mimetype'], $application['mimetypes']))
			$files[$name]['applications'] []= $application;
	}
}

$base_url_file = 'http://'.$_SERVER['HTTP_HOST'].(($_SERVER['SERVER_PORT']==80)?'':$_SERVER['SERVER_PORT']).dirname($_SERVER['REQUEST_URI']).'/file.php';
?>
<html>
<head>
<title>My portal</title>

<script type="text/javascript" src="<?php echo ULTEO_OVD_WEBCLIENT_URL; ?>/media/script/lib/prototype/prototype.js" charset="utf-8"></script>
<script type="text/javascript" src="<?php echo ULTEO_OVD_WEBCLIENT_URL; ?>/media/script/external.js" charset="utf-8"></script>

<script type="text/javascript">
function startApplication(app_id_) {
	var UOVD_startApplication = new UlteoOVD_start_Application('<?php echo ULTEO_OVD_WEBCLIENT_URL; ?>', app_id_);
	//UOVD_startApplication.setAuthPassword('<?php echo $user; ?>', '<?php echo $user; ?>');
	UOVD_startApplication.setAuthToken('<?php echo base64_encode($user); ?>');
	UOVD_startApplication.start();
}

function startApplicationWithPath(app_id_, path_, url_) {
	var UOVD_startApplication = new UlteoOVD_start_Application('<?php echo ULTEO_OVD_WEBCLIENT_URL; ?>', app_id_);
	//UOVD_startApplication.setAuthPassword('<?php echo $user; ?>', '<?php echo $user; ?>');
	UOVD_startApplication.setAuthToken('<?php echo base64_encode($user); ?>');
	UOVD_startApplication.setPathHTTP(url_, path_, null);
	UOVD_startApplication.start();
}
</script>
</head>
<body>
<h1>Welcome to My Portal</h1>

<?php if ($apps !== false) {?>

	<div>
	<h2>Available applications</h2>
	<table>
	<?php
		foreach($apps as $app) {
	?>
		<tr>
			<td><img src="icon.php?id=<?php echo $app['id']; ?>"/></td>
			<td><?php echo $app['name']; ?></td>
			<td><form onsubmit="startApplication('<?php echo $app['id']; ?>'); return false;">
				<input type="submit" value="Start instance" />
			</form></td>
		</tr>
	<?php
		}
	?>
	</table>
	</div>

	<div>
	<h2>Files</h2>
	<table>
<?php
		foreach($files as $f) {
?>
		<tr>
		<td><?php echo $f['name']; ?></td>
		<td><?php echo $f['mimetype']; ?></td>
<?php
			foreach($f['applications'] as $application) {
?>
		<td>
			<form onsubmit="startApplicationWithPath('<?php echo $application['id']; ?>', '<?php echo $f['name']; ?>', '<?php echo urlencode(base64_encode($base_url_file.'?path='.$f['name'])); ?>'); return false;">
				<input type="submit" value="Open with <?php echo $application['name']; ?>" />
			</form>
		</td>
<?php
			}
?>
		</tr>
<?php
		}
?>
	</table>	
	</div>
	
<?php } ?>

<div>
<h2>Ulteo OVD Authentication</h2>
<form action="" method="POST">
<table>
	<tr>
		<td>Login: </td>
		<td><input name="login" value="<?php echo $user; ?>" /></td>
		<td><input type="submit" value="Go" /></td>
	</tr>
</table>
</form>

<?php if (isset($_SESSION['error'])) { ?>
<pre><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></pre>
<?php } ?>
</div>

</body>
</html>
