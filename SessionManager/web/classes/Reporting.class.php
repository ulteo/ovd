<?php
/**
 * Copyright (C) 2008 Ulteo SAS
 * http://www.ulteo.com
 * Author Jeremy DESVAGES <jeremy@ulteo.com>
 * Author Laurent CLOUET <laurent@ulteo.com>
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

class Reporting {
	public function __construct($session_id_) {
		$this->session = Abstract_Session::load($session_id_);
	}

	public function session_begin($token_, $user_) {
		$buf = TMP_DIR.'/reporting_'.$this->session->id.'_'.date('Ymd'); //and if the day changes ?

		@mkdir($buf);

		@file_put_contents($buf.'/date_begin', time());
		@file_put_contents($buf.'/token', $token_);
		@file_put_contents($buf.'/user', serialize($user_));
		@file_put_contents($buf.'/remote_addr', $_SERVER['REMOTE_ADDR']);

		return true;
	}

	public function session_end() {
		$buf = TMP_DIR.'/reporting_'.$this->session->id.'_'.date('Ymd'); //and if the day changes ?

		@mkdir($buf, 0755);

		@file_put_contents($buf.'/date_end', time());
		@file_put_contents($buf.'/final_status', 4);

		$this->generateXML();

		$files = glob($buf.'/*');
		foreach ($files as $file)
			@unlink($file);

		@rmdir($buf);

		return true;
	}

	public function generateXML() {
		$buf = TMP_DIR.'/reporting_'.$this->session->id.'_'.date('Ymd'); //and if the day changes ?

		$date_begin = @file_get_contents($buf.'/date_begin');
		$date_end = @file_get_contents($buf.'/date_end');
		$final_status = @file_get_contents($buf.'/final_status');
		$token = @file_get_contents($buf.'/token');
		$user = unserialize(@file_get_contents($buf.'/user'));
		$remote_addr = @file_get_contents($buf.'/remote_addr');

		$dom = new DomDocument('1.0', 'utf-8');
		$session_node = $dom->createElement('session');
		$session_node->setAttribute('id', $this->session->id);
		$session_node->setAttribute('date_begin', $date_begin);
		$session_node->setAttribute('date_end', $date_end);
		$session_node->setAttribute('final_status', $final_status);
		$session_node->setAttribute('token', $token);
		$dom->appendChild($session_node);

		$server_node = $dom->createElement('server');
		$server_node->setAttribute('fqdn', $this->session->server);
		$session_node->appendChild($server_node);

		$users_node = $dom->createElement('users');
		$user_node = $dom->createElement('user');
		$user_node->setAttribute('id', $user->getAttribute('login'));
		$user_node->setAttribute('login', $user->getAttribute('login'));
		$user_node->setAttribute('remote_addr', $remote_addr);
		$users_node->appendChild($user_node);
		$session_node->appendChild($users_node);

		$settings_node = $dom->createElement('settings');
		$settings = $this->session->getAttribute('settings');
		foreach ($settings as $k => $v) {
			if ($k == 'user_id' || $k == 'user_login' || $k == 'user_displayname' || $k == 'module_fs')
				continue;

			$item = $dom->createElement('setting');
			$item->setAttribute('key', $k);
			$item->setAttribute('value', $v);
			$settings_node->appendChild($item);
		}
		if (isset($settings['module_fs']) && is_array($settings['module_fs'])) {
			foreach ($settings['module_fs'] as $k2 => $v2) {
				$item = $dom->createElement('setting');
				$item->setAttribute('key', $settings['home_dir_type'].'_'.$k2);
				$item->setAttribute('value', $v2);
				$settings_node->appendChild($item);
			}
		}
		$session_node->appendChild($settings_node);

		$applications_node = $dom->createElement('applications');
		//<application name="" processus="" />
		$session_node->appendChild($applications_node);

		$invitations_node = $dom->createElement('invitations');
		$invitations = $this->session->getAttribute('invitations');
		foreach ($invitations as $invitation) {
			if ($invitation === '') //for the last \n ...
				continue;

			$item = $dom->createElement('invitation');
			$item->setAttribute('email', $invitation);
			//date=""
			//token=""
			//user=""
			//date_join=""
			//date_leave="" //not possible with current system
			$invitations_node->appendChild($item);
		}
		$session_node->appendChild($invitations_node);

		$xml = $dom->saveXML();

		@file_put_contents(REPORTING_DIR.'/'.$this->session->id.'_'.date('Ymd').'.xml', $xml);

		return true;
	}
}
