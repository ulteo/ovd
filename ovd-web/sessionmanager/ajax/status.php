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
require_once(dirname(__FILE__).'/../includes/core.inc.php');

if (isset($_POST['invite']) && $_POST['invite'] == 1) {
	$session = new Session($_POST['session']);

	$token = $session->create_token('invite');

	$redir = 'http://'.$session->server.'/index.php?token='.$token;

	$email = $_POST['email'];
	$subject = 'Ulteo Online Desktop invitation';
	$message = 'Hi,'."\r\n\r\n".'You received an Ulteo Online Desktop invitation, please click on the link to join the session'."\r\n\r\n".$redir."\r\n\r\n".'Thanks !';
	$headers = 'From: no-reply@'.$_SERVER['SERVER_NAME']."\r\n".'X-Mailer: PHP/'.phpversion();

	Logger::info('main', 'Sending invitation mail to '.$email.' for token '.$token);

	mail($email, $subject, wordwrap($message, 72), $headers);
}

if (isset($_GET['update_status']) && $_GET['update_status'] == 1) {
	$lock = new Lock($_SESSION['login']);
	if ($lock->have_lock()) {
		$session = new Session($lock->session);

		if (!$session->session_alive())
			$already_online = 0;
		else
			$already_online = 1;
	}
	?>
		<h3>Invite</h3>
	<?php
	if (isset($already_online) && $already_online == 1) {
	?>
		<form action="status.php" method="post">
			<input type="hidden" name="invite" value="1" />

			<input type="hidden" name="session" value="<?php echo $session->session; ?>" />
			<!--<input type="hidden" name="width" value="<?php echo $_GET['width']; ?>" />
			<input type="hidden" name="height" value="<?php echo $_GET['height']; ?>" />
			<input type="hidden" name="quality" value="<?php echo $_GET['quality']; ?>" />-->

			<input type="text" name="email" value="" />

			<input type="submit" value="Invite" />
		</form>
	<?php
	} else {
	?>
		Vous n'avez pas de session active
	<?php
	}

	die();
}

die();
