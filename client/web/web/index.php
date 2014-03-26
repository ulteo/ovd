<?php
/**
 * Copyright (C) 2010-2014 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2011
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2010-2011
 * Author Julien LANGLOIS <julien@ulteo.com> 2011, 2012
 * Author Omar AKHAM <oakham@ulteo.com> 2011
 * Author David PHAM-VAN <d.pham-van@ulteo.com> 2012, 2013, 2014
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

$big_image_map = false;
if (get_ie_version() > 7 && file_exists(WEB_CLIENT_ROOT . "/media/image/uovd.png") &&
                            file_exists(WEB_CLIENT_ROOT . "/media/style/images.css")) {
	$big_image_map = true;
}

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
	$wi_use_local_credentials = ($_COOKIE['ovd-client']['use_local_credentials'] == "true") ? 1:0;

if (! defined('OPTION_SHOW_USE_LOCAL_CREDENTIALS'))
	define('OPTION_SHOW_USE_LOCAL_CREDENTIALS', false);

$force_sso = false;
$wi_remote_user_login = '';
if (defined('OPTION_FORCE_SSO') && OPTION_FORCE_SSO === true) {
	if (array_key_exists('REMOTE_USER', $_SERVER)) {
		$wi_remote_user_login = $_SERVER['REMOTE_USER'];
		$force_sso = true;
		$wi_use_local_credentials = 0;
	}
}

$wi_session_mode = 'desktop';
if (defined('OPTION_FORCE_SESSION_MODE'))
	$wi_session_mode = OPTION_FORCE_SESSION_MODE;
elseif (isset($_COOKIE['ovd-client']['session_mode']))
	$wi_session_mode = (string)$_COOKIE['ovd-client']['session_mode'];

$wi_session_type = 'java';
if (isset($_COOKIE['ovd-client']['session_type']))
	$wi_session_type = (string)$_COOKIE['ovd-client']['session_type'];

if (OPTION_FORCE_LANGUAGE !== true && isset($_COOKIE['ovd-client']['session_language'])) {
	$lang = (string)$_COOKIE['ovd-client']['session_language'];
	if (language_is_supported($languages, $lang))
		$user_language = $lang;
}

$java_installed = true;
if (defined('RDP_PROVIDER_JAVA_INSTALLED'))
	$java_installed = RDP_PROVIDER_JAVA_INSTALLED;

$html5_installed = false;
if (defined('RDP_PROVIDER_HTML5_INSTALLED'))
	$html5_installed = RDP_PROVIDER_HTML5_INSTALLED;

list($translations, $js_translations) = get_available_translations($user_language);

if (OPTION_FORCE_KEYMAP !== true && isset($_COOKIE['ovd-client']['session_keymap'])) {
	$cookie_keymap = (string)$_COOKIE['ovd-client']['session_keymap'];
	if (language_is_supported($keymaps, $cookie_keymap))
		$user_keymap = $cookie_keymap;
}

$wi_desktop_fullscreen = 0;
if (defined('OPTION_FORCE_FULLSCREEN'))
	$wi_desktop_fullscreen = ((OPTION_FORCE_FULLSCREEN==true)?1:0);
elseif (isset($_COOKIE['ovd-client']['desktop_fullscreen']))
	$wi_desktop_fullscreen = ($_COOKIE['ovd-client']['desktop_fullscreen'] == "true" ) ? 1:0;

$wi_debug = 1;
if (isset($_COOKIE['ovd-client']['debug']))
	$wi_debug = ($_COOKIE['ovd-client']['debug'] == "true") ? 1:0;

$rdp_input_method = null;
if (defined('OPTION_FORCE_INPUT_METHOD') && OPTION_FORCE_INPUT_METHOD !== true && isset($_COOKIE['ovd-client']['session_input_method']))
	$rdp_input_method = (string)$_COOKIE['ovd-client']['session_input_method'];
else
	if(defined('RDP_INPUT_METHOD'))
		$rdp_input_method = RDP_INPUT_METHOD;

$show_input_method = false;
if (defined('OPTION_SHOW_INPUT_METHOD'))
	$show_input_method = OPTION_SHOW_INPUT_METHOD;

$force_input_method = false;
if (defined('OPTION_FORCE_INPUT_METHOD'))
	$force_input_method = OPTION_FORCE_INPUT_METHOD;

$use_proxy = false;
if (defined('OPTION_USE_PROXY') && is_bool(OPTION_USE_PROXY)) {
 $use_proxy = OPTION_USE_PROXY;
}

$local_integration = (defined('PORTAL_LOCAL_INTEGRATION') && (PORTAL_LOCAL_INTEGRATION === true));

$confirm_logout = OPTION_CONFIRM_LOGOUT;

if ($debug_mode === false && array_key_exists('debug', $_REQUEST)) {
	$debug_mode = true;
	$big_image_map = false;
}

$headers = apache_request_headers();
$gateway_first = (is_array($headers) && array_key_exists('OVD-Gateway', $headers));

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>Ulteo Open Virtual Desktop</title>

		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

		<meta http-equiv="X-UA-Compatible" content="IE=Edge" />

		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" />

		<link rel="shortcut icon" href="media/image/favicon.ico" />
		<link rel="shortcut icon" type="image/png" href="media/image/favicon.png" />

<?php if (file_exists(WEB_CLIENT_ROOT . "/media/style/webclient.css") && $debug_mode != true) { ?>
		<link rel="stylesheet" type="text/css" href="media/style/webclient.css" />
<?php } else { ?>
		<link rel="stylesheet" type="text/css" href="media/style/images.css" />
		<link rel="stylesheet" type="text/css" href="media/style/common.css" />
		<link rel="stylesheet" type="text/css" href="media/style/dialogs.css" />
		<link rel="stylesheet" type="text/css" href="media/style/login.css" />
		<link rel="stylesheet" type="text/css" href="media/style/notifications.css" />
		<link rel="stylesheet" type="text/css" href="media/style/desktop.css" />
		<link rel="stylesheet" type="text/css" href="media/style/portal.css" />
		<link rel="stylesheet" type="text/css" href="media/style/rtl.css" />
		<link rel="stylesheet" type="text/css" href="media/style/responsive.css" />
		<link rel="stylesheet" type="text/css" href="media/style/menu.css" />
		<link rel="stylesheet" type="text/css" href="media/style/framework.css" />
<?php } ?>
<?php if (!$big_image_map) { ?>
		<link rel="stylesheet" type="text/css" href="media/style/images_files.css" />
<?php } ?>
<?php if (file_exists(WEB_CLIENT_ROOT . "/media/custom/custom.css")) {
	$custom_css = true;
?>
		<link rel="stylesheet" type="text/css" href="media/custom/custom.css" />
<?php } ?>

		<script type="text/javascript" src="media/script/lib/jquery/jquery.js" charset="utf-8"></script>

<?php if (file_exists(WEB_CLIENT_ROOT . "/media/script/uovd.js") && $debug_mode != true) { ?>
		<script type="text/javascript" src="media/script/uovd.js" charset="utf-8"></script>
<?php } else { ?>
		<script type="text/javascript" src="media/script/uovd/base.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/uovd/application.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/uovd/server/base.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/uovd/server/rdp.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/uovd/server/webapps.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/uovd/session.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/uovd/session_management.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/uovd/provider/rdp/base.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/uovd/provider/http/base.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/uovd/provider/http/direct.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/uovd/provider/http/proxy.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/uovd/provider/java.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/uovd/provider/applications/application_instance.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/uovd/provider/applications/base.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/uovd/provider/applications/html5.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/uovd/provider/applications/java.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/uovd/provider/applications/webapps.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/uovd/provider/rdp/html5.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/uovd/provider/rdp/html5/input_keyboard.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/uovd/provider/rdp/html5/input_mouse.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/uovd/provider/rdp/html5/clipboard_handler.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/uovd/provider/rdp/html5/print_handler.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/uovd/provider/rdp/html5/seamless_handler.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/uovd/provider/rdp/html5/seamless_window_factory.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/uovd/provider/rdp/html5/seamless_window_manager.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/uovd/provider/rdp/html5/seamless_icon.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/uovd/provider/rdp/html5/guacamole/base64.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/uovd/provider/rdp/html5/guacamole/exttunnel.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/uovd/provider/rdp/html5/guacamole/tmouse.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/uovd/provider/rdp/html5/guacamole/keyboard.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/uovd/provider/rdp/html5/guacamole/layer.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/uovd/provider/rdp/html5/guacamole/guacamole.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/uovd/provider/rdp/html5/guacamole/encodings.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/uovd/provider/rdp/html5/guacamole/nkeyboard.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/uovd/provider/webapps/base.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/uovd/provider/webapps/jsonp.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/uovd/provider/webapps/proxy.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/uovd/provider/webapps/direct.js" charset="utf-8"></script>
<?php } ?>

<?php if (file_exists(WEB_CLIENT_ROOT . "/media/script/webclient.js") && $debug_mode != true) { ?>
		<script type="text/javascript" src="media/script/webclient.js" charset="utf-8"></script>
<?php } else { ?>
		<script type="text/javascript" src="media/script/webclient/timezones.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/webclient/ajaxplorer.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/webclient/start_app.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/webclient/application_counter.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/webclient/desktop_container.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/webclient/menu_container.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/webclient/seamless_launcher.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/webclient/seamless_taskbar.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/webclient/progress_bar.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/webclient/webapps_popup_launcher.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/webclient/ui.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/webclient/news.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/webclient/logger.js" charset="utf-8"></script>
<?php } ?>
		<script type="text/javascript" src="media/script/webclient/uovd_int_client.js" charset="utf-8"></script>

		<script type="text/javascript">
			window.ovd = {};

			/* Options from PHP to JS */
			window.ovd.defaults = {};
			window.ovd.defaults.sessionmanager              = <?php echo defined('SESSIONMANAGER_HOST') ? "'".SESSIONMANAGER_HOST."'" : 'undefined'; ?>;
			window.ovd.defaults.gateway                     = <?php echo $gateway_first === true ? 'true' : 'false'; ?>;
			window.ovd.defaults.keymap_autodetect           = <?php echo defined('OPTION_KEYMAP_AUTO_DETECT') && OPTION_KEYMAP_AUTO_DETECT === true && !isset($_COOKIE['ovd-client']['session_keymap']) ? 'true' : 'false'; ?>;
			window.ovd.defaults.use_proxy                   = <?php echo $use_proxy === true ? 'true' : 'false'; ?>;
			window.ovd.defaults.user_keymap                 = <?php echo isset($user_keymap) ? "'".$user_keymap."'" : 'undefined'; ?>;
			window.ovd.defaults.rdp_input_method            = <?php echo $rdp_input_method !== null ? '\''.$rdp_input_method.'\'' : 'undefined'; ?>;
			window.ovd.defaults.local_integration           = <?php echo $local_integration === true ? 'true' : 'false'; ?>;
			window.ovd.defaults.debug_mode                  = <?php echo isset($debug_mode) && $debug_mode === true ? 'true' : 'false'; ?>;
			window.ovd.defaults.client_language             = <?php echo isset($user_language) ? "'".$user_language."'" : 'undefined'; ?>;
			window.ovd.defaults.confirm_logout              = <?php echo isset($confirm_logout) ? "'".$confirm_logout."'" : 'undefined' ; ?>;
			window.ovd.defaults.java_installed              = <?php echo $java_installed === true ? 'true' : 'false'; ?>;
			window.ovd.defaults.html5_installed             = <?php echo $html5_installed === true ? 'true' : 'false'; ?>;

			/* "Forced" options */
			window.ovd.defaults.force_use_local_credentials = <?php echo defined('OPTION_FORCE_USE_LOCAL_CREDENTIALS') && OPTION_FORCE_USE_LOCAL_CREDENTIALS === true ? 'true' : 'false'; ?>;
			window.ovd.defaults.force_fullscreen            = <?php echo defined('OPTION_FORCE_FULLSCREEN') && OPTION_FORCE_FULLSCREEN === true ? 'true' : 'false'; ?>;
			window.ovd.defaults.force_session_mode          = <?php echo defined('OPTION_FORCE_SESSION_MODE') ? "'".OPTION_FORCE_SESSION_MODE."'" : 'undefined'; ?>;
			window.ovd.defaults.force_language              = <?php echo defined('OPTION_FORCE_LANGUAGE') && OPTION_FORCE_LANGUAGE === true ? 'true' : 'false'; ?>;
			window.ovd.defaults.force_input_method          = <?php echo defined('OPTION_FORCE_INPUT_METHOD') && OPTION_FORCE_INPUT_METHOD === true ? 'true' : 'false'; ?>;
			window.ovd.defaults.force_keymap                = <?php echo defined('OPTION_FORCE_KEYMAP') && OPTION_FORCE_KEYMAP === true ? 'true' : 'false'; ?>;
			window.ovd.defaults.force_sso                   = <?php echo $force_sso === true ? 'true' : 'false'; ?>;

			var i18n = {};
			<?php
				foreach ($js_translations as $id => $string)
					echo 'i18n[\''.$id.'\'] = \''.str_replace('\'', '\\\'', $string).'\';'."\n";
			?>

			var i18n_tmp = {};
			<?php
				foreach ($translations as $id => $string)
					echo 'i18n_tmp[\''.$id.'\'] = \''.str_replace('\'', '\\\'', $string).'\';'."\n";
			?>

		</script>
	</head>

	<body>
		<noscript>
			<div class="boxMessage">
				<div class="shadowBox">
					<div class="boxLogo">
						<div class="image_ulteo-small_png"></div>
					</div>
					<p><strong>JavaScript must be enabled</strong></p>
					<div class="boxLogo">
						<div class="image_error_png"></div>
					</div>
					<p>
						<?php echo str_replace(
							array('[A]', '[/A]'),
							array('<a href="">', '</a>'),
							_('JavaScript must be enabled in order for you to use Ulteo OVD. However, it seems JavaScript is either disabled or not supported by your browser. To use OVD Web Client, enable JavaScript by changing your browser options, then [A]try again[/A].'));
						?>
					</p>
				</div>
			</div>
		</noscript>

		<div id="overlay" style="display: none;">
			<div id="lock" style="display: none;"></div>
			<div id="systemTest" class="shadowBox" style="display: none;">
				<div class="boxLogo">
					<div class="image_rotate_gif"></div>
				</div>
				<h1><span id="system_compatibility_check_1_gettext">&nbsp;</span></h1>
				<p id="system_compatibility_check_2_gettext">&nbsp;</p>
				<p id="system_compatibility_check_3_gettext">&nbsp;</p>
			</div>

			<div id="systemTestError" class="shadowBox" style="display: none;">
				<div class="boxLogo">
					<div class="image_error_png"></div>
				</div>
				<h1><span id="system_compatibility_error_1_gettext">&nbsp;</span></h1>
				<p id="system_compatibility_error_5_gettext">&nbsp;</p>
			</div>

			<div id="news" class="shadowBox" style="display: none;">
				<div class="boxLogo">
					<div class="image_news_png"></div>
					<br><a id="newsHideLink" href="javascript:;"><span id="close_gettext">&nbsp;</span></a>
				</div>
				<h1 id="newsTitle"></h1>
				<p id="newsContent"></p>
			</div>
		</div>

		<div id="splashContainer" class="boxMessage" style="display: none;">
			<div id="splashContainerContent" class="shadowBox">
				<div class="boxLogo">
					<div class="image_ulteo_png"></div>
				</div>
				<h1 class="loadling_text" style="display: none;" id="loading_ovd_gettext">&nbsp;</h1>
				<h1 class="loadling_text" style="display: none;" id="unloading_ovd_gettext">&nbsp;</h1>
				<div class="boxLogo">
					<div class="image_rotate_gif"></div>
				</div>
				<div id="progressBar"><div id="progressBarContent"></div></div>
			</div>
		</div>

		<div id="endContainer" class="boxMessage" style="display: none;">
			<div id="endContainerContent" class="shadowBox">
				<div class="boxLogo">
					<div class="image_ulteo_png"></div>
				</div>
				<div id="endContent"></div>
			</div>
		</div>

		<div id="sessionContainer" style="display: none;">
			<div id="applicationsHeader">
				<div id="headerLogo">
					<div class="image_ulteo-small_png"></div>
				</div>
				<div id="newsList"></div>
				<a id="logout_link" href="javascript:;">
					<div class="image_logout_png" style="display:inline-block"></div>
					<br /><span id="logout_gettext">&nbsp;</span>
				</a>
				<a id="suspend_link" href="javascript:;">
					<div class="image_suspend_png" style="display:inline-block"></div>
					<br /><span id="suspend_gettext">&nbsp;</span>
				</a>
				<h1><span id="user_displayname">&nbsp;</span><span id="welcome_gettext" style="display: none;">&nbsp;</span></h1>
				<div class="collapse"></div>
			</div>

			<div id="appsContainer"></div>
			<div id="fileManagerContainer" style="display:none">
				<h2><span id="my_files_gettext">&nbsp;</span></h2>
			</div>

			<div id="fullScreenMessage" class="boxMessage" style="display: none;">
				<div id="fullScreenMessageContainer" class="shadowBox">
					<div class="boxLogo">
						<div class="image_ulteo_png"></div>
					</div>
					<p>
						<span class="desktop_fullscreen_text" id="desktop_fullscreen_text1_gettext">&nbsp;</span>
						<br /><br />
						<span class="desktop_fullscreen_text" id="desktop_fullscreen_text2_gettext">&nbsp;</span>
					</p>
				</div>
			</div>
			<div id="desktopContainer"></div>
			<div id="windowsContainer"></div>
			<div id="menuContainer"></div>
		</div>

		<div id="main">
			<div id="header"></div>
			<div id="page">
				<div id="loginBox" style="display: none;">
					<div id="loginBoxLogo" class="image_ulteo_png"></div>
					<div id="loginBoxLogoSmall" class="image_ulteo-small_png"></div>
					<div id="loginForm">
						<form id="startsession" action="javascript:;" method="post">
							
							<div class="loginElement" style="<?php echo ((defined('SESSIONMANAGER_HOST'))?'display: none;':'') ?>">
								<label class="loginLabel" for="sessionmanager_host">
									<div class="image_sessionmanager_png"></div>
									<strong><span id="session_manager_gettext">&nbsp;</span></strong>
								</label>
								<div class="loginField">
									<input type="text" id="sessionmanager_host" value="<?php echo $wi_sessionmanager_host; ?>"/>
								</div>
							</div>
							
							<div class="loginElement">
								<label class="loginLabel" for="user_login">
									<div class="image_user_login_png"></div>
									<strong><span id="login_gettext">&nbsp;</span></strong>
								</label>
								<div class="loginField">
									<?php
										if (! defined('SESSIONMANAGER_HOST') || $users === false || $force_sso === true) {
									?>
									<input type="text" id="user_login" value="<?php echo $wi_user_login; ?>"/>
									<?php
										} else {
									?>
									<select id="user_login">
									<?php
										foreach ($users as $login => $displayname)
											echo '<option value="'.$login.'"'.(($login == $wi_user_login)?' selected="selected"':'').'>'.$login.' ('.$displayname.')</option>'."\n";
									?>
									</select>
									<?php
										}
									?>
								</div>
							</div>
								
							<div class="loginElement">
								<label class="loginLabel" for="user_password">
									<div class="image_user_password_png"></div>
									<strong><span id="password_gettext">&nbsp;</span></strong>
								</label>
								<div class="loginField">
									<input type="password" id="user_password" value=""/>
								</div>
							</div>

							<div class="loginElement">
								<label class="loginLabel" for="user_login_detected">
									<div class="image_user_login_png"></div>
									<strong><span id="login_detected_gettext">&nbsp;</span></strong>
								</label>
								<div class="loginField">
									<input type="text" id="user_login_detected" disabled="disabled" value="<?php echo $wi_remote_user_login; ?>"/>
								</div>
							</div>
							
							<div id="advanced_settings" style="display: none;">
								
								<div class="loginElement" <?php if (OPTION_SHOW_USE_LOCAL_CREDENTIALS === false) echo ' style="display: none;"';?>>
									<label class="loginLabel" for="use_local_credentials_true">
										<div class="image_use_local_credentials_png"></div>
										<strong><span id="use_local_credentials_gettext">&nbsp;</span></strong>
									</label>
									<div class="loginField">
										<?php short_list_field("use_local_credentials", defined('OPTION_FORCE_USE_LOCAL_CREDENTIALS'), $wi_use_local_credentials, array(
											"1"=>"use_local_credentials_yes_gettext", 
											"0"=>"use_local_credentials_no_gettext")); 
										?>
									</div>
								</div>
								
								<div class="loginElement">
									<label class="loginLabel" for="session_mode">
										<div class="image_session_mode_png"></div>
										<strong><span id="mode_gettext">&nbsp;</span></strong>
									</label>
									<div class="loginField">
										<?php long_list_field("session_mode", defined('OPTION_FORCE_SESSION_MODE'), $wi_session_mode, array(
											"desktop"=>"mode_desktop_gettext", 
											"applications"=>"mode_portal_gettext")); 
										?>
									</div>
								</div>
								
								<div class="loginElement">
									<label class="loginLabel" for="rdp_mode">
										<div class="image_session_mode_png"></div>
										<strong><span id="rdp_mode_gettext">Type&nbsp;</span></strong>
									</label>
									<div class="loginField">
										<?php
											$rdp_mode_list = array();
											if ($java_installed) {
												$rdp_mode_list["java"] = "Java";
											}
											if ($html5_installed) {
												$rdp_mode_list["html5"] = "HTML5";
											}
											long_list_field("rdp_mode", false, $wi_session_type, $rdp_mode_list); 
										?>
									</div>
								</div>
								
								<div class="loginElement">
									<label class="loginLabel" for="desktop_fullscreen">
										<div class="image_settings_desktop_fullscreen_png"></div>
										<strong><span id="fullscreen_gettext">&nbsp;</span></strong>
									</label>
									<div class="loginField">
										<?php short_list_field("desktop_fullscreen", defined("OPTION_FORCE_FULLSCREEN"), $wi_desktop_fullscreen, array("1"=>"fullscreen_yes_gettext", "0"=>"fullscreen_no_gettext")); ?>
									</div>
								</div>
								
								<div class="loginElement">
									<label class="loginLabel" for="session_language">
										<div class="image_session_language_png"></div>
										<strong><span id="language_gettext">&nbsp;</span></strong>
									</label>
									<div class="loginField">
										<select id="session_language" <?php if (OPTION_FORCE_LANGUAGE === true) echo ' disabled="disabled"';?>>
											<?php
												$browser_languages = detectBrowserLanguage($languages);
												if (count($browser_languages) > 0) {
													foreach ($browser_languages as $browser_language) {
														foreach ($languages as $language) {
															if ($browser_language == $language['id']) {
																echo '<option value="'.$language['id'].'" style="background-image: url(media/image/flags/'.$language['id'].'.png)"'.(($language['id'] == $user_language || $language['id'] == substr($user_language, 0, 2))?'  selected="selected"':'').'>'.$language['english_name'].((array_key_exists('local_name', $language))?' - '.$language['local_name']:'').'</option>';
																break;
															}
														}
													}
													echo '<option disabled="disabled"></option>';
												}
												foreach ($languages as $language) {
													if (!in_array($language['id'], $browser_languages)) {
														echo '<option value="'.$language['id'].'" style="background-image: url(media/image/flags/'.$language['id'].'.png)"'.(($language['id'] == $user_language || $language['id'] == substr($user_language, 0, 2))?' selected="selected"':'').'>'.$language['english_name'].((array_key_exists('local_name', $language))?' - '.$language['local_name']:'').'</option>';
													}
												}
											?>
										</select>
									</div>
								</div>
								
								<div class="loginElement">
									<label class="loginLabel" for="session_keymap">
										<div class="image_keyboard_layout_png"></div>
										<strong><span id="keyboard_layout_gettext">&nbsp;</span></strong>
									</label>
									<div class="loginField">
										<select id="session_keymap"<?php if (OPTION_FORCE_KEYMAP === true) echo " disabled=\"disabled\"";?>>
											<?php
												foreach ($keymaps as $keymap)
													echo '<option value="'.$keymap['id'].'"'.(($keymap['id']==$user_keymap)?' selected="selected"':'').'>'.$keymap['name'].'</option>';
											?>
										</select>
									</div>
								</div>
							
								<div class="loginElement" <?php if ($show_input_method === false) echo ' style="display: none;"';?>>
									<label class="loginLabel" for="session_input_method">
										<div class="image_keyboard_layout_png"></div>
										<strong><span id="keyboard_config_gettext">&nbsp;</span></strong>
									</label>
									<div class="loginField">
											<?php long_list_field("session_input_method", $force_input_method === true, $rdp_input_method, array(
												"scancode"=>"keyboard_config_scancode_gettext", 
												"unicode"=>"keyboard_config_unicode_gettext",
												"unicode_local_ime"=>"keyboard_config_unicode_lime_gettext")); 
											?>
									</div>
								</div>
<?php
	if ($debug_mode) {
?>
									<div class="loginElement">
										<label class="loginLabel" for="session_input_method">
											<div class="image_debug_png"></div>
											<strong><span id="debug_gettext">&nbsp;</span></strong>
										</label>
										<div class="loginField">
											<?php short_list_field("debug", false, $wi_debug, array(
												"1"=>"debug_yes_gettext", 
												"0"=>"debug_no_gettext")); 
											?>
										</div>
									</div>
<?php
	}
?>
							</div>
							
							<div id="loginError"></div>
							
							<div class="loginElement">
								<div class="loginLabel">
									<span id="advanced_settings_status" class="image_show_png"></span><input type="button" id="advanced_settings_gettext" value=""/>
								</div>
								<div class="loginField">
									<span id="submitButton"><input type="submit" id="connect_gettext" value="" /></span>
									<span id="submitLoader" style="display: none;">
										<div class="image_rotate_gif"></div>
									</span>
								</div>
							</div>
							<div class="loginElement"></div>
						</form>
					</div>
				</div>
			</div>
			<div id="footer"></div>
<?php
	if (isset($custom_css) && $custom_css === true) {
		echo ("<p style=\"position:absolute;right:10px;bottom:0px;opacity:0.5;\">Powered by Ulteo</p>");
	}
?>
		</div>
	</body>
</html>
