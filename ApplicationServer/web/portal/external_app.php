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
require_once(dirname(__FILE__).'/../includes/core.inc.php');

if (isset($_SESSION['parameters']['client']) && $_SESSION['parameters']['client'] == 'browser') {
	load_gettext();

	$window_title = 'Ulteo Open Virtual Desktop';

	try {
		$application = query_url(SESSIONMANAGER_URL.'/webservices/application.php?id='.$_GET['app_id'].'&fqdn='.SERVERNAME);

		$dom = new DomDocument('1.0', 'utf-8');
		@$dom->loadXML($application);

		if (! $dom->hasChildNodes())
			throw new Exception('No Child Nodes');

		$application_node = $dom->getElementsByTagname('application')->item(0);
		if (is_null($application_node))
			throw new Exception('Null Application Node');

		if ($application_node->hasAttribute('name'))
			$name = $application_node->getAttribute('name');

		$window_title = $name;
	} catch (Exception $e) {
	}

	$doc = '';
	if (isset($_GET['doc']) && $_GET['doc'] != '')
		$doc = $_GET['doc'];
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
	<head>
		<title><?php echo $window_title; ?></title>

		<?php //<meta http-equiv="Content-Type" content="application/xhtml+xml; charset=utf-8" /> ?>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

		<link rel="shortcut icon" type="image/png" href="../icon.php?id=<?php echo $_GET['app_id']; ?>" />

		<link rel="stylesheet" type="text/css" href="common.css" />

		<script type="text/javascript" src="../media/script/lib/prototype/prototype.js" charset="utf-8"></script>
		<script type="text/javascript" src="../media/script/common.js" charset="utf-8"></script>

		<script type="text/javascript" src="../media/script/lib/scriptaculous/scriptaculous.js" charset="utf-8"></script>
		<script type="text/javascript" src="../media/script/lib/scriptaculous/extensions.js" charset="utf-8"></script>

		<script type="text/javascript" src="../media/script/daemon.js" charset="utf-8"></script>
		<script type="text/javascript" src="../media/script/daemon_startapp.js" charset="utf-8"></script>

		<script type="text/javascript" charset="utf-8">
			var daemon;

			Event.observe(window, 'load', function() {
				daemon = new StartApp('ulteo-applet.jar', 'org.ulteo.applet.PortalApplication', 'ulteo-printing.jar', <?php echo ($_SESSION['debug'] == 1)?'1':'0'; ?>);
				daemon.app_id = '<?php echo $_GET['app_id']; ?>';
				daemon.doc = '<?php echo $doc; ?>';

				daemon.i18n['session_close_unexpected'] = '<?php echo _('Server: session closed unexpectedly'); ?>';
				daemon.i18n['application_end_ok'] = '<?php echo _('Your application has ended, you can now close the window'); ?>';
				daemon.i18n['application_end_unexpected'] = '<?php echo _('Your application has ended unexpectedly'); ?>';
				daemon.i18n['error_details'] = '<?php echo _('error details'); ?>';
				daemon.i18n['close_this_window'] = '<?php echo _('Close this window'); ?>';

				daemon.loop();

				Effect.ShakeUp($('app_icon'), {'distance': 5, 'duration': 1.5, 'continue_statement': $('splashContainer')});
			});
		</script>
	</head>

	<body>
		<div id="splashContainer">
			<table style="width: 100%" border="0" cellspacing="0" cellpadding="0">
				<tr>
					<td style="text-align: center" colspan="3">
						<img src="../media/image/ulteo.png" width="376" height="188" alt="" title="" />
					</td>
				</tr>
				<tr>
					<td style="text-align: right; vertical-align: middle">
						<img id="app_icon" src="../icon.php?id=<?php echo $_GET['app_id']; ?>" width="32" height="32" alt="" title="" />
					</td>
					<td style="width: 5px"></td>
					<td style="text-align: left; vertical-align: middle">
						<span style="font-size: 1.35em; font-weight: bold; color: #686868"><?php printf(_('Loading %s'), $window_title); ?></span>
					</td>
					<td style="width: 20px"></td>
					<td style="text-align: left; vertical-align: middle">
						<img src="../media/image/rotate.gif" width="32" height="32" alt="" title="" />
					</td>
				</tr>
			</table>
		</div>

		<div id="debugContainer" class="no_debug info warning error" style="display: none;">
		</div>

		<div id="debugLevels" style="display: none;">
			<span class="debug"><input type="checkbox" id="level_debug" onclick="switchDebug('debug')" value="10" /> Debug</span>
			<span class="info"><input type="checkbox" id="level_info" onclick="switchDebug('info')" value="20" checked="checked" /> Info</span>
			<span class="warning"><input type="checkbox" id="level_warning" onclick="switchDebug('warning')" value="30" checked="checked" /> Warning</span>
			<span class="error"><input type="checkbox" id="level_error" onclick="switchDebug('error')" value="40" checked="checked" /> Error</span><br />
			<input type="button" onclick="clearDebug(); return false;" value="Clear" />
		</div>

		<div id="endContainer" style="display: none;">
			<table style="width: 100%" border="0" cellspacing="0" cellpadding="0">
				<tr>
					<td style="text-align: center">
						<img src="../media/image/ulteo.png" width="376" height="188" alt="" title="" />
					</td>
				</tr>
				<tr>
					<td style="text-align: center; vertical-align: middle" id="endContent">
					</td>
				</tr>
			</table>
		</div>

		<div id="appletContainer" style="display: none;">
		</div>

		<div id="printerContainer">
		</div>
	</body>
</html>
<?php
	die();
}

header('Content-Type: text/xml; charset=utf-8');

$dom = new DomDocument('1.0', 'utf-8');
$aps_node = $dom->createElement('aps');
$aps_node->setAttribute('server', $_SERVER['SERVER_NAME']);
$dom->appendChild($aps_node);

$xml = $dom->saveXML();

echo $xml;
