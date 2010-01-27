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
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>Ulteo Open Virtual Desktop</title>

		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

		<script type="text/javascript" src="media/script/lib/prototype/prototype.js" charset="utf-8"></script>

		<link rel="stylesheet" type="text/css" href="media/script/lib/nifty/niftyCorners.css" />
		<script type="text/javascript" src="media/script/lib/nifty/niftyCorners.js" charset="utf-8"></script>
		<script type="text/javascript" charset="utf-8">
			NiftyLoad = function() {
				Nifty('div.rounded');
			}
		</script>

		<link rel="shortcut icon" type="image/png" href="favicon.ico" />
		<link rel="stylesheet" type="text/css" href="media/style/common.css" />
		<script type="text/javascript" src="media/script/common.js" charset="utf-8"></script>
	</head>

	<body style="margin: 50px; background: #ddd; color: #333;">
		<div id="mainWrap">
			<div id="headerWrap">
			</div>

			<div class="spacer"></div>

			<div id="pageWrap">
				<div id="loginBox" style="width: 75%; margin-left: auto; margin-right: auto; padding: 10px; background: #fff;" class="rounded">
					<table style="width: 100%; margin-left: auto; margin-right: auto;" border="0" cellspacing="0" cellpadding="0">
						<tr>
							<td style="width: 376px; text-align: center; vertical-align: middle;">
								<img src="media/image/ulteo.png" width="376" height="188" alt="" title="" />
							</td>
							<td style="text-align: center; vertical-align: middle;">
								<div id="loginForm" style="width: 65%; margin-left: auto; margin-right: auto; padding: 10px; background: #eee;" class="rounded">
									<form id="startsession" action="launch.php" method="post" onsubmit="return startSession($('user_login').value, $('user_password').value, $('session_mode').value);">
										<input type="hidden" id="session_id" name="session_id" value="" />
										<input type="hidden" id="session_server" name="session_server" value="" />
										<input type="hidden" id="session_login" name="session_login" value="" />
										<input type="hidden" id="session_password" name="session_password" value="" />
										<table style="width: 100%; margin-left: auto; margin-right: auto;" border="0" cellspacing="0" cellpadding="5">
											<tr>
												<td style="text-align: right; vertical-align: middle;">
													<strong><?php echo _('Login'); ?></strong>
												</td>
												<td style="text-align: center; vertical-align: middle;">
													<input type="text" id="user_login" value="" />
												</td>
											</tr>
											<tr>
												<td style="text-align: right; vertical-align: middle;">
													<strong><?php echo _('Password'); ?></strong>
												</td>
												<td style="text-align: center; vertical-align: middle;">
													<input type="password" id="user_password" value="" />
												</td>
											</tr>
											<tr>
												<td style="text-align: right; vertical-align: middle;">
													<strong><?php echo _('Mode'); ?></strong>
												</td>
												<td style="text-align: center; vertical-align: middle;">
													<select id="session_mode">
														<option value="desktop" selected="selected"><?php echo _('Desktop'); ?></option>
														<option value="portal"><?php echo _('Portal'); ?></option>
													</select>
												</td>
											</tr>
											<tr>
												<td style="text-align: right; vertical-align: middle;">
													<strong><?php echo _('Use pop-up'); ?></strong>
												</td>
												<td style="text-align: center; vertical-align: middle;">
													<input class="input_radio" id="use_popup_true" type="radio" name="popup" value="1" checked="checked" /> <?php echo _('Yes'); ?>
													<input class="input_radio" id="use_popup_false" type="radio" name="popup" value="0" /> <?php echo _('No'); ?>
												</td>
											</tr>
											<tr>
												<td style="height: 40px; text-align: center; vertical-align: bottom;" colspan="2">
													<span id="submitButton"><input type="submit" value="<?php echo _('Log in!'); ?>" /></span>
													<span id="submitLoader" style="display: none;"><img src="media/image/loader.gif" width="24" height="24" alt="" title="" /></span>
												</td>
											</tr>
										</table>
									</form>
								</div>

								<div id="appletContainer" style="display: none; width: 480px; height: 320px; margin-left: auto; margin-right: auto;">
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
