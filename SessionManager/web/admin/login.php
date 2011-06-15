<?php
/**
 * Copyright (C) 2008-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2008-2011
 * Author Julien LANGLOIS <julien@ulteo.com> 2008-2010
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
require_once(dirname(__FILE__).'/includes/core-minimal.inc.php');

function adminAuthenticate($login_, $password_) {
  return defined('SESSIONMANAGER_ADMIN_LOGIN') and
    defined('SESSIONMANAGER_ADMIN_PASSWORD') and
    SESSIONMANAGER_ADMIN_LOGIN == $login_ and
    SESSIONMANAGER_ADMIN_PASSWORD == md5($password_);
}

function authenticate_ovd_user($login_, $password_) {
	// it's not the login&password from the conf file in /etc
	// let's try to login a real user
	
	if (Preferences::fileExists() === false) {
		$_SESSION['admin_error'] = _('The system is not configured');
		Logger::info('main', 'admin/login.php::authenticate_ovd_user the system is not configured');
		return false;
	}
	
	if (Preferences::moduleIsEnabled('UserDB') === false) {
		$_SESSION['admin_error'] = _('The module UserDB is not enabled');
		Logger::info('main', 'admin/login.php::authenticate_ovd_user module UserDB is not enabled');
		return false;
	}
	
	$userDB = UserDB::getInstance();
	$user = $userDB->import($login_);
	if (!is_object($user)) {
		// the user does not exist
		$_SESSION['admin_error'] = _('There was an error with your authentication');
		Logger::info('main', 'admin/login.php::authenticate_ovd_user authentication failed: user(login='.$login_.') does not exist');
		return false;
	}

	$auth = $userDB->authenticate($user, $password_);
	if (!$auth) {
		$_SESSION['admin_error'] = _('There was an error with your authentication');
		Logger::info('main', 'admin/login.php::authenticate_ovd_user authentication failed for user(login='.$login_.'): wrong password');
		return false;
	}

	// the user exists, does he have right to log in the admin panel ?
	$policy = $user->getPolicy();
	if (isset($policy['canUseAdminPanel']) && $policy['canUseAdminPanel'] == true) {
		return $user;
	}

	Logger::info('main', 'login.php failed to log in '.$login_.' : access denied to admin panel');
	$_SESSION['admin_error'] = _('Unauthorized access');
	return false;
}

if (isset($_POST['admin_login']) && $_POST['admin_login'] != ''
    && isset($_POST['admin_password']) && $_POST['admin_password'] != '') {
	unset($_SESSION['admin_login']);
	if (isset($_SESSION['admin_ovd_user']))
		unset($_SESSION['admin_ovd_user']);

	$login = $_POST['admin_login'];
	$password = $_POST['admin_password'];

	if (adminAuthenticate($login, $password))
		$_SESSION['admin_login'] = $login;
	else {
		$user = authenticate_ovd_user($login, $password);
		if ($user !== false) {
			$_SESSION['admin_login'] = $login;
			$_SESSION['admin_ovd_user'] = $user;
		}
	}
}

if (isset($_SESSION['admin_login'])) {
  if (isset($_SESSION['redirect'])) {
    $buf = base64_decode($_SESSION['redirect']);
    unset($_SESSION['redirect']);

    redirect($buf);
  } else
    redirect('index.php');
}

$main_title = DEFAULT_PAGE_TITLE;

header_static($main_title.' - '._('Administration'));
?>
<script type="text/javascript">
Event.observe(window, 'load', function() {
	$('admin_login').focus();
});
</script>

<h2 class="centered"><?php echo _('Log in');?></h2>

<div id="login_box" class="centered">
	<div id="login_status"></div>
<?php
	if (isset($_SESSION['admin_error'])) {
		echo '<p class="msg_error">'.$_SESSION['admin_error'].'</p>';
		unset($_SESSION['admin_error']);
	}
?>
</div>

	<form id="login" action="" method="post">
		<input type="hidden" name="redirect" value="<?php echo (isset($_SESSION['redirect'])?$_SESSION['redirect']:''); ?>" />
		<fieldset class="hidden">
			<table class="centered" border="0" cellspacing="1" cellpadding="5">
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
								<td>
									<input class="input_text" type="text" name="admin_login" id="admin_login" value="" />
								</td>
							</tr>
							<tr>
								<td style="text-align: left;" class="title">
									<span style="color: #fff; font-weight: bold; font-size: 1.5em;"><?php echo _('Password'); ?></span>
								</td>
								<td>
									<input class="input_text" type="password" name="admin_password" value="" />
								</td>
							</tr>
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

<?php
footer_static();
