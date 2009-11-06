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
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
	<head>
		<title>Ulteo Open Virtual Desktop</title>

		<?php //<meta http-equiv="Content-Type" content="application/xhtml+xml; charset=utf-8" /> ?>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

		<link rel="shortcut icon" type="image/png" href="../media/image/favicon.ico" />

		<link rel="stylesheet" type="text/css" href="common.css" />

		<script type="text/javascript" src="../media/script/lib/prototype/prototype.js" charset="utf-8"></script>
		<script type="text/javascript" src="../media/script/common.js" charset="utf-8"></script>

		<script type="text/javascript" src="../media/script/lib/scriptaculous/scriptaculous.js" charset="utf-8"></script>
		<script type="text/javascript" src="../media/script/lib/scriptaculous/extensions.js" charset="utf-8"></script>

		<script type="text/javascript" src="../media/script/daemon.js" charset="utf-8"></script>
		<script type="text/javascript" src="../media/script/daemon_desktop.js" charset="utf-8"></script>

		<script type="text/javascript" charset="utf-8">
			var daemon;

			Event.observe(window, 'load', function() {
				daemon = new Desktop('ulteo-applet.jar', 'org.ulteo.applet.Standalone', 'ulteo-printing.jar', <?php echo ($_SESSION['debug'] == 1)?'1':'0'; ?>);

				daemon.in_popup = false;
				daemon.error_message = '<?php echo _('Server: invalid token'); ?>';
				daemon.i18n['session_end_unexpected'] = '<?php echo _('Your session has ended unexpectedly'); ?>';
				daemon.i18n['error_details'] = '<?php echo _('error details'); ?>';
				daemon.i18n['start_another_session'] = '<?php printf (_('If you want to start another session, click <a href="%s">here</a>'), $_SESSION['sessionmanager_url']); ?>';

				daemon.do_ended();
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
					<td style="text-align: left; vertical-align: middle">
						<span style="font-size: 1.35em; font-weight: bold; color: #686868"><?php echo _('Loading Error'); ?></span>
					</td>
					<td style="width: 20px"></td>
					<td style="text-align: left; vertical-align: middle">
						<img src="../media/image/rotate.gif" width="32" height="32" alt="" title="" />
					</td>
				</tr>
			</table>
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
	</body>
</html>
