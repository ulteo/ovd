<?php
/**
 * Copyright (C) 2010 Ulteo SAS
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

$languages = get_available_languages();
$keymaps = get_available_keymaps();
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

		<link rel="shortcut icon" type="image/png" href="media/image/favicon.ico" />
		<link rel="stylesheet" type="text/css" href="media/style/common.css" />
		<script type="text/javascript" src="media/script/common.js?<?php echo time(); ?>" charset="utf-8"></script>

		<script type="text/javascript" src="media/script/daemon.js?<?php echo time(); ?>" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/daemon_desktop.js?<?php echo time(); ?>" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/daemon_applications.js?<?php echo time(); ?>" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/server.js?<?php echo time(); ?>" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/application.js?<?php echo time(); ?>" charset="utf-8"></script>

		<script type="text/javascript">
			var daemon;

			Event.observe(window, 'load', function() {
				if ($('splashContainer'))
					new Effect.Center($('splashContainer'));

				if ($('endContainer'))
					new Effect.Center($('endContainer'));

				if ($('desktopModeContainer'))
					$('desktopModeContainer').hide();
				if ($('desktopAppletContainer'))
					$('desktopAppletContainer').hide();
				if ($('applicationsModeContainer'))
					$('applicationsModeContainer').hide();
				if ($('applicationsAppletContainer'))
					$('applicationsAppletContainer').hide();
			});
		</script>
	</head>

	<body style="margin: 50px; background: #ddd; color: #333;">
		<div id="lockWrap" style="display: none;">
		</div>

		<div id="errorWrap" style="display: none;">
		</div>
		<div id="okWrap" style="display: none;">
		</div>
		<div id="infoWrap" style="display: none;">
		</div>

		<div id="splashContainer" class="rounded" style="display: none;">
			<table style="width: 100%; padding: 10px;" border="0" cellspacing="0" cellpadding="0">
				<tr>
					<td style="text-align: center;" colspan="3">
						<img src="media/image/ulteo.png" width="376" height="188" alt="" title="" />
					</td>
				</tr>
				<tr>
					<td style="text-align: left; vertical-align: middle;">
						<span style="font-size: 1.35em; font-weight: bold; color: #686868;"><?php echo _('Loading:'); ?> Open Virtual Desktop</span>
					</td>
					<td style="width: 20px"></td>
					<td style="text-align: left; vertical-align: middle;">
						<img src="media/image/rotate.gif" width="32" height="32" alt="" title="" />
					</td>
				</tr>
			</table>
		</div>

		<div id="endContainer" class="rounded" style="display: none;">
			<table style="width: 100%; padding: 10px;" border="0" cellspacing="0" cellpadding="0">
				<tr>
					<td style="text-align: center;">
						<img src="media/image/ulteo.png" width="376" height="188" alt="" title="" />
					</td>
				</tr>
				<tr>
					<td style="text-align: center; vertical-align: middle;" id="endContent">
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
						<td style="text-align: left; border-bottom: 1px solid #ccc; width: 60%;" class="title centered">
							<h1><?php echo _('Welcome!'); ?></h1>
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

			<table id="applicationsContainer" style="width: 100%; background: #eee;" border="0" cellspacing="0" cellpadding="5">
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
							<h2><?php echo _('My files'); ?></h2>

							<div id="fileManagerContainer" style="overflow: auto;">
							</div>
						</div>
						</div>
					</td>
				</tr>
			</table>

			<div id="applicationsAppletContainer" style="display: none;">
			</div>
		</div>

		<div id="mainWrap">
			<div id="headerWrap">
			</div>

			<div class="spacer"></div>

			<div id="pageWrap">
				<div id="loginBox" class="rounded">
					<table style="width: 100%; margin-left: auto; margin-right: auto;" border="0" cellspacing="0" cellpadding="0">
						<tr>
							<td style="width: 376px; text-align: center; vertical-align: top;">
								<img src="media/image/ulteo.png" width="376" height="188" alt="" title="" />
							</td>
							<td style="text-align: center; vertical-align: top;">
								<div id="loginForm" class="rounded">
									<script type="text/javascript">Event.observe(window, 'load', function() {
<?php
if (! defined('SESSIONMANAGER_URL'))
	echo '$(\'sessionmanager_url\').focus();';
else
	echo '$(\'user_login\').focus();';
?>
									});</script>
									<form id="startsession" action="launch.php" method="post" onsubmit="return startSession();">
										<table style="width: 100%; margin-left: auto; margin-right: auto; padding-top: 10px; margin-bottom: 25px; " border="0" cellspacing="0" cellpadding="5">
											<tr style="<?php echo ((defined('SESSIONMANAGER_URL'))?'display: none;':'') ?>">
												<td style="width: 22px; text-align: right; vertical-align: middle;">
													<!--<img src="media/image/icons/sessionmanager.png" alt="" title="" />-->
												</td>
												<td style="text-align: left; vertical-align: middle;">
													<strong><?php echo _('Session Manager'); ?></strong>
												</td>
												<td style="text-align: right; vertical-align: middle;">
													<input type="text" id="sessionmanager_url" value="<?php echo ((defined('SESSIONMANAGER_URL'))?'null':'') ?>" onchange="checkLogin(); return false;" onkeyup="checkLogin(); return false;" />
													<script type="text/javascript">
														var sessionmanager_url_example = '<?php echo _('Example: sm.ulteo.com'); ?>';
														if ($('sessionmanager_url').value == '') {
															$('sessionmanager_url').style.color = 'grey';
															$('sessionmanager_url').value = sessionmanager_url_example;
															setCaretPosition($('sessionmanager_url'), 0);
														}
														Event.observe($('sessionmanager_url'), 'keypress', function() {
															if ($('sessionmanager_url').value == sessionmanager_url_example) {
																$('sessionmanager_url').style.color = 'black';
																$('sessionmanager_url').value = '';
															}
														});
														Event.observe($('sessionmanager_url'), 'keyup', function() {
															if ($('sessionmanager_url').value == '') {
																$('sessionmanager_url').style.color = 'grey';
																$('sessionmanager_url').value = sessionmanager_url_example;
																setCaretPosition($('sessionmanager_url'), 0);
															}
														});
													</script>
												</td>
											</tr>
											<tr>
												<td style="width: 22px; text-align: right; vertical-align: middle;">
													<!--<img src="media/image/icons/user_login.png" alt="" title="" />-->
												</td>
												<td style="text-align: left; vertical-align: middle;">
													<strong><?php echo _('Login'); ?></strong>
												</td>
												<td style="text-align: right; vertical-align: middle;">
													<input type="text" id="user_login" value="" onchange="checkLogin(); return false;" onkeyup="checkLogin(); return false;" />
												</td>
											</tr>
											<tr>
												<td style="text-align: right; vertical-align: middle;">
													<!--<img src="media/image/icons/user_password.png" alt="" title="" />-->
												</td>
												<td style="text-align: left; vertical-align: middle;">
													<strong><?php echo _('Password'); ?></strong>
												</td>
												<td style="text-align: right; vertical-align: middle;">
													<input type="password" id="user_password" value="" />
												</td>
											</tr>
										</table>
										<div id="advanced_settings" style="display: none;">
											<table style="width: 100%; margin-left: auto; margin-right: auto;" border="0" cellspacing="0" cellpadding="5">
												<tr>
													<td style="width: 22px; text-align: right; vertical-align: middle;">
														<!--<img src="media/image/icons/session_mode.png" alt="" title="" />-->
													</td>
													<td style="text-align: left; vertical-align: middle;">
														<strong><?php echo _('Mode'); ?></strong>
													</td>
													<td style="text-align: right; vertical-align: middle;">
														<select id="session_mode">
															<option value="desktop" selected="selected"><?php echo _('Desktop'); ?></option>
															<option value="applications"><?php echo _('Portal'); ?></option>
														</select>
													</td>
												</tr>
												<tr>
													<td style="text-align: right; vertical-align: middle;">
														<img src="media/image/icons/session_language.png" alt="" title="" />
													</td>
													<td style="text-align: left; vertical-align: middle;">
														<strong><?php echo _('Language'); ?></strong>
													</td>
													<td style="text-align: right; vertical-align: middle;">
														<span><img id="session_language_flag" /></span><script type="text/javascript">Event.observe(window, 'load', function() { updateFlag('<?php echo $user_language; ?>'); });</script>&nbsp;
														<select id="session_language" onchange="updateFlag($('session_language').value); updateKeymap($('session_language').value);" onkeyup="updateFlag($('session_language').value); updateKeymap($('session_language').value);">
															<?php
																foreach ($languages as $language)
																	echo '<option value="'.$language['id'].'" style="background: url(\'media/image/flags/'.$language['id'].'.png\') no-repeat right;"'.(($language['id'] == $user_language)?' selected="selected"':'').'>'.$language['english_name'].((array_key_exists('local_name', $language))?' - '.$language['local_name']:'').'</option>';
															?>
														</select>
													</td>
												</tr>
												<tr>
													<td style="text-align: right; vertical-align: middle;">
														<img src="media/image/icons/keyboard_layout.png" alt="" title="" />
													</td>
													<td style="text-align: left; vertical-align: middle;">
														<strong><?php echo _('Keyboard layout'); ?></strong>
													</td>
													<td style="text-align: right; vertical-align: middle;">
														<select id="session_keymap">
															<?php
																foreach ($keymaps as $keymap)
																	echo '<option value="'.$keymap['id'].'"'.(($keymap['id'] == $user_language)?' selected="selected"':'').'>'.$keymap['name'].'</option>';
															?>
														</select>
													</td>
												</tr>
												<tr>
													<td style="text-align: right; vertical-align: middle;">
														<!--<img src="media/image/icons/use_popup.png" alt="" title="" />-->
													</td>
													<td style="text-align: left; vertical-align: middle;">
														<strong><?php echo _('Use pop-up'); ?></strong>
													</td>
													<td style="text-align: right; vertical-align: middle;">
														<input class="input_radio" type="radio" id="use_popup_true" name="popup" value="1" /> <?php echo _('Yes'); ?>
														<input class="input_radio" type="radio" id="use_popup_false" name="popup" value="0" checked="checked" /> <?php echo _('No'); ?>
													</td>
												</tr>
												<tr>
													<td style="text-align: right; vertical-align: middle;">
														<!--<img src="media/image/icons/debug.png" alt="" title="" />-->
													</td>
													<td style="text-align: left; vertical-align: middle;">
														<strong><?php echo _('Debug'); ?></strong>
													</td>
													<td style="text-align: right; vertical-align: middle;">
														<input class="input_radio" type="radio" id="debug_true" name="debug" value="1" /> <?php echo _('Yes'); ?>
														<input class="input_radio" type="radio" id="debug_false" name="debug" value="0" checked="checked" /> <?php echo _('No'); ?>
													</td>
												</tr>
											</table>
										</div>
										<table style="width: 100%; margin-left: auto; margin-right: auto; margin-top: 25px; padding-bottom: 10px;" border="0" cellspacing="0" cellpadding="5">
											<tr>
												<td style="text-align: left; vertical-align: bottom;">
													<span id="advanced_settings_status" style="position: relative; left: 22px;"><img src="media/image/show.png" width="12" height="12" alt="" title="" /></span> <input style="padding-left: 18px;" type="button" value="<?php echo _('Advanced settings'); ?>" onclick="switchSettings(); return false;" />
												</td>
												<td style="text-align: right; vertical-align: bottom;">
													<span id="submitButton"><input type="submit" id="submitLogin" value="<?php echo _('Connect'); ?>" disabled="disabled" /></span>
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
