<?php
/**
 * Copyright (C) 2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Jeremy DESVAGES <jeremy@ulteo.com>
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

require_once(dirname(__FILE__).'/includes/core.inc.php');

$first = false;
if (! array_key_exists('start_app', $_SESSION)) {
	$first = true;

	$dom = new DomDocument('1.0', 'utf-8');

	$session_node = $dom->createElement('session');
	$user_node = $dom->createElement('user');
	$user_node->setAttribute('login', $_REQUEST['login']);
	$user_node->setAttribute('password', $_REQUEST['password']);
	$session_node->appendChild($user_node);
	$dom->appendChild($session_node);

	$_SESSION['ovd-client']['server'] = SESSIONMANAGER_HOST;
	$_SESSION['ovd-client']['sessionmanager_url'] = 'https://'.$_SESSION['ovd-client']['server'].'/ovd/client';
	$sessionmanager_url = $_SESSION['ovd-client']['sessionmanager_url'];

	query_sm_post_xml($sessionmanager_url.'/auth.php', $dom->saveXML());

	$_SESSION['start_app'] = array();
}

$_SESSION['start_app'][] = array(
	'id'	=>	$_GET['app']
);
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

		<script type="text/javascript">
			var i18n = new Hash();
		</script>

		<link rel="shortcut icon" type="image/png" href="media/image/favicon.ico" />
		<link rel="stylesheet" type="text/css" href="media/style/common.css" />
		<script type="text/javascript" src="media/script/common.js?<?php echo time(); ?>" charset="utf-8"></script>

		<script type="text/javascript" src="media/script/daemon.js?<?php echo time(); ?>" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/daemon_external.js?<?php echo time(); ?>" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/server.js?<?php echo time(); ?>" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/application.js?<?php echo time(); ?>" charset="utf-8"></script>

		<script type="text/javascript" src="media/script/timezones.js" charset="utf-8"></script>

		<script type="text/javascript">
			<?php
				if (! $first) {
			?>
					Event.observe(window, 'load', function() {
						window.close();
					});
			<?php
				} else {
			?>
					var daemon;
					var client_language = '<?php echo $user_language; ?>';
					var client_keymap = '<?php echo $user_keymap; ?>';

					Event.observe(window, 'load', function() {
						translateInterface(client_language);
						startExternalSession();
					});
			<?php
				}
			?>
		</script>
	</head>

	<body style="margin: 10px; background: #ddd; color: #333;">
		<div id="lockWrap" style="display: none;">
		</div>

		<div style="background: #2c2c2c; width: 0px; height: 0px;">
			<div id="errorWrap" class="rounded" style="display: none;">
			</div>
			<div id="okWrap" class="rounded" style="display: none;">
			</div>
			<div id="infoWrap" class="rounded" style="display: none;">
			</div>
		</div>

		<div id="testJava">
			<applet id="CheckJava" code="org.ulteo.ovd.applet.CheckJava" codebase="applet/" archive="CheckJava.jar" mayscript="true" width="1" height="1">
				<param name="code" value="org.ulteo.ovd.applet.CheckJava" />
				<param name="codebase" value="applet/" />
				<param name="archive" value="CheckJava.jar" />
				<param name="mayscript" value="true" />
			</applet>
		</div>

		<div style="background: #2c2c2c; width: 0px; height: 0px;">
			<div id="systemTestWrap" class="rounded" style="display: none;">
				<div id="systemTest" class="rounded">
					<table style="width: 100%; margin-left: auto; margin-right: auto;" border="0" cellspacing="1" cellpadding="3">
						<tr>
							<td style="text-align: left; vertical-align: top;">
								<strong><span id="system_compatibility_check_1_gettext">&nbsp;</span></strong>
								<div style="margin-top: 15px;">
									<p id="system_compatibility_check_2_gettext">&nbsp;</p>
									<p id="system_compatibility_check_3_gettext">&nbsp;</p>
								</div>
							</td>
							<td style="width: 32px; height: 32px; text-align: right; vertical-align: top;">
								<img src="media/image/rotate.gif" width="32" height="32" alt="" title="" />
							</td>
						</tr>
					</table>
				</div>
			</div>

			<div id="systemTestErrorWrap" class="rounded" style="display: none;">
				<div id="systemTestError" class="rounded">
					<table style="width: 100%; margin-left: auto; margin-right: auto;" border="0" cellspacing="1" cellpadding="3">
						<tr>
							<td style="text-align: left; vertical-align: middle;">
								<strong><span id="system_compatibility_error_1_gettext">&nbsp;</span></strong>
								<div id="systemTestError1" style="margin-top: 15px; display: none;">
									<p id="system_compatibility_error_2_gettext">&nbsp;</p>
									<p id="system_compatibility_error_3_gettext">&nbsp;</p>
								</div>

								<div id="systemTestError2" style="margin-top: 15px; display: none;">
									<p id="system_compatibility_error_4_gettext">&nbsp;</p>
								</div>

								<p id="system_compatibility_error_5_gettext">&nbsp;</p>
							</td>
							<td style="width: 32px; height: 32px; text-align: right; vertical-align: top;">
								<img src="media/image/error.png" width="32" height="32" alt="" title="" />
							</td>
						</tr>
					</table>
				</div>
			</div>
		</div>

		<div id="applicationsModeContainer" style="display: none;">
			<div id="appsContainer" style="overflow: auto; display: none;">
			</div>

			<div id="runningAppsContainer" style="overflow: auto; display: none;">
			</div>

			<div id="applicationsAppletContainer" style="display: none;">
			</div>
		</div>

		<div id="splashContainer" class="rounded">
			<table style="width: 100%; padding: 10px;" border="0" cellspacing="0" cellpadding="0">
				<tr>
					<td style="text-align: center;" colspan="3">
						<img src="media/image/ulteo.png" alt="" title="" />
					</td>
				</tr>
				<tr>
					<td style="text-align: left; vertical-align: middle; margin-top: 15px;">
						<span style="font-size: 1.35em; font-weight: bold; color: #686868;"><?php echo _('Do not close this Ulteo OVD window!'); ?></span>
					</td>
					<td style="width: 20px"></td>
					<td style="text-align: left; vertical-align: middle;">
						<img src="media/image/rotate.gif" width="32" height="32" alt="" title="" />
					</td>
				</tr>
			</table>
		</div>
	</body>
</html>
