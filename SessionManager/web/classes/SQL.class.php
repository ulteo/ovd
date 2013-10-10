<?php
/**
 * Copyright (C) 2008-2014 Ulteo SAS
 * http://www.ulteo.com
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2008
 * Author Laurent CLOUET <laurent@ulteo.com> 2009
 * Author Julien LANGLOIS <julien@ulteo.com> 2012, 2013
 * Author David PHAM-VAN <d.pham-van@ulteo.com> 2012, 2014
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

	public function CheckLink($die_=true, $event_=true) {
		if ($this->link)
			return;

		if ($event_) {
			$ev = new SqlFailure(array('host' => $this->sqlhost));
		}

		$this->link = @mysqli_connect($this->sqlhost, $this->sqluser, $this->sqlpass);

		if (! $this->link) {
			if ($die_) {
				if ($event_) {
					$ev->setAttribute('status', -1);
					$ev->emit();
				}
				
				die_error('Link to SQL server failed.',__FILE__,__LINE__);
			}

			return false;
		}

		if ($this->SelectDB($this->sqlbase) === false) {
			if ($die_) {
				if ($event_) {
					$ev->setAttribute('status', -1);
					$ev->emit();
				}
				
				die_error('Could not select database.',__FILE__,__LINE__);
			}

			return false;
		}

		$this->DoQuery('SET NAMES utf8');
		if ($event_) {
			$ev->setAttribute('status', 1);
			$ev->emit();
		}
		
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

		$query = preg_replace('/#([0-9]+)/se', '((! array_key_exists(\\1, $args))?\'@\\1\':(is_null($args[\\1])?\'NULL\':\'`\'.$this->prefix.mysqli_real_escape_string($this->link, $args[\\1]).\'`\'))', $query);
		
		$query = preg_replace('/@([0-9]+)/se', '((! array_key_exists(\\1, $args))?\'@\\1\':(is_null($args[\\1])?\'NULL\':\'`\'.mysqli_real_escape_string($this->link, $args[\\1]).\'`\'))', $query);
		$query = preg_replace('/%([0-9]+)/se', '((! array_key_exists(\\1, $args))?\'%\\1\':(is_null($args[\\1])?\'NULL\':\'"\'.$this->CleanValue($args[\\1]).\'"\'))', $query);

		if (is_resource($this->result)) {
			mysqli_free_result($this->result);
			$this->result = false;
		}

		$this->result = @mysqli_query($this->link, $query);
		if ($this->result === false) {
			Logger::error('main', 'SQL error: '.mysqli_error($this->link).'; Query: '.$query);
			return false;
		}

		$this->total_queries += 1;

		return true;
	}

	public function TableExists($table_) {
		$this->DoQuery('SHOW TABLES LIKE %1', $this->prefix.$table_);
		return $this->NumRows() == 1;
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

	public function FetchArrayAll() {
		$this->CheckLink();

		if (! $this->result)
			return false;

		$res = array();

		while ($r = @mysql_fetch_array($this->result))
			$res[] = $r;

		return $res;
	}

	public function NumRows() {
		$this->CheckLink();

		if (! $this->result)
			return false;

		return @mysqli_num_rows($this->result);
	}
	
	public function AffectedRows() {
		$this->CheckLink();
		
		return mysqli_affected_rows($this->link);
	}

	public function InsertId() {
		$this->CheckLink();

		return @mysqli_insert_id($this->link);
	}

	public function TotalQueries() {
		return $this->total_queries;
	}
	
	public function buildTable($name_, $table_structure_, $primary_keys_, $indexes_ = array(), $engine_=null) {
		$this->CheckLink();
		
		// the table exists ?
		$table_exists = false;
		$ret = $this->DoQuery('SHOW TABLES LIKE %1', $this->prefix.$name_);
		if ($ret !== false) {
			$ret2 = $this->NumRows($ret);
			if ($ret2 == 1)
				$table_exists =  true;
			else
				$table_exists = false;
		}
		if (! $table_exists) {
			$query  = 'CREATE TABLE IF NOT EXISTS #1 (';
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
			
			foreach($indexes_ as $name=>$index) {
				if (substr($name, 0, 2) === 'U_')
					$name = 'UNIQUE '.$name;
				else
					$name = 'INDEX '.$name;
				
				$query .= ' , '.$name.' (`'.implode('`, `', $index).'`)';
			}
			$query .= ')';
			if (! is_null($engine_)) {
				$query .= ' ENGINE='.$engine_;
			}
			
			$query .= ' DEFAULT CHARSET=utf8;';
			$ret = $this->DoQuery($query, $name_);
			return $ret;
			
		}
		else {
			$query_queue = array();
			
			// the table exists, it is the right structure ?
			$res = $this->DoQuery('SHOW COLUMNS FROM #1', $name_);
			if ($res !== false){
				$rows = $this->FetchAllResults($res);
				$columns_from_database = array();
				foreach($rows as $row) {
					if (in_array( $row['Field'], array_keys($table_structure_))) {
						// the column exists
						// it's the same type ?
						$ret2 = $this->DoQuery('SHOW COLUMNS FROM #1 WHERE @2=%3', $name_, 'Field', $row['Field']);
						if ($ret2 === false) {
							Logger::error('main', 'SQL::createTable failed to get type of \''.$row['Field'].'\'');
							return false;
						}
						$rows6 = $this->FetchResult();
						$field_type = $rows6['Type'];
						$type6 = explode(' ', $table_structure_[$row['Field']]);
						if (isset($type6[0])) {
							if (strtoupper($type6[0]) !== strtoupper($field_type)) {
								// it's not the same -> we will alter the table
								$query_queue[] = 'MODIFY `'.$row['Field'].'` '.$table_structure_[$row['Field']];
							}
						}
						$columns_from_database[] = $row['Field'];
					}
					else {
						// we must remove this column
						$query_queue[] = 'DROP `'.$row['Field'].'`';
					}
				}
				
				foreach($table_structure_ as $column_name => $column_structure) {
					if (!in_array($column_name, $columns_from_database)) {
						$query_queue[] = 'ADD `'.$column_name.'` '.$column_structure;
					}
				}
			}
			
			// look for indexes structure
			$keys = array();
			$res = $this->DoQuery('SHOW INDEXES FROM #1', $name_);
			if ($res !== false) {
				$rows = $this->FetchAllResults($res);
				foreach($rows as $row) {
					if (!array_key_exists($row['Key_name'], $keys))
						$keys[$row['Key_name']] = array();
					
					$keys[$row['Key_name']][] = $row['Column_name'];
				}
			}
			
			$process_indexes = false;
			
			if ((count($primary_keys_) > 0) != array_key_exists('PRIMARY', $keys))
				$process_indexes = true;
			
			foreach($indexes_ as $name=>$index) {
				if ((count($index) > 0) != array_key_exists($name, $keys)) {
					$process_indexes = true;
					break;
				}
				
				if (array_diff($keys[$name], $index) != array_diff($index, $keys[$name])) {
					$process_indexes = true;
					break;
				}
			}
			
			if (!$process_indexes && array_key_exists('PRIMARY', $keys) && array_diff($keys['PRIMARY'], $primary_keys_) != array_diff($primary_keys_, $keys['PRIMARY']))
				$process_indexes = true;
			
			// is the database to be updated ?
			if (count($query_queue) > 0 || $process_indexes) {
				// drop all indexes
				foreach(array_keys($keys) as $key) {
					if ($key == "PRIMARY")
						$key .= " KEY";
					else
						$key = "KEY ".$key;
					
					array_unshift($query_queue, 'DROP '.$key);
				}
				
				// add primary key
				if (count($primary_keys_) > 0)
					$query_queue[] = 'ADD PRIMARY KEY  (`'.implode('`, `', $primary_keys_).'`)';
				
				// add indexes
				foreach($indexes_ as $name=>$index) {
					if (substr($name, 0, 2) === 'U_')
						$query_queue[] = 'ADD  UNIQUE '.$name.' (`'.implode('`, `', $index).'`)';
					else
						$query_queue[] = 'ADD  INDEX '.$name.' (`'.implode('`, `', $index).'`)';
				}
				
				$res = $this->DoQuery('ALTER TABLE #1 '.implode(', ', $query_queue), $name_);
				if ($res == false)
					Logger::error('main', 'SQL::createTable failed to modify table '.$name_);
			}
			return true;
		}
	}

	public function Quote($string_) {
		return '"'.$this->CleanValue($string_).'"';
	}

	public function QuoteField($field_) {
		if (! is_string($field_)) {
			return $field_;
		}
		
		$this->CheckLink();
		
		return '`'.mysqli_real_escape_string($this->link, $field_).'`';
	}
}
