<?php
/**
 * Copyright (C) 2010-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2011
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2010-2011
 * Author Julien LANGLOIS <julien@ulteo.com> 2011
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

$languages = get_available_languages();
$keymaps = get_available_keymaps();

if (defined('SESSIONMANAGER_HOST'))
	$users = get_users_list();

$wi_sessionmanager_host = '';
if (defined('SESSIONMANAGER_HOST'))
	$wi_sessionmanager_host	= SESSIONMANAGER_HOST;
if (isset($_COOKIE['ovd-client']['sessionmanager_host']))
	$wi_sessionmanager_host = (string)$_COOKIE['ovd-client']['sessionmanager_host'];

$wi_user_login = '';
if (isset($_COOKIE['ovd-client']['user_login']))
	$wi_user_login = (string)$_COOKIE['ovd-client']['user_login'];

$wi_use_local_credentials = 0;
if (defined('OPTION_FORCE_USE_LOCAL_CREDENTIALS'))
	$wi_use_local_credentials = ((OPTION_FORCE_USE_LOCAL_CREDENTIALS==true)?1:0);
elseif (isset($_COOKIE['ovd-client']['use_local_credentials']))
	$wi_use_local_credentials = (int)$_COOKIE['ovd-client']['use_local_credentials'];

if (! defined('OPTION_SHOW_USE_LOCAL_CREDENTIALS'))
	define('OPTION_SHOW_USE_LOCAL_CREDENTIALS', false);

$wi_session_mode = 'desktop';
if (defined('OPTION_FORCE_SESSION_MODE'))
	$wi_session_mode = OPTION_FORCE_SESSION_MODE;
elseif (isset($_COOKIE['ovd-client']['session_mode']))
	$wi_session_mode = (string)$_COOKIE['ovd-client']['session_mode'];

if (OPTION_FORCE_LANGUAGE !== true && isset($_COOKIE['ovd-client']['session_language'])) {
	$wi_session_language = (string)$_COOKIE['ovd-client']['session_language'];
	$user_language = $wi_session_language;
}
if (strlen($user_language) == 2)
	$user_language = $user_language.'-'.$user_language;

if (OPTION_FORCE_KEYMAP !== true && isset($_COOKIE['ovd-client']['session_keymap']))
	$user_keymap = (string)$_COOKIE['ovd-client']['session_keymap'];

$wi_desktop_fullscreen = 0;
if (defined('OPTION_FORCE_FULLSCREEN'))
	$wi_desktop_fullscreen = ((OPTION_FORCE_FULLSCREEN==true)?1:0);
elseif (isset($_COOKIE['ovd-client']['desktop_fullscreen']))
	$wi_desktop_fullscreen = (int)$_COOKIE['ovd-client']['desktop_fullscreen'];

$wi_debug = 1;
if (isset($_COOKIE['ovd-client']['debug']))
	$wi_debug = (int)$_COOKIE['ovd-client']['debug'];

$rdp_input_unicode = null;
if (defined('RDP_INPUT_METHOD'))
	$rdp_input_unicode = RDP_INPUT_METHOD;

function get_users_list() {
	if (! defined('SESSIONMANAGER_HOST'))
		return false;

	global $sessionmanager_url;

	$ret = query_sm($sessionmanager_url.'/userlist.php');

	$dom = new DomDocument('1.0', 'utf-8');
	$buf = @$dom->loadXML($ret);
	if (! $buf)
		return false;

	if (! $dom->hasChildNodes())
		return false;

	$users_node = $dom->getElementsByTagname('users')->item(0);
	if (is_null($users_node))
		return false;

	$users = array();
	foreach ($users_node->childNodes as $user_node) {
		if ($user_node->hasAttribute('login'))
			$users[$user_node->getAttribute('login')] = ((strlen($user_node->getAttribute('displayname')) > 32)?substr($user_node->getAttribute('displayname'), 0, 32).'...':$user_node->getAttribute('displayname'));
	}
	natcasesort($users);

	if (count($users) == 0)
		return false;

	return $users;
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>Ulteo Open Virtual Desktop</title>

		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

		<meta http-equiv="X-UA-Compatible" content="IE=8" />

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
			function updateSMHostField() {
				 if ($('sessionmanager_host').style.color != 'grey')
					return;
				
				$('sessionmanager_host').value = i18n.get('sessionmanager_host_example');
			}
			
			var i18n = new Hash();

			var user_keymap = '<?php echo $user_keymap; ?>';
			var OPTION_KEYMAP_AUTO_DETECT = <?php echo ( (OPTION_KEYMAP_AUTO_DETECT === true && !isset($_COOKIE['ovd-client']['session_keymap']))?'true':'false'); ?>;
		</script>

		<link rel="shortcut icon" type="image/png" href="media/image/favicon.ico" />
		<link rel="stylesheet" type="text/css" href="media/style/common.css" />
		<script type="text/javascript" src="media/script/common.js?<?php echo time(); ?>" charset="utf-8"></script>

		<script type="text/javascript" src="media/script/daemon.js?<?php echo time(); ?>" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/daemon_desktop.js?<?php echo time(); ?>" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/daemon_applications.js?<?php echo time(); ?>" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/server.js?<?php echo time(); ?>" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/application.js?<?php echo time(); ?>" charset="utf-8"></script>

		<script type="text/javascript" src="media/script/timezones.js" charset="utf-8"></script>

		<script type="text/javascript">
			var daemon;
			var rdp_input_method = <?php echo (($rdp_input_unicode == null)?'null':'\''.$rdp_input_unicode.'\''); ?>;

			Event.observe(window, 'load', function() {
				new Effect.Center($('splashContainer'));
				new Effect.Center($('desktopFullscreenContainer'));
				new Effect.Center($('endContainer'));

				Event.observe(window, 'resize', function() {
					new Effect.Center($('splashContainer'));
					new Effect.Center($('desktopFullscreenContainer'));
					new Effect.Center($('endContainer'));
				});

				$('desktopModeContainer').hide();
				$('desktopAppletContainer').hide();

				$('applicationsModeContainer').hide();
				$('applicationsAppletContainer').hide();

				$('fileManagerWrap').hide();

				$('debugContainer').hide();
				$('debugLevels').hide();
			});
		</script>
	</head>

	<body style="margin: 50px; background: #ddd; color: #333;">
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

			<div id="newsWrap" class="rounded" style="display: none;">
				<div id="newsWrapCont" class="rounded">
					<table style="width: 100%; margin-left: auto; margin-right: auto;" border="0" cellspacing="1" cellpadding="3">
						<tr>
							<td style="width: 100%; text-align: left; vertical-align: top;">
								<div id="newsWrap_title"></div>
							</td>
							<td style="width: 32px; height: 32px; text-align: right; vertical-align: top; margin-bottom: 15px;">
								<img src="media/image/news.png" width="32" height="32" alt="" title="" />
							</td>
						</tr>
						<tr>
							<td style="text-align: left; vertical-align: top; margin-bottom: 15px;" colspan="2">
								<div id="newsWrap_content"></div>
							</td>
						</tr>
						<tr>
							<td style="text-align: right; vertical-align: bottom; margin: 10px;" colspan="2">
								<a href="javascript:;" onclick="hideNews(); return false;"><span id="close_gettext">&nbsp;</span></a>
							</td>
						</tr>
					</table>
				</div>
			</div>
		</div>

		<div id="splashContainer" class="rounded" style="display: none;">
			<table style="width: 100%; padding: 10px;" border="0" cellspacing="0" cellpadding="0">
				<tr>
					<td style="text-align: center;" colspan="3">
						<img src="media/image/ulteo.png" alt="" title="" />
					</td>
				</tr>
				<tr>
					<td style="text-align: left; vertical-align: middle; margin-top: 15px;">
						<span style="font-size: 1.35em; font-weight: bold; color: #686868;" id="loading_ovd_gettext">&nbsp;</span>
					</td>
					<td style="width: 20px"></td>
					<td style="text-align: left; vertical-align: middle;">
						<img src="media/image/rotate.gif" width="32" height="32" alt="" title="" />
					</td>
				</tr>
				<tr>
					<td style="text-align: left; vertical-align: middle;" colspan="3">
						<div id="progressBar">
							<div id="progressBarContent"></div>
						</div>
					</td>
				</tr>
			</table>
		</div>

		<div id="desktopFullscreenContainer" class="rounded" style="display: none;">
			<table style="width: 100%; padding: 10px;" border="0" cellspacing="0" cellpadding="0">
				<tr>
					<td style="text-align: center;">
						<img src="media/image/ulteo.png" alt="" title="" />
					</td>
				</tr>
				<tr>
					<td style="text-align: center; vertical-align: middle; margin-top: 15px;">
						<span style="font-size: 1.1em; font-weight: bold; color: #686868;" id="desktop_fullscreen_text1_gettext">&nbsp;</span>
						<br /><br />
						<span style="font-size: 1.1em; font-weight: bold; color: #686868;" id="desktop_fullscreen_text2_gettext">&nbsp;</span>
					</td>
				</tr>
			</table>
		</div>

		<div id="endContainer" class="rounded" style="display: none;">
			<table style="width: 100%; padding: 10px;" border="0" cellspacing="0" cellpadding="0">
				<tr>
					<td style="text-align: center;">
						<img src="media/image/ulteo.png" alt="" title="" />
					</td>
				</tr>
				<tr>
					<td style="text-align: center; vertical-align: middle; margin-top: 15px;" id="endContent">
					</td>
				</tr>
			</table>
		</div>

		<div id="desktopModeContainer" style="display: none;">
			<div id="desktopAppletContainer" style="display: none;">
			</div>
		</div>

		<div id="applicationsModeContainer" style="display: none;">
			<div id="applicationsHeaderWrap">
				<table style="width: 100%; margin-left: auto; margin-right: auto;" border="0" cellspacing="0" cellpadding="0">
					<tr>
						<td style="width: 175px; text-align: left; border-bottom: 1px solid #ccc;" class="logo">
							<img src="media/image/ulteo.png" height="80" alt="Ulteo Open Virtual Desktop" title="Ulteo Open Virtual Desktop" />
						</td>
						<td style="text-align: left; border-bottom: 1px solid #ccc;" class="title centered">
							<h1><span id="user_displayname">&nbsp;</span><span id="welcome_gettext" style="display: none;">&nbsp;</span></h1>
						</td>
						<td style="width: 60%; border-bottom: 1px solid #ccc; text-align: left;" class="title centered">
							<div id="newsContainer" style="padding-left: 5px; padding-right: 5px; height: 70px; overflow: auto;">
							</div>
						</td>
						<td style="text-align: right; padding-left: 5px; padding-right: 10px; border-bottom: 1px solid #ccc;">
							<table style="margin-left: auto; margin-right: 0px;" border="0" cellspacing="0" cellpadding="10">
								<tr>
									<?php
										/*{ //persistent session
									?>
									<td style="text-align: center; vertical-align: middle;"><a href="#" onclick="daemon.suspend(); return false;"><img src="media/image/suspend.png" width="32" height="32" alt="" title="" /><br /><span id="suspend_gettext">&nbsp;</span></a></td>
									<?php
										}*/
									?>
									<td style="text-align: center; vertical-align: middle;"><a href="#" onclick="daemon.logout(); return false;"><img src="media/image/logout.png" width="32" height="32" alt="" title="" /><br /><span id="logout_gettext">&nbsp;</span></a></td>
								</tr>
							</table>
						</td>
					</tr>
				</table>
			</div>

			<table id="applicationsContainer" style="width: 100%; background: #eee;" border="0" cellspacing="0" cellpadding="5">
				<tr>
					<td style="width: 15%; text-align: left; vertical-align: top; background: #eee;">
						<div class="container rounded" style="background: #fff; width: 98%; margin-left: auto; margin-right: auto;">
						<div>
							<h2 style="display: none;"><span id="my_apps_gettext">&nbsp;</span></h2>

							<div id="appsContainer" style="overflow: auto;">
							</div>
						</div>
						</div>
					</td>
					<td style="width: 5px;">
					</td>
					<td style="width: 15%; text-align: left; vertical-align: top; background: #eee; display: none;">
						<div class="container rounded" style="background: #fff; width: 98%; margin-left: auto; margin-right: auto;">
						<div>
							<h2 style="display: none;"><span id="running_apps_gettext">&nbsp;</span></h2>

							<div id="runningAppsContainer" style="overflow: auto;">
							</div>
						</div>
						</div>
					</td>
					<td style="width: 5px; display: none;">
					</td>
					<td style="text-align: left; vertical-align: top; background: #eee;">
						<div id="fileManagerWrap" class="container rounded" style="background: #fff; width: 98%; margin-left: auto; margin-right: auto;">
						<div>
							<h2 style="display: none;"><span id="my_files_gettext">&nbsp;</span></h2>

							<div id="fileManagerContainer">
							</div>
						</div>
						</div>
					</td>
				</tr>
			</table>

			<div id="applicationsAppletContainer" style="display: none;">
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

		<div id="mainWrap">
			<div id="headerWrap">
			</div>

			<div class="spacer"></div>

			<div id="pageWrap">
				<div id="loginBox" class="rounded" style="display: none;">
					<table style="width: 100%; margin-left: auto; margin-right: auto;" border="0" cellspacing="0" cellpadding="0">
						<tr>
							<td style="width: 300px; text-align: left; vertical-align: top;">
								<img src="media/image/ulteo.png" alt="" title="" />
							</td>
							<td style="width: 10px;">
							</td>
							<td style="text-align: center; vertical-align: top;">
								<div id="loginForm" class="rounded">
									<script type="text/javascript">
									Event.observe(window, 'load', function() {
										setTimeout(function() {
<?php
if (! defined('SESSIONMANAGER_HOST') && (! isset($wi_sessionmanager_host) || $wi_sessionmanager_host == ''))
	echo 'if ($(\'sessionmanager_host\') && $(\'sessionmanager_host\').visible()) $(\'sessionmanager_host\').focus();';
elseif ((isset($users) && $users !== false) || (isset($wi_user_login) && $wi_user_login != ''))
	echo 'if ($(\'user_password\') && $(\'user_password\').visible()) $(\'user_password\').focus();';
else
	echo 'if ($(\'user_login\') && $(\'user_login\').visible()) $(\'user_login\').focus();';
?>

checkSessionMode();
										}, 1500);
									});</script>
									<form id="startsession" method="post" onsubmit="return startSession();">
										<table style="width: 100%; margin-left: auto; margin-right: auto; padding-top: 10px;" border="0" cellspacing="0" cellpadding="5">
											<tr style="<?php echo ((defined('SESSIONMANAGER_HOST'))?'display: none;':'') ?>">
												<td style="width: 22px; text-align: right; vertical-align: middle;">
													<img src="media/image/icons/sessionmanager.png" alt="" title="" />
												</td>
												<td style="text-align: left; vertical-align: middle;">
													<strong><span id="session_manager_gettext">&nbsp;</span></strong>
												</td>
												<td style="text-align: right; vertical-align: middle;">
													<input type="text" id="sessionmanager_host" value="<?php echo $wi_sessionmanager_host; ?>" onchange="checkLogin();" onkeyup="checkLogin();" />
													<script type="text/javascript">Event.observe(window, 'load', function() {
														setTimeout(function() {
															if ($('sessionmanager_host').value == '') {
																$('sessionmanager_host').style.color = 'grey';
																$('sessionmanager_host').value = i18n.get('sessionmanager_host_example');
																if ($('sessionmanager_host') && $('sessionmanager_host').visible())
																	setCaretPosition($('sessionmanager_host'), 0);
															}
															Event.observe($('sessionmanager_host'), 'focus', function() {
																if ($('sessionmanager_host').value == i18n.get('sessionmanager_host_example')) {
																	$('sessionmanager_host').style.color = 'black';
																	$('sessionmanager_host').value = '';
																}
															});
															Event.observe($('sessionmanager_host'), 'blur', function() {
																if ($('sessionmanager_host').value == '') {
																	$('sessionmanager_host').style.color = 'grey';
																	$('sessionmanager_host').value = i18n.get('sessionmanager_host_example');
																}
															});
														}, 1500);
													});</script>
												</td>
											</tr>
											<tr>
												<td style="width: 22px; text-align: right; vertical-align: middle;">
													<img src="media/image/icons/user_login.png" alt="" title="" />
												</td>
												<td style="text-align: left; vertical-align: middle;">
													<strong><span id="login_gettext">&nbsp;</span></strong>
												</td>
												<td style="text-align: right; vertical-align: middle;">
													<?php
														if (! defined('SESSIONMANAGER_HOST') || $users === false) {
													?>
													<input type="text" id="user_login" value="<?php echo $wi_user_login; ?>" onchange="checkLogin();" onkeyup="checkLogin();" onkeydown="if (typeof window.event != 'undefined' && window.event.keyCode == 13) return startSession();" />
													<?php
														} else {
													?>
													<select id="user_login" onchange="checkLogin();" onkeyup="checkLogin();">
													<?php
														foreach ($users as $login => $displayname)
															echo '<option value="'.$login.'"'.(($login == $wi_user_login)?'selected="selected"':'').'>'.$login.' ('.$displayname.')</option>'."\n";
													?>
													</select>
													<?php
														}
													?>
													<span id="user_login_local" style="display: none; color: grey; font-style: italic;"></span>
												</td>
											</tr>
											<tr id="password_row">
												<td style="text-align: right; vertical-align: middle;">
													<img src="media/image/icons/user_password.png" alt="" title="" />
												</td>
												<td style="text-align: left; vertical-align: middle;">
													<strong><span id="password_gettext">&nbsp;</span></strong>
												</td>
												<td style="text-align: right; vertical-align: middle;">
													<input type="password" id="user_password" value="" onkeydown="if (typeof window.event != 'undefined' && window.event.keyCode == 13) return startSession();" />
												</td>
											</tr>
										</table>
<?php
	if ($debug_mode) {
?>
										<script type="text/javascript">
											Event.observe(window, 'load', function() {
												switchSettings();
											});
										</script>
<?php
	}
?>
										<div id="advanced_settings" style="display: none;">
											<table style="width: 100%; margin-left: auto; margin-right: auto;" border="0" cellspacing="0" cellpadding="5">
												<tr<?php if (OPTION_SHOW_USE_LOCAL_CREDENTIALS === false) echo ' style="display: none;"';?>>
													<td style="text-align: right; vertical-align: middle;">
														<img src="media/image/icons/use_local_credentials.png" alt="" title="" />
													</td>
													<td style="text-align: left; vertical-align: middle;">
														<strong><span id="use_local_credentials_gettext">&nbsp;</span></strong>
													</td>
													<td style="text-align: right; vertical-align: middle;">
														<input class="input_radio" type="radio" id="use_local_credentials_true" name="use_local_credentials" value="1"<?php if ($wi_use_local_credentials == 1) echo ' checked="checked"'; ?><?php if (defined('OPTION_FORCE_USE_LOCAL_CREDENTIALS')) echo ' disabled="disabled"'; ?> onchange="checkLogin();" onclick="checkLogin();" /> <span id="use_local_credentials_yes_gettext">&nbsp;</span>
														<input class="input_radio" type="radio" id="use_local_credentials_false" name="use_local_credentials" value="0"<?php if ($wi_use_local_credentials == 0) echo ' checked="checked"'; ?><?php if (defined('OPTION_FORCE_USE_LOCAL_CREDENTIALS')) echo ' disabled="disabled"'; ?> onchange="checkLogin();" onclick="checkLogin();" /> <span id="use_local_credentials_no_gettext">&nbsp;</span>
													</td>
												</tr>
												<tr>
													<td style="width: 22px; text-align: right; vertical-align: middle;">
														<img src="media/image/icons/session_mode.png" alt="" title="" />
													</td>
													<td style="text-align: left; vertical-align: middle;">
														<strong><span id="mode_gettext">&nbsp;</span></strong>
													</td>
													<td style="text-align: right; vertical-align: middle;">
														<select id="session_mode" onchange="checkSessionMode();" onclick="checkSessionMode();" <?php if (defined('OPTION_FORCE_SESSION_MODE')) echo ' disabled="disabled"';?>>
															<option id="mode_desktop_gettext" value="desktop"<?php if ($wi_session_mode == 'desktop') echo ' selected="selected"'; ?>></option>
															<option id="mode_portal_gettext" value="applications"<?php if ($wi_session_mode == 'applications') echo ' selected="selected"'; ?>></option>
														</select>
													</td>
												</tr>
												<tr id="advanced_settings_desktop">
													<td style="text-align: right; vertical-align: middle;">
														<img src="media/image/icons/settings_desktop_fullscreen.png" alt="" title="" />
													</td>
													<td style="text-align: left; vertical-align: middle;">
														<strong><span id="fullscreen_gettext">&nbsp;</span></strong>
													</td>
													<td style="text-align: right; vertical-align: middle;">
														<input class="input_radio" type="radio" id="desktop_fullscreen_true" name="desktop_fullscreen" value="1"<?php if ($wi_desktop_fullscreen == 1) echo ' checked="checked"'; ?><?php if (defined('OPTION_FORCE_FULLSCREEN')) echo ' disabled="disabled"'; ?>/> <span id="fullscreen_yes_gettext">&nbsp;</span>
														<input class="input_radio" type="radio" id="desktop_fullscreen_false" name="desktop_fullscreen" value="0"<?php if ($wi_desktop_fullscreen == 0) echo ' checked="checked"'; ?><?php if (defined('OPTION_FORCE_FULLSCREEN')) echo ' disabled="disabled"'; ?> /> <span id="fullscreen_no_gettext">&nbsp;</span>
													</td>
												</tr>
												<tr>
													<td style="text-align: right; vertical-align: middle;">
														<img src="media/image/icons/session_language.png" alt="" title="" />
													</td>
													<td style="text-align: left; vertical-align: middle;">
														<strong><span id="language_gettext">&nbsp;</span></strong>
													</td>
													<td style="text-align: right; vertical-align: middle;">
														<span style="margin-right: 5px;"><img id="session_language_flag" /></span>
														<script type="text/javascript">
															Event.observe(window, 'load', function() {
																translateInterface($('session_language').value);
																updateFlag($('session_language').value);
															});
														</script>
														<select id="session_language" onchange="translateInterface($('session_language').value); updateFlag($('session_language').value);" onkeyup="translateInterface($('session_language').value); updateFlag($('session_language').value);"<?php if (OPTION_FORCE_LANGUAGE === true) echo ' disabled="disabled"';?>>
															<?php
																foreach ($languages as $language)
																	echo '<option value="'.$language['id'].'" style="background: url(\'media/image/flags/'.$language['id'].'.png\') no-repeat right;"'.(($language['id'] == $user_language || $language['id'] == substr($user_language, 0, 2))?' selected="selected"':'').'>'.$language['english_name'].((array_key_exists('local_name', $language))?' - '.$language['local_name']:'').'</option>';
															?>
														</select>
													</td>
												</tr>
												<tr<?php if ($rdp_input_unicode == 'unicode') echo ' style="display: none;"';?>>
													<td style="text-align: right; vertical-align: middle;">
														<img src="media/image/icons/keyboard_layout.png" alt="" title="" />
													</td>
													<td style="text-align: left; vertical-align: middle;">
														<strong><span id="keyboard_layout_gettext">&nbsp;</span></strong>
													</td>
													<td style="text-align: right; vertical-align: middle;">
														<select id="session_keymap"<?php if (OPTION_FORCE_KEYMAP === true) echo ' disabled="disabled"';?>>
															<?php
																foreach ($keymaps as $keymap)
																	echo '<option value="'.$keymap['id'].'"'.(($keymap['id']==$user_keymap)?' selected="selected"':'').'>'.$keymap['name'].'</option>';
															?>
														</select>
													</td>
												</tr>
<?php
	if ($debug_mode) {
?>
												<tr>
													<td style="text-align: right; vertical-align: middle;">
														<img src="media/image/icons/debug.png" alt="" title="" />
													</td>
													<td style="text-align: left; vertical-align: middle;">
														<strong><span id="debug_gettext">&nbsp;</span></strong>
													</td>
													<td style="text-align: right; vertical-align: middle;">
														<input class="input_radio" type="radio" id="debug_true" name="debug" value="1"<?php if ($wi_debug == 1) echo ' checked="checked"'; ?> /> <span id="debug_yes_gettext">&nbsp;</span>
														<input class="input_radio" type="radio" id="debug_false" name="debug" value="0"<?php if ($wi_debug == 0) echo ' checked="checked"'; ?> /> <span id="debug_no_gettext">&nbsp;</span>
													</td>
												</tr>
<?php
	}
?>
											</table>
										</div>
										<table style="width: 100%; margin-left: auto; margin-right: auto; margin-top: 35px; padding-bottom: 10px;" border="0" cellspacing="0" cellpadding="5">
											<tr style="height: 40px;">
												<td style="text-align: left; vertical-align: bottom;">
													<span id="advanced_settings_status" style="position: relative; left: 20px;"><img src="media/image/show.png" width="12" height="12" alt="" title="" /></span><input style="padding-left: 18px;" type="button" id="advanced_settings_gettext" value="" onclick="switchSettings(); return false;" />
												</td>
												<td style="text-align: right; vertical-align: bottom;">
													<span id="submitButton"><input type="submit" id="connect_gettext" value="" /></span>
													<span id="submitLoader" style="display: none;"><img src="media/image/loader.gif" width="24" height="24" alt="" title="" /></span>
												</td>
											</tr>
										</table>
									</form>
								</div>
							</td>
						</tr>
					</table>
				</div>
			</div>

			<div class="spacer"></div>

			<div id="footerWrap">
			</div>
		</div>
	</body>
</html>
