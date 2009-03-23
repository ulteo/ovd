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
require_once(dirname(__FILE__).'/includes/core.inc.php');

include_once(dirname(__FILE__).'/check.php');

$list_languages = array(
	'en_GB.UTF-8'	=>	'English',
	'fr_FR.UTF-8'	=>	'Français'
);

$list_desktop_sizes = array(
	'auto'	=>	_('Maximum')
);

$list_desktop_qualitys = array(
	//0	=>	'Auto',
	2	=>	_('Lowest'),
	5	=>	_('Medium'),
	8	=>	_('High'),
	9	=>	_('Highest')
);

$list_desktop_timeouts = array(
	60		=>	_('1 minute'),
	120		=>	_('2 minutes'),
	300		=>	_('5 minutes'),
	600		=>	_('10 minutes'),
	900		=>	_('15 minutes'),
	1800		=>	_('30 minutes'),
	3600		=>	_('1 hour'),
	7200		=>	_('2 hours'),
	18000	=>	_('5 hours'),
	43200	=>	_('12 hours'),
	86400	=>	_('1 day'),
	172800	=>	_('2 days'),
	604800	=>	_('1 week'),
	2764800	=>	_('1 month'),
	-1		=>	_('Never')
);

$prefs = Preferences::getInstance();
if (! $prefs)
	die_error(_('get Preferences failed'),__FILE__,__LINE__);

$default_settings = $prefs->get('general', 'session_settings_defaults');
$desktop_locale = $default_settings['language'];
$desktop_size = 'auto';
$desktop_quality = $default_settings['quality'];
$desktop_timeout = $default_settings['timeout'];
$start_app = '';
$persistent = $default_settings['persistent'];
$shareable = $default_settings['shareable'];
$desktop_icons = $default_settings['desktop_icons'];
$allow_shell = $default_settings['allow_shell'];
$debug = 0;

$default_settings = $prefs->get('general', 'web_interface_settings');
$use_popup = $default_settings['use_popup'];

$mods_enable = $prefs->get('general', 'module_enable');
if (!in_array('UserDB', $mods_enable))
	die_error(_('Module UserDB must be enabled'),__FILE__,__LINE__);

$mod_user_name = 'UserDB_'.$prefs->get('UserDB', 'enable');
$userDB = new $mod_user_name();

if ($userDB->canShowList())
	$list_users = $userDB->getList();

$password_field = $userDB->needPassword();

$buf = $prefs->get('general', 'web_interface_settings');
$show_list_users = $buf['show_list_users'];
$testapplet = $buf['testapplet'];

// $mods_enable = $prefs->get('general', 'module_enable');
// if (!in_array('UserDB', $mods_enable))
// 	die_error(_('Module UserDB must be enabled'),__FILE__,__LINE__);

// $mod_user_name = 'UserDB_'.$prefs->get('UserDB', 'enable');
// $userDB = new $mod_user_name();

// $fs = $prefs->get('plugins', 'FS');
// if (!is_array($fs) || count($fs) == 0)
// 	die_error(_('No available filesystem'),__FILE__,__LINE__);
// $module_fs = $fs[0];

// $user = $userDB->import($_SESSION['login']);
// if (!is_object($user))
// 	die_error(_('User importation failed'),__FILE__,__LINE__);

// $user_login = $user->getAttribute('login');
// $user_id = $user->getAttribute('uid');
// $user_displayname = $user->getAttribute('displayname');

$advanced_settings_session = $prefs->get('general', 'session_settings_defaults');
$advanced_settings_session = $advanced_settings_session['advanced_settings_startsession'];
if (!is_array($advanced_settings_session))
	$advanced_settings_session = array();

$advanced_settings_webinterface = $prefs->get('general', 'web_interface_settings');
$advanced_settings_webinterface = $advanced_settings_webinterface['advanced_settings_startsession'];
if (!is_array($advanced_settings_webinterface))
	$advanced_settings_webinterface = array();

$advanced_settings = array_merge($advanced_settings_session, $advanced_settings_webinterface);

$list_servers = array();
// if (in_array('server', $advanced_settings) && isset($_GET['force'])) {
// 	$servers = $user->getAvailableServers();
	$servers = Servers::getAvailableType('linux');
	if (!is_array($servers) || count($servers) < 1)
		die_error(_('No available server'),__FILE__,__LINE__);

	foreach ($servers as $server)
		$list_servers[] = $server;
// 	if (in_array($_GET['force'], $list_servers))
// 		$random_server = $_GET['force'];
// 	else
		$random_server = $list_servers[array_rand($list_servers)];
// } else {
// 	$server = $user->getAvailableServer();
// 	if (is_object($server)) {
// 		$random_server = $server;
// 		$list_servers []= $random_server;
// 	}
// }

if ((!isset($random_server)) || !is_object($random_server))
	die_error(_('No available server'),__FILE__,__LINE__);

if (@gethostbyname($random_server->fqdn) == $random_server->fqdn) {
	$buf = $prefs->get('general', 'application_server_settings');
	$fqdn_private_address = $buf['fqdn_private_address'];
	if (isset($fqdn_private_address[$random_server->fqdn]))
		$random_server->fqdn = $fqdn_private_address[$random_server->fqdn];
}

// $applications = $user->applications();
// $list_start_app = array();
// if (count($applications) > 0)
// 	foreach ($applications as $a)
// 		$list_start_app[$a->getAttribute('executable_path')] = $a->getAttribute('name');

require_once('header.php');
?>
<script type="text/javascript" src="media/script/ajax/login.js" charset="utf-8"></script>
<script type="text/javascript" src="media/script/ajax/index.js" charset="utf-8"></script>

<script type="text/javascript">
	Event.observe(window, 'load', function() {
		setAvailableSize('desktop_size');

		<?php
			if (count($list_servers) < 1)
				echo 'testFailed(-1);';
		?>

		<?php
			if (!$testapplet) {
				echo 'appletLoaded();';
				echo 'testDone = true;';
			}
		?>
	});
</script>
&nbsp;
<div id="login_box">
	<div id="login_status" class="centered"></div>

	<form id="startsession" action="startsession.php" method="post" onsubmit="return doLogin(this);">
		<fieldset class="hidden">
			<?php
				if (!$password_field)
					echo '<input type="hidden" id="login_password" value="" />';
			?>
			<table class="centered" border="0" cellspacing="1" cellpadding="5">
				<tr>
					<td class="centered" colspan="2">
						<h2 class="centered"><?php
								if ($password_field)
									echo _('Please login with your username and password');
								else
									echo _('Please login with your username');
							?></h2>
					</td>
				</tr>
				<tr>
					<td style="text-align: right; vertical-align: middle">
						<img src="media/image/password.png" width="64" height="64" alt="" title="" />
					</td>
					<td style="text-align: left; vertical-align: middle">
						<table class="main_login centered" border="0" cellspacing="1" cellpadding="5">
							<tr>
								<td style="text-align: left;" class="title">
									<span style="color: #fff; font-weight: bold; font-size: 1.5em;"><?php echo _('Login'); ?></span>
								</td>
								<td style="text-align: right;">
									<?php
										if ($show_list_users && $userDB->canShowList()) {
									?>
									<select id="login_login" style="width: 150px">
									<?php
										foreach ($list_users as $list_user)
											echo '<option value="'.$list_user->getAttribute('login').'">'.$list_user->getAttribute('displayname').'</option>'."\n";
									?>
									</select>
									<?php
										} else {
									?>
									<input class="input_text" type="text" id="login_login" name="user_login" value="" />
									<?php
										}
									?>
								</td>
							</tr>
							<?php
								if ($password_field) {
							?>
							<tr>
								<td style="text-align: left;" class="title">
									<span style="color: #fff; font-weight: bold; font-size: 1.5em;"><?php echo _('Password'); ?></span>
								</td>
								<td style="text-align: right;">
									<input class="input_text" style="width: 150px" type="password" id="login_password" name="user_password" value="" />
								</td>
							</tr>
							<?php
								}
							?>
							<tr>
								<td style="text-align: right" class="centered" colspan="2">
									<div id="loading_div">
										<img src="media/image/loading.gif" width="32" height="32" alt="<?php echo _('Loading'); ?>..." title="<?php echo _('Loading'); ?>..." />
									</div>

									<div id="launch_buttons">
										<input type="submit" id="launch_button" value="<?php echo _('Log in'); ?>" />
										<input type="button" id="lock_button" value="<?php echo _('YOU ALREADY HAVE AN ACTIVE SESSION'); ?>" />
										<input type="submit" id="warn_button" value="<?php echo _('WARNING, START ANYWAY'); ?>" />
										<input type="button" id="failed_button" value="<?php echo _('ERROR'); ?>" />
										<input type="button" id="started_button" value="<?php echo _('SESSION IS STARTED'); ?>" />
									</div>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</fieldset>

		<fieldset class="hidden">
			<input type="hidden" id="user_login" name="user_login" value="" />
			<input type="hidden" id="user_password" name="user_password" value="" />

			<?php
				if (in_array('language', $advanced_settings) || in_array('server', $advanced_settings) || in_array('size', $advanced_settings) || in_array('quality', $advanced_settings) || in_array('timeout', $advanced_settings) || in_array('application', $advanced_settings) || in_array('persistent', $advanced_settings) || in_array('shareable', $advanced_settings) || in_array('desktop_icons', $advanced_settings) || in_array('popup', $advanced_settings) || in_array('debug', $advanced_settings)) {
			?>
			<br />
			<div class="centered">
				<a href="javascript:;" onclick="switchSettings(); return false"><span id="advanced_settings_status"><img src="media/image/show.png" width="9" height="9" alt="" title="" /></span> <?php echo _('Advanced settings'); ?></a>
			</div>

			<div id="advanced_settings" style="display: none">
				<br />
				<table class="main_sub centered" border="0" cellspacing="1" cellpadding="5">
					<?php
						if (in_array('language', $advanced_settings)) {
					?>
					<tr class="content2">
						<td class="title">
							<?php echo _('Language'); ?>
						</td>
						<td>
							<select id="desktop_locale" name="desktop_locale">
								<?php
									foreach ($list_languages as $code => $language) {
										echo '<option value="'.$code.'"';
										if ($desktop_locale == $code)
											echo ' selected="selected"';
										echo '>'.$language.'</option>'."\n";
									}
								?>
							</select>
						</td>
					</tr>
					<?php
						}

						if (in_array('server', $advanced_settings)) {
					?>
					<tr class="content2">
						<td class="title">
							<?php echo _('Server'); ?>
						</td>
						<td>
							<select id="force" name="force">
								<?php
									foreach ($list_servers as $server) {
										if ($server->getNbAvailableSessions() > 0) {
											echo '<option value="'.$server->fqdn.'"';
											if ($random_server->fqdn == $server->fqdn)
												echo ' selected="selected"';
											echo '>'.$server->fqdn.' ('.$server->getNbAvailableSessions().')</option>'."\n";
										}
									}
								?>
							</select>
						</td>
					</tr>
					<?php
						}

						if (in_array('size', $advanced_settings)) {
					?>
					<tr class="content2">
						<td class="title">
							<?php echo _('Size'); ?>
						</td>
						<td>
							<select id="desktop_size" name="desktop_size">
								<?php
									foreach ($list_desktop_sizes as $code => $size) {
										echo '<option value="'.$code.'"';
										if ($desktop_size == $code)
											echo ' selected="selected"';
										echo '>'.$size.'</option>'."\n";
									}
								?>
							</select>
						</td>
					</tr>
					<?php
						} else
							echo '<input type="hidden" id="desktop_size" name="desktop_size" value="auto" />';

						if (in_array('quality', $advanced_settings)) {
					?>
					<tr class="content2">
						<td class="title">
							<?php echo _('Quality'); ?>
						</td>
						<td>
							<select id="desktop_quality" name="desktop_quality">
								<?php
									foreach ($list_desktop_qualitys as $code => $quality) {
										echo '<option value="'.$code.'"';
										if ($desktop_quality == $code)
											echo ' selected="selected"';
										echo '>'.$quality.'</option>'."\n";
									}
								?>
							</select>
						</td>
					</tr>
					<?php
						}

						if (in_array('timeout', $advanced_settings)) {
					?>
					<tr class="content2">
						<td class="title">
							<?php echo _('Timeout'); ?>
						</td>
						<td>
							<select id="desktop_timeout" name="desktop_timeout">
								<?php
									foreach ($list_desktop_timeouts as $seconds => $text) {
										echo '<option value="'.$seconds.'"';
										if ($desktop_timeout == $seconds)
											echo ' selected="selected"';
										echo '>'.$text.'</option>'."\n";
									}
								?>
							</select>
						</td>
					</tr>
					<?php
						}

						if (in_array('application', $advanced_settings)) {
					?>
					<tr class="content2">
						<td class="title">
							<?php echo _('Application'); ?>
						</td>
						<td>
							<?php
								if (isset($list_start_app) && count($list_start_app) > 0) {
									echo '<select id="start_app" name="start_app">';

									foreach ($list_start_app as $app => $text) {
										echo '<option value="'.$app.'"';
										if ($start_app == $app)
											echo ' selected="selected"';
										echo '>'.$text.'</option>'."\n";
									}
									echo '</select>';
								} else
									echo _('Full desktop');
							?>
						</td>
					</tr>
					<?php
						}

						if (in_array('persistent', $advanced_settings)) {
					?>
					<tr class="content2">
						<td class="title">
							<?php echo _('Persistent session'); ?>
						</td>
						<td>
							<input class="input_radio" type="radio" name="persistent" value="1"<?php if ($persistent == 1) echo ' checked="checked"'; ?> /> <?php echo _('Yes'); ?>
							<input class="input_radio" type="radio" name="persistent" value="0"<?php if ($persistent != 1) echo ' checked="checked"'; ?> /> <?php echo _('No'); ?>
						</td>
					</tr>
					<?php
						}

						if (in_array('shareable', $advanced_settings)) {
					?>
					<tr class="content2">
						<td class="title">
							<?php echo _('Shareable session'); ?>
						</td>
						<td>
							<input class="input_radio" type="radio" name="shareable" value="1"<?php if ($shareable == 1) echo ' checked="checked"'; ?> /> <?php echo _('Yes'); ?>
							<input class="input_radio" type="radio" name="shareable" value="0"<?php if ($shareable != 1) echo ' checked="checked"'; ?> /> <?php echo _('No'); ?>
						</td>
					</tr>
					<?php
						}

						if (in_array('desktop_icons', $advanced_settings)) {
					?>
					<tr class="content2">
						<td class="title">
							<?php echo _('Show icons on user desktop'); ?>
						</td>
						<td>
							<input class="input_radio" type="radio" name="desktop_icons" value="1"<?php if ($desktop_icons == 1) echo ' checked="checked"'; ?> /> <?php echo _('Yes'); ?>
							<input class="input_radio" type="radio" name="desktop_icons" value="0"<?php if ($desktop_icons != 1) echo ' checked="checked"'; ?> /> <?php echo _('No'); ?>
						</td>
					</tr>
					<?php
						}

						if (in_array('popup', $advanced_settings)) {
					?>
					<tr class="content2">
						<td class="title">
							<?php echo _('Use pop-up'); ?>
						</td>
						<td>
							<input class="input_radio" id="use_popup_true" type="radio" name="use_popup" value="1"<?php if ($use_popup == 1) echo ' checked="checked"'; ?> /> <?php echo _('Yes'); ?>
							<input class="input_radio" id="use_popup_false" type="radio" name="use_popup" value="0"<?php if ($use_popup != 1) echo ' checked="checked"'; ?> /> <?php echo _('No'); ?>
						</td>
					</tr>
					<?php
						} else {
							echo '<div style="display: none;">';
							$buf_use_popup = ($use_popup == 1)?'use_popup_true':'use_popup_false';
							echo '<input class="input_radio" id="'.$buf_use_popup.'" type="radio" name="use_popup" value="'.$use_popup.'" checked="checked" />';
							echo '</div>';
						}

						if (in_array('debug', $advanced_settings)) {
					?>
					<tr class="content2">
						<td class="title">
							<?php echo _('Debug'); ?>
						</td>
						<td>
							<input class="input_radio" type="radio" id="session_debug_true" name="debug" value="1" onclick="appletLoaded()" <?php if ($debug == 1) echo ' checked="checked"'; ?> /> <?php echo _('Yes'); ?>
							<input class="input_radio" type="radio" id="session_debug_false" name="debug" value="0"<?php if ($debug != 1) echo ' checked="checked"'; ?> /> <?php echo _('No'); ?>
						</td>
					</tr>
					<?php
						} else
							echo '<input type="hidden" id="session_debug_false" name="debug" value="0" checked="checked" />';
					?>
				</table>
			</div>
			<?php
				} else {
					echo '<input type="hidden" id="desktop_size" name="desktop_size" value="auto" />';
					echo '<div style="display: none;">';
					$buf_use_popup = ($use_popup == 1)?'use_popup_true':'use_popup_false';
					echo '<input class="input_radio" id="'.$buf_use_popup.'" type="radio" name="use_popup" value="'.$use_popup.'" checked="checked" />';
					echo '</div>';
					echo '<input type="hidden" id="session_debug_false" name="debug" value="0" checked="checked" />';
				}
			?>
			<input type="hidden" id="enable_proxy" name="enable_proxy" value="0" />
			<div id="proxy_settings" style="display: none">
				<table class="main_sub" border="0" cellspacing="1" cellpadding="5">
					<tr class="content2">
						<td class="title">
							<?php echo _('Proxy type'); ?>
						</td>
						<td>
							<select id="proxy_type" name="proxy_type">
								<option value="HTTP">HTTP</option>
								<option value="SOCKS">SOCKS</option>
							</select>
						</td>
					</tr>
					<tr class="content2">
						<td class="title">
							<?php echo _('Proxy host'); ?>
						</td>
						<td>
							<input type="text" id="proxy_host" name="proxy_host" value="" />
						</td>
					</tr>
					<tr class="content2">
						<td class="title">
							<?php echo _('Proxy port'); ?>
						</td>
						<td>
							<input type="text" id="proxy_port" name="proxy_port" value="" />
						</td>
					</tr>
					<tr class="content2">
						<td class="title">
							<?php echo _('Proxy username'); ?> (<?php echo _('optional'); ?>)
						</td>
						<td>
							<input type="text" id="proxy_username" name="proxy_username" value="" />
						</td>
					</tr>
					<tr class="content2">
						<td class="title">
							<?php echo _('Proxy password'); ?> (<?php echo _('optional'); ?>)
						</td>
						<td>
							<input type="password" id="proxy_password" name="proxy_password" value="" />
						</td>
					</tr>
					<tr class="content2">
						<td class="title">
							<?php echo _('Remember proxy'); ?>
						</td>
						<td>
							<input class="input_checkbox" type="checkbox" id="remember_proxy" name="remember_proxy" value="0" />
						</td>
					</tr>
				</table>
			</div>
		</fieldset>
	</form>
</div>
<?php
if ($testapplet) {
?>
	<applet width="1" height="1">
		<param name="name" value="ulteoapplet" />
		<param name="code" value="org.vnc.VncViewer" />
		<param name="codebase" value="applet/" />
		<param name="archive" value="ulteo-applet-0.2.3g.jar" />
		<param name="cache_archive" value="ulteo-applet-0.2.3g.jar" />
		<param name="cache_archive_ex" value="ulteo-applet-0.2.3g.jar;preload" />
		<param name="mayscript" value="true" />

		<param name="HOST" value="<?php echo $random_server->getAttribute('external_name'); ?>" />
		<param name="PORT" value="5900" />
		<!--<param name="ENCPASSWORD" value="ba0c8ea04ccc5697" />-->

		<param name="SSH" value="yes" />
		<param name="ssh.host" value="<?php echo $random_server->getAttribute('external_name'); ?>" />
		<param name="ssh.port" value="443,993,995,110,40001" />
		<!--<param name="ssh.user" value="dummy" />
		<param name="ssh.password" value="3666623866373263" />-->

		<!--<param name="Compression level" value="7" />
		<param name="Restricted colors" value="false" />
		<param name="JPEG image quality" value="" />-->
		<!--<param name="Encoding" value="Tight" />-->

		<!-- Caching options -->
		<param name="rfb.cache.enabled" value="true">
		<param name="rfb.cache.ver.major" value="1">
		<param name="rfb.cache.ver.minor" value="0">
		<param name="rfb.cache.size" value="42336000">
		<param name="rfb.cache.alg" value="LRU">
		<param name="rfb.cache.datasize" value="2000000">

		<!--<param name="proxyType" value="<?php echo $proxy_type; ?>" />
		<param name="proxyHost" value="<?php echo $proxy_host; ?>" />
		<param name="proxyPort" value="<?php echo $proxy_port; ?>" />
		<param name="proxyUsername" value="<?php echo $proxy_username; ?>" />
		<param name="proxyPassword" value="<?php echo $proxy_password; ?>" />-->

		<param name="Share desktop" value="true" />
		<param name="View only" value="No" />

		<param name="agent" value="<?php echo $_SERVER['HTTP_USER_AGENT']; ?>" />
		<param name="preload" value="true" />
		<param name="onLoad" value="appletLoaded()" />
		<param name="onBadPing" value="badPing()" />
		<param name="onFail" value="testFailed" />
		<param name="haveProxy" value="haveProxy" />
		<param name="hostToPing" value="<?php echo $random_server->fqdn; ?>" />
		<param name="maxPingAccepted" value="150" />
	</applet>
<?php
}

require_once('footer.php');
