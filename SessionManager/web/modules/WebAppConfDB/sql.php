<?php
/**
 * Copyright (C) 2008-2013 Ulteo SAS
 * http://www.ulteo.com
 * Author Wojciech LICHOTA <wojciech.lichota@stxnext.pl> 2013
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
require_once(dirname(__FILE__).'/../../includes/core.inc.php');

class WebAppConfDB_sql extends ApplicationDB {
	const table = 'application_webapp_configuration';
	
	protected $cache;
	public function __construct(){
		$this->cache = array();
	}
	
	public function import($id_) {
		if (array_key_exists($id_, $this->cache)) {
			return $this->cache[$id_];
		}
		else {
			$conf = $this->import_nocache($id_);
			if (is_object($conf)) {
				$this->cache[$conf->getAttribute('id')] = $conf;
				return $conf;
			}
			return $conf;
		}
	}

	protected function import_nocache($id_){
		$sql2 = SQL::getInstance();
		$res = $sql2->DoQuery('SELECT * FROM #1 WHERE @2=%3',self::table, 'id', $id_);
		if ($res !== false){
			if ($sql2->NumRows($res) == 1){
				$row = $sql2->FetchResult($res);
				$conf = $this->generateObjectFromRow($row);
				if ($this->isOK($conf))
					return $conf;
			}
		}
		return NULL;
	}

	public function search($application_id){
	Logger::debug('main',"WebAppConfDB_sql::search ('".$application_id."')");
		$sql2 = SQL::getInstance();
		$res = $sql2->DoQuery('SELECT @1 FROM #2 WHERE @3 = %4', 'id', self::table, 'application_id', $application_id);
		if ($res !== false){
			if ($sql2->NumRows($res) > 0){
				$row = $sql2->FetchResult($res);
				return $this->import($row['id']);
			}
		}
		return NULL;
	}

	public function generateObjectFromRow($row){
        $conf = new Application_webapp_configuration($row['id'], $row['application_id'],$row['raw_configuration']);

        unset($row['id']);
        unset($row['application_id']);
        unset($row['raw_configuration']);

        foreach ($row as $key => $value){
            $conf->setAttribute($key, $value);
        }
        return $conf;
	}
    
	public function isWriteable(){
		return true;
	}

	public function isOK($conf){
		$minimun_attribute = array('raw_configuration', 'application_id');
		if (is_object($conf)){
			foreach ($minimun_attribute as $attribute){
				if ($conf->hasAttribute($attribute) == false) {
					Logger::info('api', "$attribute is missing");
					return false;
				}
				if ($conf->getAttribute($attribute) === "") {
					Logger::info('api', "$attribute is empty");
					return false;
				}
			}
			return true;
		}
		else
			return false;
	}

	public static function configuration() {
		return array();
	}

	public static function prefsIsValid($prefs_, &$log=array()) {
		$sql_conf = $prefs_->get('general', 'sql');
		if (!is_array($sql_conf)) {

			return false;
		}
		$sql2 = SQL::newInstance($sql_conf);
		$ret = $sql2->DoQuery('SHOW TABLES FROM @1 LIKE %2', $sql_conf['database'], $sql2->prefix.self::table);
		if ($ret !== false) {
			$ret2 = $sql2->NumRows($ret);
			if ($ret2 == 1) {
				return true;
			}
			else {
				Logger::error('main', 'WebAppConfDB_sql table \''.self::table.'\' does not exist');
				return false;
			}
		}
		else {
			Logger::error('main', 'WebAppConfDB_sql table \''.self::table.'\' does not exist(2)');
			return false;
		}
	}

	public static function prettyName() {
		return _('MySQL');
	}

	public static function isDefault() {
		return true;
	}
	
	public function add($conf){
		if (is_object($conf)){
			$query_keys = array();
			$query_values = array();
			$attributes = $conf->getAttributesList();
			$result = array_search('id', $attributes);
			if ($result !== false) {
				unset($attributes[$result]); // the id is an auto_increment
			}
			foreach ($attributes as $key){
				$query_keys   []= '`'.$key.'`';
				$query_values []= '"'.mysql_escape_string($conf->getAttribute($key)).'"';
			}
			$query_keys = implode(', ', $query_keys);
			$query_values = implode(', ', $query_values);
			$sql2 = SQL::getInstance();
			$res = $sql2->DoQuery('INSERT INTO #1 ( '.$query_keys.' ) VALUES ('.$query_values.' )', self::table);
			$id = $sql2->InsertId();
			$conf->setAttribute('id', $id);
			if ($res === false)
				return false;

			return true;
		}
		return false;

	}

	public function remove($conf){
		if (array_key_exists($conf->getAttribute('id'), $this->cache)) {
			unset($this->cache[$conf->getAttribute('id')]);
		}
		if (is_object($conf) && $conf->hasAttribute('id') && is_numeric($conf->getAttribute('id'))) {
			$sql2 = SQL::getInstance();
			$res = $sql2->DoQuery('DELETE FROM #1 WHERE @2 = %3', self::table, 'id', $conf->getAttribute('id'));
			return ($res !== false);
		}
		else
			return false;
	}

	public function update($conf){
		if (array_key_exists($conf->getAttribute('id'), $this->cache)) {
			unset($this->cache[$conf->getAttribute('id')]);
		}
		if ($this->isOK($conf)){
			$query = 'UPDATE#1 SET ';
			$attributes = $conf->getAttributesList();
			foreach ($attributes as $key){
				$query .=  '`'.$key.'` = \''.mysql_escape_string($conf->getAttribute($key)).'\' , ';
			}
			$query = substr($query, 0, -2); // del the last ,
			$query .= ' WHERE `id` =\''.$conf->getAttribute('id').'\'';

			$sql2 = SQL::getInstance();
			$res = $sql2->DoQuery($query, self::table);
			if ($res === false)
				return false;
			
			return true;
		}
		return false;
	}

	public static function init($prefs_) {
		Logger::debug('main', 'WebAppConfDB::sql::init');
		$sql_conf = $prefs_->get('general', 'sql');
		if (!is_array($sql_conf)) {
			Logger::error('main', 'WebAppConfDB::sql::init sql conf not valid');
			return false;
		}
		
		$sql2 = SQL::newInstance($sql_conf);
		$WEBAPPCONF_table_structure = array(
			'id' => 'int(8) NOT NULL auto_increment',
			'application_id' => 'int(8) NOT NULL',
			'raw_configuration' => 'text NOT NULL',
		);

		$ret = $sql2->buildTable(self::table, $WEBAPPCONF_table_structure, array('id'));

		if ( $ret === false) {
			Logger::error('main', 'WebAppConfDB::sql::init table '.self::table.' fail to created');
			return false;
		}
		else {
			Logger::debug('main', 'WebAppConfDB::sql::init table '.self::table.' created');
			return true;
		}
	}

	public static function enable() {
		return true;
	}	

}
