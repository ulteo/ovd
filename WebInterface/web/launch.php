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
$daemon_class = strtoupper(substr($_POST['session_mode'], 0, 1)).substr($_POST['session_mode'], 1);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
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
		<script type="text/javascript" src="media/script/daemon_<?php echo $_POST['session_mode']; ?>.js?<?php echo time(); ?>" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/application.js?<?php echo time(); ?>" charset="utf-8"></script>

		<script type="text/javascript" charset="utf-8">
			Event.observe(window, 'load', function() {
				if ($('splashContainer'))
					Effect.Center($('splashContainer'));
				if ($('endContainer'))
					Effect.Center($('endContainer'));
				$('endContainer').style.top = parseInt($('endContainer').style.top)-50+'px';

				if ($('<?php echo $_POST['session_mode']; ?>Container'))
					$('<?php echo $_POST['session_mode']; ?>Container').hide();
				if ($('appletContainer'))
					$('appletContainer').hide();
				if ($('splashContainer'))
					$('splashContainer').show();
			});
		</script>

		<script type="text/javascript" charset="utf-8">
			var daemon;

			Event.observe(window, 'load', function() {
				daemon = new <?php echo $daemon_class; ?>('ulteo-applet.jar', 'org.ulteo.applet.Standalone', 'ulteo-printing.jar');
				daemon.access_id = '<?php echo $_POST['session_mode']; ?>';

				daemon.session_id = '<?php echo $_POST['session_id']; ?>';
				daemon.session_server = '<?php echo $_POST['session_server']; ?>';
				daemon.session_login = '<?php echo $_POST['session_login']; ?>';
				daemon.session_password = '<?php echo $_POST['session_password']; ?>';

				daemon.i18n['session_close_unexpected'] = '<?php echo str_replace("'", "\'", _('Server: session closed unexpectedly')); ?>';
				daemon.i18n['session_end_ok'] = '<?php echo str_replace("'", "\'", _('Your session has ended, you can now close the window')); ?>';
				daemon.i18n['session_end_unexpected'] = '<?php echo str_replace("'", "\'", _('Your session has ended unexpectedly')); ?>';
				daemon.i18n['error_details'] = '<?php echo str_replace("'", "\'", _('error details')); ?>';
				daemon.i18n['close_this_window'] = '<?php echo str_replace("'", "\'", _('Close this window')); ?>';
				daemon.i18n['start_another_session'] = '<?php echo str_replace("'", "\'", _('You can now start a new session')); ?>';

				daemon.i18n['share'] = '<?php echo str_replace("'", "\'", _('share')); ?>';
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
			<div id="appletContainer" style="display: none;">
			</div>
		</div>

		<div id="portalModeContainer" style="display: none;">
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
		</div>

		<div id="printerContainer">
		</div>
	</body>
</html>
