<?php
/**
 * Copyright (C) 2008,2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com>
 * Author Laurent CLOUET <laurent@ulteo.com>
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

function sendamail($to_, $subject_, $message_) {
	require_once('Mail.php');

	$prefs = Preferences::getInstance();
	if (! $prefs)
		die_error('get Preferences failed',__FILE__,__LINE__);

	$buf = $prefs->get('general', 'mails_settings');

	$method = $buf['send_type'];
	$from = $buf['send_from'];
	$host = $buf['send_host'];
	$port = $buf['send_port'];
	$ssl = false;
	if ($buf['send_ssl'] == '1')
		$ssl = true;
	if ($ssl === true)
		$host = 'ssl://'.$host;
	$localhost = '['.$_SERVER['SERVER_ADDR'].']';
	$auth = false;
	if ($buf['send_auth'] == '1')
		$auth = true;
	$username = $buf['send_username'];
	$password = $buf['send_password'];

	$to = $to_;
	$subject = $subject_;
	$message = wordwrap($message_, 72);
	$headers = array(
		'From'		=>	$from.' <'.$from.'>',
		'To'			=>	$to,
		'Subject'		=>	$subject,
		'Content-Type'		=>	'text/plain; charset=UTF-8',
		'X-Mailer'	=>	'PHP/'.phpversion()
	);

	$api_mail = Mail::factory(
		$method,
		array (
			'host'		=>	$host,
			'port'		=>	$port,
			'localhost'	=>	$localhost,
			'auth'		=>	$auth,
			'username'	=>	$username,
			'password'	=>	$password
		)
	);

	return $api_mail->send($to, $headers, $message);
}

function send_alert_mail($subject_, $message_) {
	$prefs = Preferences::getInstance();
	if (! $prefs)
		die_error('get Preferences failed',__FILE__,__LINE__);

	$tos = $prefs->get('events', 'mail_to');
	foreach ($tos as $to) {
		sendamail($to, $subject_, $message_);
	}
}
