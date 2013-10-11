<?php
/**
 * Copyright (C) 2011-2013 Ulteo SAS
 * http://www.ulteo.com
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2011
 * Author Julien LANGLOIS <julien@ulteo.com> 2012, 2013
 * Author David PHAM-VAN <d.pham-van@ulteo.com> 2012, 2013
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


if (array_key_exists('REQUEST_METHOD', $_SERVER) && $_SERVER['REQUEST_METHOD'] == 'POST') {
	$_SESSION['last_request'] = array();
	foreach($_POST as $k => $v)
		$_SESSION['last_request'][$k] = $v;
	
	header('Location: external.php');
	die();
}

if (array_key_exists('last_request', $_SESSION)) {
	foreach($_SESSION['last_request'] as $k => $v)
		$_REQUEST[$k] = $v;
	
	unset($_SESSION['last_request']);
}

$big_image_map = false;
if (get_ie_version() > 7 && file_exists(WEB_CLIENT_ROOT . "/media/image/uovd.png")) {
	$big_image_map = true;
}

if (OPTION_FORCE_LANGUAGE !== true && array_key_exists('language', $_REQUEST)) {
	$available_languages = get_available_languages();

	if (language_is_supported($available_languages, $_REQUEST['language'])) {
		$user_language = $_REQUEST['language'];
		if (OPTION_FORCE_KEYMAP !== true)
			$user_keymap = $user_language;
	}
}

list($translations, $js_translations) = get_available_translations($user_language);

if (array_key_exists('app', $_REQUEST)) {
	$order = array('id' => $_REQUEST['app']);

	if (array_key_exists('file', $_REQUEST)) {
		$args = array();
		$args['path'] = $_REQUEST['file'];
		$args['share'] = base64_decode($_REQUEST['file_share']);
		$args['type'] = $_REQUEST['file_type'];

		$order['file'] = $args;
	}

	$_SESSION['ovd-client']['start_app'][] = $order;
}

$rdp_provider = null;
if (array_key_exists('type', $_REQUEST)) {
	$rdp_provider = $_REQUEST['type'];
}

$rdp_input_unicode = null;
if (defined('RDP_INPUT_METHOD'))
	$rdp_input_unicode = RDP_INPUT_METHOD;

$use_proxy = false;
if (defined('OPTION_USE_PROXY') && is_bool(OPTION_USE_PROXY)) {
 $use_proxy = OPTION_USE_PROXY;
}

$local_integration = (defined('PORTAL_LOCAL_INTEGRATION') && (PORTAL_LOCAL_INTEGRATION === true));

$confirm_logout = OPTION_CONFIRM_LOGOUT;

if ($debug_mode === false && array_key_exists('debug', $_REQUEST))
	$debug_mode = true;

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

<?php if (file_exists(WEB_CLIENT_ROOT . "/media/style/webclient.css")) { ?>
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
<?php } ?>
<?php if (!$big_image_map) { ?>
		<link rel="stylesheet" type="text/css" href="media/style/images_files.css" />
<?php } ?>

		<script type="text/javascript" src="media/script/lib/jquery/jquery.js" charset="utf-8"></script>

<?php if (file_exists(WEB_CLIENT_ROOT . "/media/script/uovd.js")) { ?>
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
		<script type="text/javascript" src="media/script/uovd/provider/rdp/html5/seamless_handler.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/uovd/provider/rdp/html5/seamless_window_factory.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/uovd/provider/rdp/html5/seamless_window_manager.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/uovd/provider/rdp/html5/seamless_icon.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/uovd/provider/rdp/html5/guacamole/exttunnel.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/uovd/provider/rdp/html5/guacamole/tmouse.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/uovd/provider/rdp/html5/guacamole/keyboard.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/uovd/provider/rdp/html5/guacamole/layer.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/uovd/provider/rdp/html5/guacamole/guacamole.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/uovd/provider/rdp/html5/guacamole/encodings.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/uovd/provider/rdp/html5/guacamole/nkeyboard.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/uovd/provider/webapps/base.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/uovd/provider/webapps/jsonp.js" charset="utf-8"></script>
<?php } ?>

<?php if (file_exists(WEB_CLIENT_ROOT . "/media/script/webclient.js")) { ?>
		<script type="text/javascript" src="media/script/webclient.js" charset="utf-8"></script>
<?php } else { ?>
		<script type="text/javascript" src="media/script/webclient/timezones.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/webclient/ajaxplorer.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/webclient/start_app.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/webclient/application_counter.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/webclient/desktop_container.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/webclient/seamless_launcher.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/webclient/progress_bar.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/webclient/webapps_popup_launcher.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/webclient/ui.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/webclient/news.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/webclient/logger.js" charset="utf-8"></script>
<?php } ?>
		<script type="text/javascript" src="media/script/webclient/uovd_ext_client.js" charset="utf-8"></script>

		<script type="text/javascript">
			window.ovd = {};

			/* Options from PHP to JS */
			window.ovd.defaults = {};
			window.ovd.defaults.sessionmanager              = <?php echo defined('SESSIONMANAGER_HOST') ? "'".SESSIONMANAGER_HOST."'" : 'undefined'; ?>;
			window.ovd.defaults.gateway                     = <?php echo $gateway_first === true ? 'true' : 'false'; ?>;
			window.ovd.defaults.keymap_autodetect           = <?php echo defined('OPTION_KEYMAP_AUTO_DETECT') && OPTION_KEYMAP_AUTO_DETECT === true && !isset($_COOKIE['ovd-client']['session_keymap']) ? 'true' : 'false'; ?>;
			window.ovd.defaults.use_proxy                   = <?php echo $use_proxy === true ? 'true' : 'false'; ?>;
			window.ovd.defaults.keymap                      = <?php echo isset($user_keymap) ? "'".$user_keymap."'" : 'undefined'; ?>;
			window.ovd.defaults.rdp_input_method            = <?php echo $rdp_input_unicode !== null ? '\''.$rdp_input_unicode.'\'' : 'undefined'; ?>;
			window.ovd.defaults.local_integration           = <?php echo $local_integration === true ? 'true' : 'false'; ?>;
			window.ovd.defaults.debug_mode                  = <?php echo isset($debug_mode) && $debug_mode === true ? 'true' : 'false'; ?>;
			window.ovd.defaults.language                    = <?php echo isset($user_language) ? "'".$user_language."'" : 'undefined'; ?>;
			window.ovd.defaults.confirm_logout              = <?php echo isset($confirm_logout) ? "'".$confirm_logout."'" : 'undefined' ; ?>;
			window.ovd.defaults.rdp_provider                = <?php echo isset($rdp_provider) ? "'".$rdp_provider."'" : 'undefined'; ?>;
			window.ovd.defaults.java_installed              = <?php echo defined('RDP_PROVIDER_JAVA_INSTALLED') && RDP_PROVIDER_JAVA_INSTALLED === true ? 'true' : 'false'; ?>;
			window.ovd.defaults.html5_installed             = <?php echo defined('RDP_PROVIDER_HTML5_INSTALLED') && RDP_PROVIDER_HTML5_INSTALLED === true ? 'true' : 'false'; ?>;

			/* "Forced" options */
			window.ovd.defaults.force_use_local_credentials = <?php echo defined('OPTION_FORCE_USE_LOCAL_CREDENTIALS') && OPTION_FORCE_USE_LOCAL_CREDENTIALS === true ? 'true' : 'false'; ?>;
			window.ovd.defaults.force_fullscreen            = <?php echo defined('OPTION_FORCE_FULLSCREEN') && OPTION_FORCE_FULLSCREEN === true ? 'true' : 'false'; ?>;
			window.ovd.defaults.force_session_mode          = <?php echo defined('OPTION_FORCE_SESSION_MODE') ? "'".OPTION_FORCE_SESSION_MODE."'" : 'undefined'; ?>;
			window.ovd.defaults.force_language              = <?php echo defined('OPTION_FORCE_LANGUAGE') && OPTION_FORCE_LANGUAGE === true ? 'true' : 'false'; ?>;
			window.ovd.defaults.force_keymap                = <?php echo defined('OPTION_FORCE_KEYMAP') && OPTION_FORCE_KEYMAP === true ? 'true' : 'false'; ?>;

			/* Session params */
			<?php
				$params = array();
				$params['mode'] = '\''.$_REQUEST['mode'].'\'';
				$params['login'] = '\''.$_REQUEST['login'].'\'';
				$params['password'] = '\''.$_REQUEST['password'].'\'';
				$params['token'] = '\'\'';

				if (array_key_exists('token', $_REQUEST)) {
					$params['token'] = '\''.$_REQUEST['token'].'\'';
				}

				$params['app'] = '\'\'';
				$params['file'] = '\'\'';
				$params['file_type'] = '\'\'';
				$params['file_share'] = '\'\'';

				if($_REQUEST['mode'] == 'desktop' && array_key_exists('app', $_REQUEST)) {
					$params['app'] = '\''.$_REQUEST['app'].'\'';

					if (array_key_exists('file', $_REQUEST)) {
						$params['file'] = '\''.$_REQUEST['file'].'\'';
						$params['file_type'] = '\''.$_REQUEST['file_type'].'\'';
						$params['file_share'] = '\''.base64_decode($_REQUEST['file_share']).'\'';
					}
				}
			?>

			window.ovd.defaults.login         = <?php echo $params['login']; ?>;
			window.ovd.defaults.password      = <?php echo $params['password']; ?>;
			window.ovd.defaults.mode          = <?php echo $params['mode']; ?>;
			window.ovd.defaults.token         = <?php echo $params['token']; ?>;
			window.ovd.defaults.application   = <?php echo $params['app']; ?>;
			window.ovd.defaults.file_location = <?php echo $params['file']; ?>;
			window.ovd.defaults.file_type     = <?php echo $params['file_type']; ?>;
			window.ovd.defaults.file_path     = <?php echo $params['file_share']; ?>;

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

			applyTranslations(i18n_tmp);
		</script>
	</head>

	<body style="margin: 10px; background: #ddd; color: #333;">
		<noscript>
			<div class="finalErrorBox" style="width: 500px;">
				<table style="width: 100%;" border="0" cellspacing="1" cellpadding="3">
					<tr>
						<td style="text-align: left; vertical-align: middle;">
							<strong>JavaScript must be enabled</strong>
							<div style="margin-top: 15px;">
<?php echo str_replace(
	array('[A]', '[/A]'),
	array('<a href="">', '</a>'),
	_('JavaScript must be enabled in order for you to use Ulteo OVD. However, it seems JavaScript is either disabled or not supported by your browser. To use OVD Web Client, enable JavaScript by changing your browser options, then [A]try again[/A].'));
?>
							</div>
						</td>
						<td style="width: 32px; height: 32px; text-align: right; vertical-align: top;">
							<div class="image_error_png"></div>
						</td>
					</tr>
				</table>
				<div style="text-align:center;">
					<div class="image_ulteo-small_png" style="margin-left: auto; margin-right: auto;"></div>
				</div>
			</div>
		</noscript>

		<div id="notification" style="display: none;">
			<div id="error" style="display: none;"></div>
			<div id="ok" style="display: none;"></div>
			<div id="info" style="display: none;"></div>
		</div>

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
				<h1><span id="user_displayname">&nbsp;</span><span id="welcome_gettext" style="display: none;">&nbsp;</span></h1>
				<div id="newsList"></div>
				<a id="logout_link" href="javascript:;">
					<div class="image_logout_png" style="display:inline-block"></div>
					<br /><span id="logout_gettext">&nbsp;</span>
				</a>
				<a id="suspend_link" href="javascript:;">
					<div class="image_suspend_png" style="display:inline-block"></div>
					<br /><span id="suspend_gettext">&nbsp;</span>
				</a>
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

			<div id="applicationsContainer"></div>
			<div id="desktopContainer"></div>
			<div id="windowsContainer"></div>
		</div>
	</body>
</html>
