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
							<td style="text-align: center; vertical-align: middle;">
								<div id="loginForm" class="rounded">
									<form id="startsession" action="launch.php" method="post" onsubmit="return startSession();">
										<table style="width: 100%; margin-left: auto; margin-right: auto;" border="0" cellspacing="0" cellpadding="5">
											<tr>
												<td style="width: 22px; text-align: right; vertical-align: middle;">
													<img src="media/image/icons/user_login.png" alt="" title="" />
												</td>
												<td style="text-align: left; vertical-align: middle;">
													<strong><?php echo _('Login'); ?></strong>
												</td>
												<td style="text-align: right; vertical-align: middle;">
													<input type="text" id="user_login" value="" />
												</td>
											</tr>
											<tr>
												<td style="text-align: right; vertical-align: middle;">
													<img src="media/image/icons/user_password.png" alt="" title="" />
												</td>
												<td style="text-align: left; vertical-align: middle;">
													<strong><?php echo _('Password'); ?></strong>
												</td>
												<td style="text-align: right; vertical-align: middle;">
													<input type="password" id="user_password" value="" />
												</td>
											</tr>
										</table>
										<div class="centered" style="margin-top: 15px; margin-bottom: 15px;">
											<a href="javascript:;" onclick="switchSettings(); return false;"><span id="advanced_settings_status"><img src="media/image/show.png" width="9" height="9" alt="" title="" /></span> <?php echo _('Advanced settings'); ?></a>
										</div>
										<div id="advanced_settings" style="display: none;">
											<table style="width: 100%; margin-left: auto; margin-right: auto;" border="0" cellspacing="0" cellpadding="5">
												<tr>
													<td style="width: 22px; text-align: right; vertical-align: middle;">
														<img src="media/image/icons/session_mode.png" alt="" title="" />
													</td>
													<td style="text-align: left; vertical-align: middle;">
														<strong><?php echo _('Mode'); ?></strong>
													</td>
													<td style="text-align: right; vertical-align: middle;">
														<select id="session_mode">
															<option value="desktop" selected="selected"><?php echo _('Desktop'); ?></option>
															<option value="applications"><?php echo _('Applications'); ?></option>
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
														<span><img id="session_language_flag" /></span><script type="text/javascript">Event.observe(window, 'load', updateFlag('en-us'));</script>&nbsp;
														<select id="session_language" onchange="updateFlag($('session_language').value);" onkeyup="updateFlag($('session_language').value);">
															<?php
																foreach ($languages as $language)
																	echo '<option value="'.$language['id'].'" style="background: url(\'media/image/flags/'.$language['id'].'.png\') no-repeat right;"'.(($language['id'] == 'en-us')?' selected="selected"':'').'>'.$language['english_name'].((array_key_exists('local_name', $language))?' - '.$language['local_name']:'').'</option>';
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
																	echo '<option value="'.$keymap['id'].'"'.(($keymap['id'] == 'en-us')?' selected="selected"':'').'>'.$keymap['name'].'</option>';
															?>
														</select>
													</td>
												</tr>
												<tr>
													<td style="text-align: right; vertical-align: middle;">
														<img src="media/image/icons/use_popup.png" alt="" title="" />
													</td>
													<td style="text-align: left; vertical-align: middle;">
														<strong><?php echo _('Use pop-up'); ?></strong>
													</td>
													<td style="text-align: right; vertical-align: middle;">
														<input class="input_radio" type="radio" id="use_popup_true" name="popup" value="1" checked="checked" /> <?php echo _('Yes'); ?>
														<input class="input_radio" type="radio" id="use_popup_false" name="popup" value="0" /> <?php echo _('No'); ?>
													</td>
												</tr>
												<tr>
													<td style="text-align: right; vertical-align: middle;">
														<img src="media/image/icons/debug.png" alt="" title="" />
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
										<table style="width: 100%; margin-left: auto; margin-right: auto;" border="0" cellspacing="0" cellpadding="5">
											<tr>
												<td style="height: 40px; text-align: center; vertical-align: bottom;">
													<span id="submitButton"><input type="submit" value="<?php echo _('Connect'); ?>" /></span>
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
