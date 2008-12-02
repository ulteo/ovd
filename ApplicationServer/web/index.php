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

if (!isset($_GET['token']) || $_GET['token'] == '')
	header('Location: '.SESSIONMANAGER_URL);
$token = $_GET['token'];

if (!isset($_SESSION['current_token']) || $_SESSION['current_token'] != $token) {
	session_destroy();
	session_start();
}
$_SESSION['current_token'] = $token;

$xml = query_url(SESSIONMANAGER_URL.'/webservices/session_token.php?fqdn='.$_SERVER['SERVER_NAME'].'&token='.$token);

$dom = new DomDocument();
@$dom->loadXML($xml);

$session_node = $dom->getElementsByTagname('session')->item(0);
if (is_null($session_node))
	die('Missing element \'session\'');

if ($session_node->hasAttribute('id'))
	$_SESSION['session'] = $session_node->getAttribute('id');
if ($session_node->hasAttribute('mode'))
	$_SESSION['mode'] = $session_node->getAttribute('mode');

$settings = array('user_id', 'user_login', 'user_displayname', 'locale', 'quality', 'timeout', 'debug', 'start_app');
if ($_SESSION['mode'] == 'invite')
	$settings[] = 'view_only';
foreach ($settings as $setting) {
	$item = $session_node->getElementsByTagname($setting)->item(0);

	if (is_null($item))
		die('Missing element \''.$setting.'\'');

	$_SESSION[$setting] = $item->getAttribute('value');
}

$settings2 = array('proxy_type', 'proxy_host', 'proxy_port', 'proxy_username', 'proxy_password');
foreach ($settings2 as $setting2) {
	$item2 = @$session_node->getElementsByTagname($setting2)->item(0);

	if (!is_null($item2))
		$_SESSION[$setting2] = $item2->getAttribute('value');
}

$_SESSION['share_desktop'] = 'true';
if ($_SESSION['mode'] == 'start')
	$_SESSION['view_only'] = 'No';

$module_fs_node = $session_node->getElementsByTagname('module_fs')->item(0);
if (is_null($item))
	die('Missing element \'module_fs\'');

$_SESSION['module_fs']['type'] = $module_fs_node->getAttribute('type');

$param_nodes = $module_fs_node->getElementsByTagname('param');
foreach ($param_nodes as $param_node)
	if ($param_node->hasAttribute('key') && $param_node->hasAttribute('value'))
		$_SESSION['module_fs'][$param_node->getAttribute('key')] = $param_node->getAttribute('value');

$menu_node = $session_node->getElementsByTagname('menu')->item(0);
$application_nodes = $menu_node->getElementsByTagname('application');
foreach ($application_nodes as $application_node)
	if ($application_node->hasAttribute('desktopfile'))
		$_SESSION['desktopfile'][md5($application_node->getAttribute('desktopfile'))] = $application_node->getAttribute('desktopfile');

if ($_SESSION['mode'] == 'start')
	@touch(SESSION2CREATE_PATH.'/'.$_SESSION['session']);
?>
<html>
	<head>
		<title>Ulteo Open Virtual Desktop</title>

		<link rel="stylesheet" type="text/css" href="media/style/common.css" />

		<script type="text/javascript" src="media/script/lib/prototype/prototype.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/common.js" charset="utf-8"></script>

		<script type="text/javascript" src="media/script/lib/scriptaculous/scriptaculous.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/lib/scriptaculous/extensions.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/daemon.js" charset="utf-8"></script>

		<script type="text/javascript" charset="utf-8">
			Event.observe(window, 'load', function() {
				daemon_init('<?php echo $_SERVER['SERVER_NAME']; ?>', '<?php echo $_SESSION['session']; ?>', <?php echo time(); ?>, <?php echo ($_SESSION['mode'] == 'start')?'1':'0'; ?>, <?php echo ($_SESSION['debug'] == 1)?'1':'0'; ?>);
			});
		</script>
	</head>

	<body>
<?php
if ($_SESSION['mode'] == 'start') {
?>
		<div id="menuContainer" style="display: none;">
			<a href="javascript:;" onclick="clicMenu('menuShare')"><img src="media/image/share-button.png" width="80" height="18" alt="Share desktop" title="Share desktop" /></a>
			<div id="menuShare" style="display: none;">
				<div id="menuShareClose"><a href="javascript:;" onclick="clicMenu('menuShare')"><img style="margin-right: 10px;" src="media/image/close.png" width="16" height="16" alt="" title="" /></a></div>
				<iframe id="menuShareFrame" src="<?php echo SESSIONMANAGER_URL; ?>/invite.php?server=<?php echo $_SERVER['SERVER_NAME']; ?>&session=<?php echo $_SESSION['session']; ?>" frameborder="0"></iframe>
			</div>
		</div>
<?php
}
?>

		<div id="printerContainer">
		</div>

		<div id="splashContainer">
			<table style="width: 100%" border="0" cellspacing="0" cellpadding="0">
				<tr>
					<td style="text-align: center" colspan="3">
						<img src="media/image/ulteo.png" width="376" height="188" alt="" title="" align="middle" />
					</td>
				</tr>
				<tr>
					<td style="text-align: left; vertical-align: middle">
						<span style="font-size: 1.5em; font-weight: bold; color: #686868">Loading Open Virtual Desktop</span>
					</td>
					<td style="width: 20px"></td>
					<td style="text-align: left; vertical-align: middle">
						<img src="media/image/rotate.gif" width="32" height="32" alt="" title="" align="middle" />
					</td>
				</tr>
			</table>
		</div>

		<div id="debugContainer" class="no_debug info warning error" style="display: none;">
		</div>

		<div id="debugLevels" style="display: none;">
			<span class="debug"><input type="checkbox" id="level_debug" onClick="switchDebug('debug')" value="10" /> Debug</span>
			<span class="info"><input type="checkbox" id="level_info" onClick="switchDebug('info')" value="20" checked="checked" /> Info</span>
			<span class="warning"><input type="checkbox" id="level_warning" onClick="switchDebug('warning')" value="30" checked="checked" /> Warning</span>
			<span class="error"><input type="checkbox" id="level_error" onClick="switchDebug('error')" value="40" checked="checked" /> Error</span><br />
			<input type="button" onClick="clearDebug()" value="Clear" />
		</div>

		<div id="endContainer" style="display: none;">
			Your session has ended, you can now close the window

			<div id="errorContainer">
			</div>

			<br />
			<input type="button" value="Close" onclick="window.close(); return false" />
		</div>

		<div id="appletContainer" style="<?php
if ($_SESSION['mode'] == 'start')
	echo 'top: 18px; ';
?>display: none;">
		</div>
	</body>
</html>
