<?php
/**
 * Copyright (C) 2008-2010 Ulteo SAS
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
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>Ulteo Open Virtual Desktop</title>

		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

		<script type="text/javascript" src="media/script/lib/prototype/prototype.js" charset="utf-8"></script>

		<script type="text/javascript" src="media/script/lib/scriptaculous/scriptaculous.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/lib/scriptaculous/extensions.js" charset="utf-8"></script>

		<link rel="stylesheet" type="text/css" href="media/script/lib/nifty/niftyCorners.css" />
		<script type="text/javascript" src="media/script/lib/nifty/niftyCorners.js" charset="utf-8"></script>
		<script type="text/javascript" charset="utf-8">
			NiftyLoad = function() {
				Nifty('div.rounded');
			}
		</script>

		<link rel="shortcut icon" type="image/png" href="favicon.ico" />
		<link rel="stylesheet" type="text/css" href="media/style/common.css" />
		<script type="text/javascript" src="media/script/common.js?<?php echo time(); ?>" charset="utf-8"></script>

		<script type="text/javascript" src="media/script/daemon.js?<?php echo time(); ?>" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/daemon_<?php echo $_SESSION['session_mode']; ?>.js?<?php echo time(); ?>" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/server.js?<?php echo time(); ?>" charset="utf-8"></script>
		<?php
			if ($_SESSION['session_mode'] == 'portal') {
		?>
		<script type="text/javascript" src="media/script/application.js?<?php echo time(); ?>" charset="utf-8"></script>
		<?php
			}
		?>

		<script type="text/javascript" charset="utf-8">
			Event.observe(window, 'load', function() {
				if ($('splashContainer'))
					Effect.Center($('splashContainer'));
				if ($('endContainer')) {
					Effect.Center($('endContainer'));
					$('endContainer').style.top = parseInt($('endContainer').style.top)-50+'px';
				}

				if ($('<?php echo $_SESSION['session_mode']; ?>ModeContainer'))
					$('<?php echo $_SESSION['session_mode']; ?>ModeContainer').hide();
				if ($('<?php echo $_SESSION['session_mode']; ?>AppletContainer'))
					$('<?php echo $_SESSION['session_mode']; ?>AppletContainer').hide();
				if ($('splashContainer'))
					$('splashContainer').show();
			});
		</script>

		<script type="text/javascript" charset="utf-8">
			var daemon;

			Event.observe(window, 'load', function() {
				daemon = new <?php echo strtoupper(substr($_SESSION['session_mode'], 0, 1)).substr($_SESSION['session_mode'], 1); ?>('ulteo-applet.jar', 'org.ulteo.ovd.applet.<?php echo strtoupper(substr($_SESSION['session_mode'], 0, 1)).substr($_SESSION['session_mode'], 1); ?>', 'ulteo-printing.jar', <?php echo $_SESSION['interface']['in_popup']; ?>, <?php echo $_SESSION['interface']['debug']; ?>);
				daemon.access_id = '<?php echo $_SESSION['session_mode']; ?>';

				daemon.i18n['session_close_unexpected'] = '<?php echo str_replace("'", "\'", _('Server: session closed unexpectedly')); ?>';
				daemon.i18n['session_end_ok'] = '<?php echo str_replace("'", "\'", _('Your session has ended, you can now close the window')); ?>';
				daemon.i18n['session_end_unexpected'] = '<?php echo str_replace("'", "\'", _('Your session has ended unexpectedly')); ?>';
				daemon.i18n['error_details'] = '<?php echo str_replace("'", "\'", _('error details')); ?>';
				daemon.i18n['close_this_window'] = '<?php echo str_replace("'", "\'", _('Close this window')); ?>';
				daemon.i18n['start_another_session'] = '<?php printf(str_replace("'", "\'", _('Click %shere%s to start a new session')), '<a href="index.php">', '</a>'); ?>';

				daemon.i18n['suspend'] = '<?php echo str_replace("'", "\'", _('suspend')); ?>';
				daemon.i18n['resume'] = '<?php echo str_replace("'", "\'", _('resume')); ?>';

				setTimeout(function() {
					daemon.loop();
				}, 1000);
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

		<div id="splashContainer">
			<table style="width: 100%" border="0" cellspacing="0" cellpadding="0">
				<tr>
					<td style="text-align: center" colspan="3">
						<img src="media/image/ulteo.png" width="376" height="188" alt="" title="" />
					</td>
				</tr>
				<tr>
					<td style="text-align: left; vertical-align: middle">
						<span style="font-size: 1.35em; font-weight: bold; color: #686868"><?php echo _('Loading:'); ?> Open Virtual Desktop</span>
					</td>
					<td style="width: 20px"></td>
					<td style="text-align: left; vertical-align: middle">
						<img src="media/image/rotate.gif" width="32" height="32" alt="" title="" />
					</td>
				</tr>
			</table>
		</div>

		<div id="endContainer" style="display: none;">
			<table style="width: 100%" border="0" cellspacing="0" cellpadding="0">
				<tr>
					<td style="text-align: center">
						<img src="media/image/ulteo.png" width="376" height="188" alt="" title="" />
					</td>
				</tr>
				<tr>
					<td style="text-align: center; vertical-align: middle" id="endContent">
					</td>
				</tr>
			</table>
		</div>

		<div id="desktopModeContainer" style="display: none;">
			<div id="desktopAppletContainer" style="display: none;">
			</div>
		</div>

		<div id="portalModeContainer" style="display: none;">
			<div id="portalHeaderWrap">
				<table style="width: 100%; margin-left: auto; margin-right: auto;" border="0" cellspacing="0" cellpadding="0">
					<tr>
						<td style="width: 175px; text-align: left; border-bottom: 1px solid #ccc;" class="logo">
							<img src="media/image/ulteo.png" height="80" alt="Ulteo Open Virtual Desktop" title="Ulteo Open Virtual Desktop" />
						</td>
						<td style="text-align: left; border-bottom: 1px solid #ccc; width: 60%;" class="title centered">
							<h1><?php printf(_('Welcome %s!'), $_SESSION['session_displayname']); ?></h1>
						</td>
						<td style="text-align: right; padding-left: 5px; padding-right: 10px; border-bottom: 1px solid #ccc;">
							<table style="margin-left: auto; margin-right: 0px;" border="0" cellspacing="0" cellpadding="10">
								<tr>
									<?php
										/*{ //persistent session
									?>
									<td style="text-align: center; vertical-align: middle;"><a href="#" onclick="daemon.suspend(); return false;"><img src="media/image/suspend.png" width="32" height="32" alt="suspend" title="<?php echo _('Suspend'); ?>" /><br /><?php echo _('Suspend'); ?></a></td>
									<?php
										}*/
									?>
									<td style="text-align: center; vertical-align: middle;"><a href="#" onclick="daemon.logout(); return false;"><img src="media/image/logout.png" width="32" height="32" alt="logout" title="<?php echo _('Logout'); ?>" /><br /><?php echo _('Logout'); ?></a></td>
								</tr>
							</table>
						</td>
					</tr>
				</table>
			</div>

			<table id="portalContainer" style="width: 100%; background: #eee;" border="0" cellspacing="0" cellpadding="5">
				<tr>
					<td style="width: 15%; text-align: left; vertical-align: top; background: #eee;">
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
					<td style="width: 15%; text-align: left; vertical-align: top; background: #eee;">
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
					<td style="text-align: left; vertical-align: top; background: #eee;">
						<div class="container rounded" style="background: #fff; width: 98%; margin-left: auto; margin-right: auto;">
						<div>
							<h2><?php echo _('News'); ?></h2>

							<div id="newsContainer" style="overflow: auto;">
							</div>
						</div>
						</div>
					</td>
				</tr>
			</table>

			<div id="portalAppletContainer" style="display: none;">
			</div>
		</div>

		<div id="debugContainer" class="no_debug info warning error" style="display: none;">
		</div>

		<div id="debugLevels" style="display: none;">
			<span class="debug"><input type="checkbox" id="level_debug" onclick="daemon.switch_debug('debug');" value="10" /> Debug</span>
			<span class="info"><input type="checkbox" id="level_info" onclick="daemon.switch_debug('info');" value="20" checked="checked" /> Info</span>
			<span class="warning"><input type="checkbox" id="level_warning" onclick="daemon.switch_debug('warning');" value="30" checked="checked" /> Warning</span>
			<span class="error"><input type="checkbox" id="level_error" onclick="daemon.switch_debug('error');" value="40" checked="checked" /> Error</span><br />
			<input type="button" onclick="daemon.clear_debug(); return false;" value="Clear" />
		</div>

		<div id="printerContainer">
		</div>
	</body>
</html>
