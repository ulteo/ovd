<?php
/**
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com>
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
/*
if (isset($_GET['error']))
	echo '<p class="msg_error centered">'._('An error occured with your invitation, please try again!').'</p>';
elseif (isset($_GET['invited']))
	echo '<p class="msg_ok centered">'._('Your invitation to '.$_GET['invited'].' has been sent!').'</p>';
*/
require_once(dirname(__FILE__).'/includes/core.inc.php');

Logger::debug('main', 'Starting invite.php');

if (! isset($_SESSION['session']))
  die2(400, 'ERROR - No $session');

$session = $_SESSION['session'];
$session_owner = (isset($_SESSION['owner']) && $_SESSION['owner']);
$token = $_SESSION['current_token'];

$session_dir = SESSION_PATH.'/'.$session;

$xml = query_url(SESSIONMANAGER_URL.'/webservices/session_invite.php?fqdn='.SERVERNAME.'&session='.$session.'&email='.$_POST['email'].'&mode='.$_POST['mode']);

$dom = new DomDocument();
@$dom->loadXML($xml);

if (! $dom->hasChildNodes())
	die('Invalid XML');

$invite_node = $dom->getElementsByTagname('invite')->item(0);
if (is_null($invite_node))
	die('Missing element \'invite\'');

if ($invite_node->hasAttribute('token'))
	$invite_token = $invite_node->getAttribute('token');
if ($invite_node->hasAttribute('email'))
	$invite_email = $invite_node->getAttribute('email');
if ($invite_node->hasAttribute('mode'))
	$invite_mode = $invite_node->getAttribute('mode');

$buf = $session_dir.'/infos/share/'.$invite_token;
@mkdir($buf);
@file_put_contents($buf.'/email', $invite_email);
@file_put_contents($buf.'/mode', $invite_mode);

echo 'OK';
