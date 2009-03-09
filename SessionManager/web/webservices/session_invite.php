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

Logger::debug('main', 'Starting webservices/session_invite.php');

if (! isset($_GET['fqdn'])) {
	Logger::error('main', '(webservices/session_invite) Missing parameter : fqdn');
	die('ERROR - NO $_GET[\'fqdn\']');
}

$buf = Abstract_Server::load($_GET['fqdn']);
if (! $buf || ! $buf->isAuthorized()) {
	Logger::error('main', '(webservices/session_invite) Server not authorized : '.$_GET['fqdn'].' == '.@gethostbyname($_GET['fqdn']).' ?');
	die('Server not authorized');
}

Logger::debug('main', '(webservices/session_invite) Security check OK');

if (!isset($_REQUEST['session']) || $_REQUEST['session'] == '') {
	Logger::error('main', '(webservices/session_token) Missing parameter : session');
	die('ERROR - NO $_GET[\'session\']');
}

if (!isset($_REQUEST['mode']) || $_REQUEST['mode'] == '') {
	Logger::error('main', '(webservices/session_token) Missing parameter : mode');
	die('ERROR - NO $_GET[\'mode\']');
}

if (!isset($_REQUEST['email']) || $_REQUEST['email'] == '') {
	Logger::error('main', '(webservices/session_token) Missing parameter : email');
	die('ERROR - NO $_GET[\'email\']');
}

$session = Abstract_Session::load($_REQUEST['session']);

$mode = $_REQUEST['mode'];
if ($mode == 'passive')
	$view_only = 'Yes';
else
	$view_only = 'No';

$invite = new Invite(gen_string(5));
$invite->session = $session->id;
$invite->settings = array(
	'view_only'	=>	($view_only == 'Yes')?1:0
);
$invite->email = $_REQUEST['email'];
$invite->valid_until = (time()+(60*30));
Abstract_Invite::save($invite);

$token = new Token(gen_string(5));
$token->type = 'invite';
$token->link_to = $invite->id;
$token->valid_until = (time()+(60*30));
Abstract_Token::save($token);

$buf = Abstract_Server::load($session->server);

$redir = 'http://'.$buf->getAttribute('external_name').'/index.php?token='.$token->id;

$email = $_REQUEST['email'];

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
	Logger::error('main', '(webservices/session_invite) - sendamail error : '.$buf->message);
	die();
}

header('Content-Type: text/xml; charset=utf-8');

$dom = new DomDocument();
$invite_node = $dom->createElement('invite');
$invite_node->setAttribute('token', $token->id);
$invite_node->setAttribute('mode', $mode);
$invite_node->setAttribute('email', $email);
$dom->appendChild($invite_node);

$xml = $dom->saveXML();

echo $xml;
