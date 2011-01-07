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

?>
<html>
<head>
<title>My portal</title>

<script type="text/javascript" src="<?php echo ULTEO_OVD_WEBCLIENT_URL; ?>/media/script/external.js" charset="utf-8"></script>


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
			<td><img src="<?php echo getIconURL($app['id']); ?>"/></td>
			<td><?php echo $app['name']; ?></td>
			<td><form onsubmit="UlteoOVD_start_Application('<?php echo ULTEO_OVD_WEBCLIENT_URL; ?>', '<?php echo $app['id']; ?>'); return false;">
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
		<tr><td>toto.txt</td>
		<td>
		<form action="<?php echo $SM_URL; ?>/startsession.php" method="POST" onsubmit="return popupOpen(this); return false;">
		<input type="hidden" name="client" value="browser" />
		<input type="hidden" name="session_mode" value="external" />
		<input type="hidden" name="start_app" value="30" />
		<input type="hidden" name="start_app_args" value="toto.txt" />

		<input type="hidden" name="token" value="external" />

		<input type="submit" value="Open file with Mousepad" />
		</form>
		</td>
		<td>
			<form action="<?php echo $SM_URL; ?>/startsession.php" method="POST" onsubmit="return popupOpen(this);">
			<input type="hidden" name="client" value="browser" />
			<input type="hidden" name="session_mode" value="external" />
			<input type="hidden" name="start_app" value="23" />
			<input type="hidden" name="start_app_args" value="toto.txt" />

			<input type="hidden" name="token" value="external" />

			<input type="submit" value="Open file with OOWriter" />
			</form>
		</td>
		</tr>
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
