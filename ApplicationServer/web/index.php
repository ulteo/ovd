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

if (!isset($_SERVER['HTTP_REFERER']) && !isset($_GET['token'])) {
	header('Location: '.SESSIONMANAGER_URL);
	die();
}

if (!isset($_SERVER['HTTP_REFERER']) && isset($_GET['token']))
	$_SERVER['HTTP_REFERER'] = SESSIONMANAGER_URL;

$buf1 = @parse_url($_SERVER['HTTP_REFERER']);
$buf2 = @parse_url(SESSIONMANAGER_URL);
$sessionmanager_url = $buf1['scheme'].'://'.$buf1['host'].$buf2['path'];

if (!isset($_GET['token']) || $_GET['token'] == '')
	header('Location: '.$sessionmanager_url);
$token = $_GET['token'];

if (!isset($_SESSION['current_token']) || $_SESSION['current_token'] != $token) {
	session_destroy();
	session_start();
}
$_SESSION['current_token'] = $token;

$_SESSION['sessionmanager_url'] = $sessionmanager_url;

$xml = query_url(SESSIONMANAGER_URL.'/webservices/session_token.php?fqdn='.SERVERNAME.'&token='.$token);

$dom = new DomDocument();
@$dom->loadXML($xml);

if (! $dom->hasChildNodes())
	die('Invalid XML');

$session_node = $dom->getElementsByTagname('session')->item(0);
if (is_null($session_node))
	die('Missing element \'session\'');

if ($session_node->hasAttribute('id'))
	$_SESSION['session'] = $session_node->getAttribute('id');
if ($session_node->hasAttribute('mode'))
	$_SESSION['mode'] = $session_node->getAttribute('mode');
$_SESSION['owner'] = false;
if ($_SESSION['mode'] == 'start' || $_SESSION['mode'] == 'resume')
	$_SESSION['owner'] = true;

$parameters = array();
foreach ($session_node->childNodes as $node) {
	if (!$node->hasAttribute('value'))
		continue;

	$parameters[$node->nodeName] = $node->getAttribute('value');
}

$settings = array('user_login', 'user_displayname', 'locale', 'quality'); //user_id

foreach ($settings as $setting)
	if (!isset($parameters[$setting]))
		die('Missing parameter \''.$setting.'\'');

$_SESSION['parameters'] = $parameters;

$_SESSION['debug'] = (isset($_SESSION['parameters']['debug']))?1:0;

$_SESSION['share_desktop'] = 'true';
if ($_SESSION['owner'])
	$_SESSION['parameters']['view_only'] = 'No';

$module_fs_node = $session_node->getElementsByTagname('module_fs')->item(0);
if (is_null($module_fs_node))
	die('Missing element \'module_fs\'');

$_SESSION['parameters']['module_fs'] = array();
$_SESSION['parameters']['module_fs']['type'] = $module_fs_node->getAttribute('type');

$param_nodes = $module_fs_node->getElementsByTagname('param');
foreach ($param_nodes as $param_node)
	if ($param_node->hasAttribute('key') && $param_node->hasAttribute('value'))
		$_SESSION['parameters']['module_fs'][$param_node->getAttribute('key')] = $param_node->getAttribute('value');

$menu_node = $session_node->getElementsByTagname('menu')->item(0);
if (is_null($menu_node))
	die('Missing element \'menu\'');

$application_nodes = $menu_node->getElementsByTagname('application');
$_SESSION['parameters']['desktopfiles'] = array();
foreach ($application_nodes as $application_node)
	if ($application_node->hasAttribute('desktopfile'))
		$_SESSION['parameters']['desktopfiles'][md5($application_node->getAttribute('desktopfile'))] = $application_node->getAttribute('desktopfile');

$_SESSION['print_timestamp'] = time();

if ($_SESSION['mode'] == 'start')
	@touch(SESSION2CREATE_PATH.'/'.$_SESSION['session']);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>Ulteo Open Virtual Desktop</title>

		<?php //<meta http-equiv="Content-Type" content="application/xhtml+xml; charset=utf-8" /> ?>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

		<link rel="shortcut icon" type="image/png" href="media/image/favicon.ico" />

		<link rel="stylesheet" type="text/css" href="media/style/common.css" />

		<script type="text/javascript" src="media/script/lib/prototype/prototype.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/common.js" charset="utf-8"></script>

		<script type="text/javascript" src="media/script/lib/scriptaculous/scriptaculous.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/lib/scriptaculous/extensions.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/daemon.js" charset="utf-8"></script>

		<script type="text/javascript" charset="utf-8">
			Event.observe(window, 'load', function() {
				daemon_init('<?php echo $_SERVER['SERVER_NAME']; ?>', <?php echo ($_SESSION['debug'] == 1)?'1':'0'; ?>);
			});
		</script>
	</head>

	<body>
<?php
if ($_SESSION['owner'] && isset($_SESSION['parameters']['shareable'])) {
?>
		<div id="menuContainer" style="display: none;">
			<div id="menuShareWarning"></div>

			<a href="javascript:;" onclick="clicMenu('menuShare'); return false;">
				<img src="media/image/share-button.png" width="80" height="18" alt="Share desktop" title="Share desktop" />
			</a>
		</div>

		<div id="menuShare" style="display: none;">
			<div style="width: 500px; height: 300px; float: right;">
				<div style="background: #fff; width: 500px; height: 300px;" id="menuShareFrame">
					<h2 class="centered"><?php echo _('Desktop sharing'); ?></h2>
					<span id="menuShareContent"></span>
					<fieldset style="border: 0;">
						<form action="javascript:;" method="post" onsubmit="do_invite(); return false;">
							<p><?php echo _('Email address'); ?>: <input type="text" id="invite_email" name="email" value="" />
							<input class="input_checkbox" type="checkbox" id="invite_mode" name="mode" /> <?php echo _('active mode'); ?></p>

							<input type="submit" id="invite_submit" value="<?php echo _('Invite'); ?>" />
						</form>
					</fieldset>
				</div>
			</div>
		</div>
<?php
}
?>

		<div id="splashContainer">
			<table style="width: 100%" border="0" cellspacing="0" cellpadding="0">
				<tr>
					<td style="text-align: center" colspan="3">
						<img src="media/image/ulteo.png" width="376" height="188" alt="" title="" align="middle" />
					</td>
				</tr>
				<tr>
					<td style="text-align: left; vertical-align: middle">
						<span style="font-size: 1.35em; font-weight: bold; color: #686868">Loading Open Virtual Desktop</span>
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
			<input type="button" onclick="clearDebug(); return false;" value="Clear" />
		</div>

		<div id="endContainer" style="display: none;">
			Your session has ended, you can now close the window

			<div id="errorContainer">
			</div>

			<br />
			<input type="button" value="Close" onclick="window.close(); return false" />
		</div>

		<div id="appletContainer" style="<?php
if ($_SESSION['owner'] && isset($_SESSION['parameters']['shareable']))
	echo 'top: 18px; ';
?>display: none;">
		</div>

		<div id="printerContainer">
		</div>
	</body>
</html>
