<?php
/**
 * Copyright (C) 2011-2013 Ulteo SAS
 * http://www.ulteo.com
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2011
 * Author Julien LANGLOIS <julien@ulteo.com> 2012, 2013
 * Author David PHAM-VAN <d.pham-van@ulteo.com> 2012
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

if (!$big_image_map) {
	$logo_size = getimagesize(dirname(__FILE__).'/media/image/ulteo.png');
	if ($logo_size === false)
		$logo_size = "";
	else
		$logo_size = $logo_size[3];
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

$rdp_input_unicode = null;
if (defined('RDP_INPUT_METHOD'))
	$rdp_input_unicode = RDP_INPUT_METHOD;

$use_proxy = false;
if (defined('OPTION_USE_PROXY') && is_bool(OPTION_USE_PROXY)) {
 $use_proxy = OPTION_USE_PROXY;
}

$local_integration = (defined('PORTAL_LOCAL_INTEGRATION') && (PORTAL_LOCAL_INTEGRATION === true));

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

		<link rel="shortcut icon" href="media/image/favicon.ico" />
		<link rel="shortcut icon" type="image/png" href="media/image/favicon.png" />

<?php if (file_exists(WEB_CLIENT_ROOT . "/media/style/uovd.css")) { ?>
		<link rel="stylesheet" type="text/css" href="media/style/uovd.css" />
<?php } else { ?>
		<link rel="stylesheet" type="text/css" href="media/script/lib/nifty/niftyCorners.css" />
		<link rel="stylesheet" type="text/css" href="media/style/images.css" />
		<link rel="stylesheet" type="text/css" href="media/style/common.css" />
<?php } ?>

		<script type="text/javascript" src="media/script/lib/jquery/jquery.js" charset="utf-8"></script>

<?php if (file_exists(WEB_CLIENT_ROOT . "/media/script/webclient/uovd.js")) { ?>
		<script type="text/javascript" src="media/script/webclient/uovd.js" charset="utf-8"></script>
<?php } else { ?>
		<script type="text/javascript" src="media/script/lib/prototype/prototype.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/lib/scriptaculous/effects.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/lib/scriptaculous/extensions.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/lib/nifty/niftyCorners.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/webclient/common.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/webclient/daemon.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/webclient/daemon_desktop.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/webclient/daemon_applications.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/webclient/daemon_external.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/webclient/server.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/webclient/application.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/webclient/JavaTester.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/webclient/Logger.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/webclient/timezones.js" charset="utf-8"></script>
<?php } ?>

		<script type="text/javascript" src="media/script/webclient/uovd_ext_client.js" charset="utf-8"></script>

		<script type="text/javascript">
			/* Options from PHP to JS */
			var SESSIONMANAGER = '<?php echo SESSIONMANAGER_HOST; ?>';
			var GATEWAY_FIRST_MODE = <?php echo (($gateway_first === true)?'true':'false'); ?>;
			var OPTION_KEYMAP_AUTO_DETECT = <?php echo ((OPTION_KEYMAP_AUTO_DETECT === true)?'true':'false'); ?>;
			var OPTION_USE_PROXY = <?php echo (($use_proxy === true)?'true':'false'); ?>;
			var big_image_map = <?php echo ($big_image_map?'true':'false'); ?>;
			var user_keymap = '<?php echo $user_keymap; ?>';
			var rdp_input_method = <?php echo (($rdp_input_unicode == null)?'null':'\''.$rdp_input_unicode.'\''); ?>;
			var local_integration = <?php echo (($local_integration === true)?'true':'false'); ?>;
			var debug_mode = <?php echo (($debug_mode === true)?'true':'false'); ?>;
			var client_language = '<?php echo $user_language; ?>';

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

			var session_mode = <?php echo $params['mode']; ?>;
			var session_user = <?php echo $params['login']; ?>;
			var session_pass = <?php echo $params['password']; ?>;
			var session_token = <?php echo $params['token']; ?>;
			var session_app = <?php echo $params['app']; ?>;
			var session_file = <?php echo $params['file']; ?>;
			var session_file_type = <?php echo $params['file_type']; ?>;
			var session_file_share = <?php echo $params['file_share']; ?>;

			var i18n = new Hash();
			<?php
				foreach ($js_translations as $id => $string)
					echo 'i18n.set(\''.$id.'\', \''.str_replace('\'', '\\\'', $string).'\');'."\n";
			?>
			var i18n_tmp = new Hash();
			<?php
				foreach ($translations as $id => $string)
					echo 'i18n_tmp.set(\''.$id.'\', \''.str_replace('\'', '\\\'', $string).'\');'."\n";
			?>
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
<?php if (!$big_image_map) { ?>
							<img src="media/image/error.png" width="32" height="32" alt="" title="" />
<?php } else { ?>
							<div class="image_error_png"></div>
<?php } ?>
						</td>
					</tr>
				</table>
				<div style="text-align:center;">
<?php if (!$big_image_map) { ?>
					<img src="media/image/ulteo-small.png" width="141" height="80" alt="Ulteo Open Virtual Desktop" title="Ulteo Open Virtual Desktop"/>
<?php } else { ?>
					<div class="image_ulteo-small_png" style="margin-left: auto; margin-right: auto;"></div>
<?php } ?>
				</div>
			</div>
		</noscript>

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
								<?php if (!$big_image_map) { ?>
								<img src="media/image/rotate.gif" width="32" height="32" alt="" title="" />
								<?php } else { ?>
								<div class="image_rotate_gif"></div>
								<?php } ?>
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
								<?php if (!$big_image_map) { ?>
								<img src="media/image/error.png" width="32" height="32" alt="" title="" />
								<?php } else { ?>
								<div class="image_error_png"></div>
								<?php } ?>
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
						<?php if (!$big_image_map) { ?>
						<img src="media/image/ulteo.png" <?php echo $logo_size; ?> alt="" title="" />
						<?php } else { ?>
						<div class="image_ulteo_png"></div>
						<?php } ?>
					</td>
				</tr>
				<tr>
					<td style="text-align: left; vertical-align: middle; margin-top: 15px;">
						<span style="font-size: 1.35em; font-weight: bold; color: #686868;"><?php echo _('Do not close this Ulteo OVD window!'); ?></span>
					</td>
					<td style="width: 20px"></td>
					<td style="text-align: left; vertical-align: middle;">
						<?php if (!$big_image_map) { ?>
						<img src="media/image/rotate.gif" width="32" height="32" alt="" title="" />
						<?php } else { ?>
						<div class="image_rotate_gif"></div>
						<?php } ?>
					</td>
				</tr>
			</table>
		</div>

		<div id="endContainer" class="rounded" style="display: none;">
			<table style="width: 100%; padding: 10px;" border="0" cellspacing="0" cellpadding="0">
				<tr>
					<td style="text-align: center;">
						<?php if (!$big_image_map) { ?>
						<img src="media/image/ulteo.png" <?php echo $logo_size; ?> alt="" title="" />
						<?php } else { ?>
						<div class="image_ulteo_png"></div>
						<?php } ?>
					</td>
				</tr>
				<tr>
					<td style="text-align: center; vertical-align: middle; margin-top: 15px;" id="endContent">
					</td>
				</tr>
			</table>
		</div>

		<div id="sessionContainer" style="display: none;">
			<div id="desktopContainer">
			</div>
		</div>

		<div id="debugContainer" class="no_debug info warning error" style="display: none;">
		</div>

		<div id="debugLevels" style="display: none;">
			<span class="debug"><input type="checkbox" id="level_debug" value="10" /> Debug</span>
			<span class="info"><input type="checkbox" id="level_info" value="20" checked="checked" /> Info</span>
			<span class="warning"><input type="checkbox" id="level_warning" value="30" checked="checked" /> Warning</span>
			<span class="error"><input type="checkbox" id="level_error" value="40" checked="checked" /> Error</span><br />
			<input type="button" id="clear_button" value="Clear" />
		</div>
	</body>
</html>
