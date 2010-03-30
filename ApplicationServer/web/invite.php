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
require_once(dirname(__FILE__).'/includes/core.inc.php');

Logger::debug('main', 'Starting invite.php');

if (! isset($_SESSION['ovd_session']['session']))
  die2(400, 'ERROR - No $session');

$session = $_SESSION['ovd_session']['session'];
$session_owner = (isset($_SESSION['ovd_session']['owner']) && $_SESSION['ovd_session']['owner']);
$token = $_SESSION['ovd_session']['current_token'];

$session_dir = SESSION_PATH.'/'.$session;

if (get_from_file($session_dir.'/sessions/'.$_POST['access_id'].'/status') != 2)
  die2(400, 'ERROR - No such application');

$xml = query_url(SESSIONMANAGER_URL.'/webservices/session_invite.php?fqdn='.SERVERNAME.'&session='.$session.'&email='.$_POST['email'].'&mode='.$_POST['mode']);

$dom = new DomDocument('1.0', 'utf-8');
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
@file_put_contents($buf.'/access_id', $_POST['access_id']);

echo 'OK';
