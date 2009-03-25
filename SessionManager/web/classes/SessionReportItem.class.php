<?php
/**
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Gauvain Pocentek <gauvain@ulteo.com>
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

class SessionReportItem {
	private $token;
	private $sql_id = -1;
	private $server;
	private $xml_input;

	/* Session items are stored in the db before computing anything else
	 * because the sql_id is an auto-incremented int. we need to know this id
	 * so that the server reports can be linked to it */
	public function __construct($token_, $xml_input_) {
		$this->token = $token_;
		$session = Abstract_Session::load($token_);
		$this->server = $session->getAttribute('server');
		$this->user = $session->getAttribute('user_login');

		$this->xml_input = $xml_input_;

		$sql = MySQL::getInstance();
		$res = $sql->DoQuery(
			'INSERT INTO @1 (@2,@3,@4) VALUES (%5,%6,%7)',
			SESSIONS_HISTORY_TABLE,'user','server','data',
			$this->user,$this->server,'');
		if ($res !== false)
			$this->sql_id = $sql->InsertId();
	}

	public function getId() {
		return $this->sql_id;
	}
}
