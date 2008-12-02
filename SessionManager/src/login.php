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

// if (isset($_SESSION['login']))
// 	redirect('index.php');

$prefs = Preferences::getInstance();
if (! $prefs)
	die_error('get Preferences failed',__FILE__,__LINE__);

$mods_enable = $prefs->get('general', 'module_enable');
if (!in_array('UserDB', $mods_enable))
	die_error(_('Module UserDB must be enabled'),__FILE__,__LINE__);

$mod_user_name = 'UserDB_'.$prefs->get('UserDB', 'enable');
$userDB = new $mod_user_name();

if ($userDB->canShowList())
	$list_users = $userDB->getList();

$password_field = $userDB->needPassword();

$show_list_users = $prefs->get('general', 'show_list_users');

require_once('header.php');
?>
<script type="text/javascript" src="media/script/ajax/login.js" charset="utf-8"></script>

<div id="login_box">
	<div id="login_status" class="centered"></div>

	<form id="login" action="startsession.php" method="post" onsubmit="doLogin(); return false">
		<fieldset class="hidden">
			<?php
				if (!$password_field)
					echo '<input type="hidden" id="login_password" value="" />';
			?>
			<table class="centered" border="0" cellspacing="1" cellpadding="5">
				<tr>
					<td class="centered" colspan="2">
						<h2>Please login with your username and password</h2>
					</td>
				</tr>
				<tr>
					<td>
						<img src="media/image/password.png" width="64" height="64" alt="" title="" />
					</td>
					<td>
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
									<input class="input_text" type="text" id="login_login" value="" />
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
									<input class="input_text" style="width: 150px" type="password" id="login_password" value="" />
								</td>
							</tr>
							<?php
								}
							?>
							<tr>
								<td style="text-align: right" class="centered" colspan="2">
									<input type="submit" id="login_submit" value="<?php echo _('Log in'); ?>" />
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</fieldset>
	</form>
</div>
<?php
require_once('footer.php');
