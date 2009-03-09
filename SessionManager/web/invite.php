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
	$session = Abstract_Session::load($_POST['session']);

	$view_only = 'Yes';
	if (isset($_POST['active_mode']))
		$view_only = 'No';

	$invite = new Invite(gen_string(5));
	$invite->session = $session->id;
	$invite->settings = array(
		'view_only'	=>	($view_only == 'Yes')?1:0
	);
	$invite->email = $_POST['email'];
	$invite->valid_until = (time()+(60*30));
	Abstract_Invite::save($invite);

	$token = new Token(gen_string(5));
	$token->type = 'invite';
	$token->link_to = $invite->id;
	$token->valid_until = (time()+(60*30));
	Abstract_Token::save($token);

	$buf = Abstract_Server::load($session->server);

	$redir = 'http://'.$buf->getAttribute('external_name').'/index.php?token='.$token->id;

	$email = $_POST['email'];

	$prefs = Preferences::getInstance();
	if (! $prefs)
		die_error('get Preferences failed',__FILE__,__LINE__);

	$web_interface_settings = $prefs->get('general', 'web_interface_settings');
	$main_title = $web_interface_settings['main_title'];

	$subject = _('Invitation').' from '.$main_title;

	$message =  _('You received an invitation from').' '.$main_title.', '._('please click on the link below to join the online session').'.'."\r\n\r\n";
	$message .= $redir;

	Logger::info('main', 'Sending invitation mail to '.$email.' for token '.$token->id);

	$buf = sendamail($email, $subject, wordwrap($message, 72));
	if ($buf !== true) {
		Logger::error('main', 'invite.php:49 - sendamail error : '.$buf->message);
		redirect('invite.php?server='.$session->server.'&session='.$session->id.'&invited='.$email.'&error='.$buf->message);
	}

	redirect('invite.php?server='.$session->server.'&session='.$session->id.'&invited='.$email);
}

$session = Abstract_Session::load($_GET['session']);
?>
<link rel="stylesheet" type="text/css" href="media/style/common.css" />

<h1 class="centered"><?php echo _('Desktop sharing'); ?></h2>

<p><?php echo _('Invite the following people to share this session'); ?></p>

<?php
if (isset($_GET['error']))
	echo '<p class="msg_error centered">'._('An error occured with your invitation, please try again!').'</p>';
elseif (isset($_GET['invited']))
	echo '<p class="msg_ok centered">'._('Your invitation to '.$_GET['invited'].' has been sent!').'</p>';
?>

<div style="margin-left: 0px; margin-right: 0px; text-align: left">
	<ul>
		<?php
			$invites = Invites::getBySession($_GET['session']);
			$inviteds = array();
			if (count($invites) > 0) {
				foreach ($invites as $invite) {
					$buf = $invite->getAttribute('settings');
					$inviteds[] = array($invite->getAttribute('email'), ($buf['view_only'] == 0)?_('active mode'):_('passive mode'));
				}
			}

			if (isset($inviteds) && is_array($inviteds) && count($inviteds) > 0) {
				foreach ($inviteds as $invited)
					echo '<li>'.$invited[0].' ('.$invited[1].')</li>';
			} else
				echo '<li>'._('No invitation sent for now').'</li>';
		?>
	</ul>
</div>

<fieldset class="hidden center">
	<form action="" method="post">
		<input type="hidden" name="invite" value="1" />

		<input type="hidden" name="session" value="<?php echo $session->id; ?>" />

		<p><?php echo _('Email address'); ?>: <input type="text" name="email" value="" /> <input class="input_checkbox" type="checkbox" name="active_mode" /> <?php echo _('active mode'); ?></p>

		<input type="submit" value="<?php echo _('Invite'); ?>" />
	</form>
</fieldset>
