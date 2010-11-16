<?php
/**
 * Copyright (C) 2008-2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2008
 * Author Laurent CLOUET <laurent@ulteo.com> 2009
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

class SQL {
	private static $instance = NULL;

	private $link = false;
	private $db = NULL;
	private $result = false;

	public $sqltype;
	public $sqlhost;
	public $sqluser;
	public $sqlpass;
	public $sqlbase;

	public $prefix;

	private $total_queries = 0;

	public function __construct($sql_conf_) {
		if (array_key_exists('type', $sql_conf_))
			$this->sqltype = $sql_conf_['type'];
		if (array_key_exists('host', $sql_conf_))
			$this->sqlhost = $sql_conf_['host'];
		if (array_key_exists('user', $sql_conf_))
			$this->sqluser = $sql_conf_['user'];
		if (array_key_exists('password', $sql_conf_))
			$this->sqlpass = $sql_conf_['password'];
		if (array_key_exists('database', $sql_conf_))
			$this->sqlbase = $sql_conf_['database'];
		if (array_key_exists('prefix', $sql_conf_))
			$this->prefix = $sql_conf_['prefix'];
	}

	public static function newInstance($sql_conf_) {
		self::$instance = new self($sql_conf_);

		return self::$instance;
	}

	public function hasInstance() {
		return (! is_null(self::$instance));
	}

	public static function getInstance() {
		if (is_null(self::$instance)) {
			$prefs = Preferences::getInstance();
			if (! $prefs) {
				die_error('get Preferences failed',__FILE__,__LINE__);
				return false;
			}
			$sql_conf = $prefs->get('general', 'sql');
			self::newInstance($sql_conf);
		}
		return self::$instance;
	}

	public function CheckLink($die_=true) {
		if ($this->link)
			return;

		$ev = new SqlFailure(array('host' => $this->sqlhost));

		$this->link = @mysqli_connect($this->sqlhost, $this->sqluser, $this->sqlpass);

		if (! $this->link) {
			if ($die_) {
				$ev->setAttribute('status', -1);
				$ev->emit();
				die_error('Link to SQL server failed.',__FILE__,__LINE__);
			}

			return false;
		}

		if ($this->SelectDB($this->sqlbase) === false) {
			if ($die_) {
				$ev->setAttribute('status', -1);
				$ev->emit();
				die_error('Could not select database.',__FILE__,__LINE__);
			}

			return false;
		}

		$this->DoQuery('SET NAMES utf8');
		$ev->setAttribute('status', 1);
		$ev->emit();
		return true;
	}

	public function SelectDB($db) {
		$this->CheckLink();

		if (! mysqli_select_db($this->link, $db))
			return false;

		$this->db = $db;

		return true;
	}

	private function CleanValue($value_) {
		if (! is_string($value_))
			return $value_;

		$this->CheckLink();

		return mysqli_real_escape_string($this->link, $value_);
	}

	public function DoQuery() {
		$this->CheckLink();

		$args = func_get_args();

		$query = $args[0];

		$query = preg_replace('/@([0-9]+)/se', '((! array_key_exists(\\1, $args))?\'@\\1\':(is_null($args[\\1])?\'NULL\':\'`\'.mysqli_real_escape_string($this->link, $args[\\1]).\'`\'))', $query);
		$query = preg_replace('/%([0-9]+)/se', '((! array_key_exists(\\1, $args))?\'%\\1\':(is_null($args[\\1])?\'NULL\':\'"\'.$this->CleanValue($args[\\1]).\'"\'))', $query);

		if (is_resource($this->result)) {
			mysqli_free_result($this->result);
			$this->result = false;
		}

		$this->result = @mysqli_query($this->link, $query) or die_error('<strong>Error:</strong><br /> '.mysqli_error($this->link).'<br />Query: '.$query,__FILE__,__LINE__);

		$this->total_queries += 1;

		return true;
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
	
	public function buildTable($name_, $table_structure_, $primary_keys_) {
		$this->CheckLink();
		
		// the table exists ?
		$table_exists = false;
		$ret = $this->DoQuery('SHOW TABLES LIKE %1', $name_);
		if ($ret !== false) {
			$ret2 = $this->NumRows($ret);
			if ($ret2 == 1)
				$table_exists =  true;
			else
				$table_exists = false;
		}
		if (! $table_exists) {
			$query  = 'CREATE TABLE IF NOT EXISTS @1 (';
			foreach ($table_structure_ as $column_name => $column_type) {
				$query .= '`'.mysql_escape_string($column_name).'` '.$column_type.' , ';
			}
			$query = substr($query, 0, -3);
			
			if ($primary_keys_ != array()) {
				$query .= ' , ';
				$query .= 'PRIMARY KEY  (';
				foreach ($primary_keys_ as $key_name) {
					$query .= '`'.$key_name.'` , ';
				}
				$query = substr($query, 0, -3);
				$query .= ')';
			}
			$query .= ') DEFAULT CHARSET=utf8;';
			$ret = $this->DoQuery($query, $name_);
			return $ret;
			
		}
		else {
			// TODO : see if it works when we change the primary key
		
			// the table exists, it is the right structure ?
			$res = $this->DoQuery('SHOW COLUMNS FROM @1', $name_);
			if ($res !== false){
				$rows = $this->FetchAllResults($res);
				$columns_from_database = array();
				foreach($rows as $row) {
					if (in_array( $row['Field'], array_keys($table_structure_))) {
						// the column exists
						// it's the same type ?
						$ret2 = $this->DoQuery('SHOW COLUMNS FROM @1 WHERE @2=%3', $name_, 'Field', $row['Field']);
						if ($ret2 === false) {
							Logger::error('main', 'SQL::createTable failed to get type of \''.$row['Field'].'\'');
							return false;
						}
						$rows6 = $this->FetchResult();
						$field_type = $rows6['Type'];
						$type6 = explode(' ', $table_structure_[$row['Field']]);
						if (isset($type6[0])) {
							if ($type6[0] !== $field_type) {
								// it's not the same -> we will alter the table
								$this->DoQuery('ALTER TABLE @1 CHANGE @2 @2 '.$table_structure_[$row['Field']], $name_, $row['Field']);
							}
						}
						$columns_from_database[] = $row['Field'];
					}
					else {
						// we must remove this column
						$res = $this->DoQuery('ALTER TABLE @1 DROP @2', $name_, $row['Field']);
						if ($res == false)
							Logger::error('main', 'SQL::createTable failed to remove \''.$row['Field'].'\' from the table \''.$name_.'\'');
					}
				}
				
				foreach($table_structure_ as $column_name => $column_structure) {
					if (!in_array($column_name, $columns_from_database)) {
						$res = $this->DoQuery('ALTER TABLE @1 ADD @2 '.$column_structure, $name_, $column_name);
						if ($res == false)
							Logger::error('main', "SQL::createTable failed to add '$columns_from_database' in the table '$name_'");
					}
				}
			}
			// TODO : what about primary key
			
			return true;
		}
	}

	public function Quote($string_) {
		return '"'.$this->CleanValue($string_).'"';
	}
}
