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

$_SESSION['connected_since'] = time();

if (isset($_SESSION['parameters']['client']) && $_SESSION['parameters']['client'] == 'browser') {
	load_gettext();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
	<head>
		<title>Ulteo Open Virtual Desktop</title>

		<?php //<meta http-equiv="Content-Type" content="application/xhtml+xml; charset=utf-8" /> ?>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

		<link rel="shortcut icon" type="image/png" href="../media/image/favicon.ico" />

		<link rel="stylesheet" type="text/css" href="common.css" />

		<link rel="stylesheet" type="text/css" href="../media/script/lib/nifty/niftyCorners.css" />
		<script type="text/javascript" src="../media/script/lib/nifty/niftyCorners.js" charset="utf-8"></script>
		<script type="text/javascript" charset="utf-8">
			NiftyLoad = function() {
				Nifty("div.rounded");
			}
		</script>

		<script type="text/javascript" src="../media/script/lib/prototype/prototype.js" charset="utf-8"></script>
		<script type="text/javascript" src="../media/script/common.js" charset="utf-8"></script>

		<script type="text/javascript" src="../media/script/lib/scriptaculous/scriptaculous.js" charset="utf-8"></script>
		<script type="text/javascript" src="../media/script/lib/scriptaculous/extensions.js" charset="utf-8"></script>

		<script type="text/javascript" src="../media/script/daemon.js" charset="utf-8"></script>
		<script type="text/javascript" src="../media/script/daemon_portal.js" charset="utf-8"></script>
		<script type="text/javascript" src="../media/script/application.js" charset="utf-8"></script>

		<script type="text/javascript" charset="utf-8">
			var daemon;

			Event.observe(window, 'load', function() {
				daemon = new Portal('ulteo-applet.jar', 'org.ulteo.OvdSshConnection', 'ulteo-printing.jar', <?php echo ($_SESSION['debug'] == 1)?'1':'0'; ?>);
				daemon.access_id = 'portal';
				daemon.shareable = <?php echo (isset($_SESSION['parameters']['shareable']))?'true':'false'; ?>;
				daemon.persistent = <?php echo (isset($_SESSION['parameters']['persistent']))?'true':'false'; ?>;
				daemon.in_popup = <?php echo (isset($_SESSION['popup']) && $_SESSION['popup'] == 1)?'true':'false'; ?>;

				daemon.i18n['share'] = '<?php echo _('share'); ?>';
				daemon.i18n['suspend'] = '<?php echo _('suspend'); ?>';
				daemon.i18n['resume'] = '<?php echo _('resume'); ?>';
				daemon.i18n['session_close_unexpected'] = '<?php echo _('Server: session closed unexpectedly'); ?>';
				daemon.i18n['session_end_ok'] = '<?php echo _('Your session has ended, you can now close the window'); ?>';
				daemon.i18n['session_end_unexpected'] = '<?php echo _('Your session has ended unexpectedly'); ?>';
				daemon.i18n['error_details'] = '<?php echo _('error details'); ?>';
				daemon.i18n['close_this_window'] = '<?php echo _('Close this window'); ?>';
				daemon.i18n['start_another_session'] = '<?php printf (_('If you want to start another session, click <a href="%s">here</a>'), $_SESSION['sessionmanager_url']); ?>';

				daemon.loop();
			});
		</script>
	</head>

	<body>
		<div id="lockWrap" style="display: none;">
		</div>

		<div id="errorWrap" style="display: none;">
		</div>
		<div id="okWrap" style="display: none;">
		</div>
		<div id="infoWrap" style="display: none;">
		</div>

		<div id="mainWrap" style="display: none;">
			<div id="headerWrap">
				<table style="width: 100%;" border="0" cellspacing="0" cellpadding="0">
					<tr>
						<td style="text-align: left; border-bottom: 1px solid #ccc;" class="logo">
							<img src="<?php echo $_SESSION['sessionmanager_url'].'/webservices/get_logo.php'; ?>" height="80" alt="Ulteo Open Virtual Desktop" title="Ulteo Open Virtual Desktop" />
						</td>
						<td style="text-align: left; border-bottom: 1px solid #ccc;" class="title centered">
							<h1><?php printf(_('Welcome %s!'), $_SESSION['parameters']['user_displayname']); ?></h1>
						</td>
						<td style="text-align: left; border-bottom: 1px solid #ccc; width: 60%;" class="title centered">
							<div id="newsContainer" style="padding-left: 5px; padding-right: 5px; height: 70px; overflow: auto;">
							</div>
						</td>
						<td style="text-align: center; padding-left: 5px; padding-right: 10px; border-bottom: 1px solid #ccc;">
							<table border="0" cellspacing="0" cellpadding="10">
								<tr>
									<?php
										if (isset($_SESSION['parameters']['persistent'])) {
									?>
									<td style="text-align: center; vertical-align: middle;"><a href="#" onclick="daemon.suspend(); return false;"><img src="../media/image/suspend.png" width="32" height="32" alt="suspend" title="<?php echo _('Suspend'); ?>" /><br /><?php echo _('Suspend'); ?></a></td>
									<?php
										}
									?>
									<td style="text-align: center; vertical-align: middle;"><a href="#" onclick="daemon.logout(); return false;"><img src="../media/image/logout.png" width="32" height="32" alt="logout" title="<?php echo _('Logout'); ?>" /><br /><?php echo _('Logout'); ?></a></td>
								</tr>
							</table>
						</td>
					</tr>
				</table>
			</div>

			<div class="spacer"></div>

			<div id="pageWrap">
				<table id="portalContainer" style="width: 100%;" border="0" cellspacing="3" cellpadding="5">
					<tr>
						<td style="width: 15%; text-align: left; vertical-align: top;">
							<div class="container rounded" style="background: #fff; width: 98%; margin-left: auto; margin-right: auto;">
							<div>
								<h2><?php echo _('My applications'); ?></h2>

								<div id="appsContainer" style="overflow: auto;">
								</div>
							</div>
							</div>
						</td>
						<td style="width: 5px;">
						</td>
						<td style="width: 15%; text-align: left; vertical-align: top;">
							<div class="container rounded" style="background: #fff; width: 98%; margin-left: auto; margin-right: auto;">
							<div>
								<h2><?php echo _('Running applications'); ?></h2>

								<div id="runningAppsContainer" style="overflow: auto;">
								</div>
							</div>
							</div>
						</td>
						<td style="width: 5px;">
						</td>
						<td style="text-align: left; vertical-align: top;">
							<div class="container rounded" style="background: #fff; width: 98%; margin-left: auto; margin-right: auto;">
							<div>
								<h2><?php echo _('My files'); ?></h2>

								<div id="fileManagerContainer" style="overflow: auto;">
								</div>
							</div>
							</div>
						</td>
					</tr>
				</table>
			</div>
		</div>

		<div id="splashContainer">
			<table style="width: 100%" border="0" cellspacing="0" cellpadding="0">
				<tr>
					<td style="text-align: center" colspan="3">
						<img src="../media/image/ulteo.png" width="376" height="188" alt="" title="" />
					</td>
				</tr>
				<tr>
					<td style="text-align: left; vertical-align: middle">
						<span style="font-size: 1.35em; font-weight: bold; color: #686868"><?php echo _('Loading Portal'); ?></span>
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
