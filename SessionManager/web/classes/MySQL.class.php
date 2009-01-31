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

class MySQL {
	private static $instance;

	private $link = false;
	private $db = NULL;
	private $result = false;

	public $sqlhost;
	public $sqluser;
	public $sqlpass;
	public $sqlbase;

	private $total_queries = 0;

	public function __construct($host_, $user_, $pass_, $db_) {
		$this->sqlhost = $host_;
		$this->sqluser = $user_;
		$this->sqlpass = $pass_;
		$this->sqlbase = $db_;
	}

	public function newInstance($host_, $user_, $pass_, $db_) {
		self::$instance = new MySQL($host_, $user_, $pass_, $db_);

		return self::$instance;
	}

	public function hasInstance() {
		return (isset(self::$instance));
	}

	public static function getInstance() {
		if (! isset(self::$instance))
			return false;

		return self::$instance;
	}

	public function CheckLink($die_=true) {
		if ($this->link)
			return;

		$ev = Events::getEvent ('SqlFailure');
		$this->link = @mysqli_connect($this->sqlhost, $this->sqluser, $this->sqlpass);

		if (! $this->link) {
			$mysqlcommand = 'mysql --host="'.$this->sqlhost.'" --user="'.$this->sqluser.'" --password="'.$this->sqlpass.'"  --database="'.$this->sqlbase.'"';
			Logger::error('main', '(MySQL::CheckLink) link to SQL server failed, to validate the configuration please try this bash command : '.$mysqlcommand);

			if ($die_)
				die_error('Link to SQL server failed.');

			$ev->emit();
			return false;
		}

		if ($this->SelectDB($this->sqlbase) === false) {
			if ($die_)
				die_error('Could not select database.');

			$ev->emit();
			return false;
		}

		$this->DoQuery('SET NAMES utf8');
		return true;
	}

	public function SelectDB($db) {
		$this->CheckLink();

		if (! mysqli_select_db($this->link, $db))
			return false;

		$this->db = $db;

		return true;
	}

	public function DoQuery() {
		$this->CheckLink();

		$args = func_get_args();

		$query = $args[0];

		$query = preg_replace('/@([0-9]+)/se', '(is_null($args[\\1])?\'NULL\':\'`\'.mysqli_real_escape_string($this->link, $args[\\1]).\'`\')', $query);
		$query = preg_replace('/%([0-9]+)/se', '(is_null($args[\\1])?\'NULL\':\'"\'.mysqli_real_escape_string($this->link, $args[\\1]).\'"\')', $query);

		if (is_resource($this->result)) {
			mysqli_free_result($this->result);
			$this->result = false;
		}

		Logger::debug('main','MySQL::DoQuery '.$query);
		$this->result = @mysqli_query($this->link, $query) or die_error('<strong>Error:</strong><br /> '.mysqli_error($this->link).'<br />Query: '.$query);

		$this->total_queries += 1;

		return $this->result;
	}

	public function FetchResult() {
		$this->CheckLink();

		if (! $this->result)
			return false;

		return @mysqli_fetch_assoc($this->result);
	}

	public function FetchAllResults() {
		$this->CheckLink();

		if (! $this->result)
			return false;

		$res = array();

		while ($r = @mysqli_fetch_assoc($this->result))
			$res[] = $r;

		return $res;
	}

	public function NumRows() {
		$this->CheckLink();

		if (! $this->result)
			return false;

		return @mysqli_num_rows($this->result);
	}

	public function InsertId() {
		$this->CheckLink();

		return @mysqli_insert_id($this->link);
	}

	public function TotalQueries() {
		return $this->total_queries;
	}
}
