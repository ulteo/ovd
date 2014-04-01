<?php
/**
 * Copyright (C) 2008-2012 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2008-2011
 * Author Julien LANGLOIS <julien@ulteo.com> 2008-2011, 2012
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
require_once(dirname(dirname(__FILE__)).'/includes/core-minimal.inc.php');
require_once(dirname(dirname(__FILE__)).'/includes/page_template_static.php');

function adminAuthenticate($login_, $password_) {
	if (array_key_exists('no_ssl', $_SESSION)) {
		unset($_SESSION['no_ssl']);
	}
	
	try {
		$service = new SessionManager($login_, $password_, true);
		$ret = $service->test_link_connected();
	}
	catch (Exception $e) {
		if ($e->faultcode == 'auth_failed') {
			$_SESSION['admin_error'] = _('There was an error with your authentication');
			return false;
		}
		
		if (defined('DEBUG_MODE') && DEBUG_MODE === true) {
			popup_error('Unable to login the Session Manager using HTTPS method, retry with HTTP');
		}
		
		try {
			$service = new SessionManager($login_, $password_, false);
			$ret = $service->test_link_connected();
		}
		catch (Exception $e) {
			if ($e->faultcode == 'auth_failed') {
				$_SESSION['admin_error'] = _('There was an error with your authentication');
				return false;
			}
			
			if (defined('DEBUG_MODE') && DEBUG_MODE === true) {
				popup_error($service->format_soap_error_message($e));
			}
			
			die_error(_('Unable to initialize communication with Session Manager'));
		}
		
		$_SESSION['no_ssl'] = true;
		if (defined('DEBUG_MODE') && DEBUG_MODE === true) {
			popup_info('Succefully connected the Session Manager using HTTP method, will alwais use HTTP for this session');
		}
	}
	
	$_SESSION['admin_login'] = $login_;
	$_SESSION['admin_password'] = $password_;
	$_SESSION['service'] = $service;
	return true;
}

if (isset($_POST['admin_login']) && $_POST['admin_login'] != ''
    && isset($_POST['admin_password']) && $_POST['admin_password'] != '') {
	unset($_SESSION['admin_login']);
	if (isset($_SESSION['admin_ovd_user']))
		unset($_SESSION['admin_ovd_user']);
	
	adminAuthenticate($_POST['admin_login'], $_POST['admin_password']);
}

if (array_key_exists('service', $_SESSION) and array_key_exists('admin_login', $_SESSION) and array_key_exists('admin_password', $_SESSION)) {
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
