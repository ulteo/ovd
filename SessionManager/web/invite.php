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

if (isset($_POST['invite']) && $_POST['invite'] == 1) {
	$session = new Session($_POST['session']);

	$view_only = 'Yes';
	if (isset($_POST['active_mode']))
		$view_only = 'No';

	$token = $session->create_token('invite', array('view_only' => $view_only));

	$redir = 'http://'.$session->server.'/index.php?token='.$token;

	$email = $_POST['email'];

	$prefs = Preferences::getInstance();
	if (! $prefs)
		die_error('get Preferences failed',__FILE__,__LINE__);

	$main_title = $prefs->get('general', 'main_title');

	$subject = _('Invitation').' from '.$main_title;

	$message =  _('You received an invitation from').' '.$main_title.', '._('please click on the link below to join the online session').'.'."\r\n\r\n";
	$message .= $redir;

	Logger::info('main', 'Sending invitation mail to '.$email.' for token '.$token);

	sendamail($email, $subject, wordwrap($message, 72));

	$session->addInvite($email);

	header('Location: invite.php?server='.$session->server.'&session='.$session->session.'&invited='.$email);
}

$session = new Session($_GET['session']);
?>
<link rel="stylesheet" type="text/css" href="media/style/common.css" />

<style>
html,body {
	background: #0f2d47;
	margin: none;
	padding: none;
}
</style>

<div style="background: #eee; width: 30%; margin-left: auto; margin-right: 0px; text-align: center; position: absolute; top: 0px; right: 0px;">
	<h1 class="centered"><?php echo _('Desktop sharing'); ?></h2>

	<p><?php echo _('Invite the following people to share this session'); ?></p>

	<?php
	if (isset($_GET['invited']))
		echo '<p class="msg_ok centered">'._('Your invitation to '.$_GET['invited'].' has been sent !').'</p>';
	?>

	<?php
// 		if (file_exists($session->folder.'/used'))
// 			echo 'Session started: '.date('d/m/Y H:i:s', filemtime($session->folder.'/used'));
	?>

	<div style="margin-left: auto; margin-right: 0px; text-align: left">
		<ul>
			<?php
				$invited_emails = $session->invitedEmails();

				if (is_array($invited_emails)) {
					foreach ($invited_emails as $invited_email)
						echo '<li>'.$invited_email.'</li>';
				} else
					echo '<li>'._('No invitation sent for the moment').'</li>';
			?>
		</ul>
	</div>

	<fieldset class="hidden centered">
		<form action="" method="post">
			<input type="hidden" name="invite" value="1" />

			<input type="hidden" name="session" value="<?php echo $session->session; ?>" />

			<p><?php echo _('Email address'); ?>: <input type="text" name="email" value="" /> <input type="checkbox" name="active_mode" /> <?php echo _('active mode'); ?></p>

			<input type="submit" value="<?php echo _('Invite'); ?>" />
		</form>
	</fieldset>
</div>
