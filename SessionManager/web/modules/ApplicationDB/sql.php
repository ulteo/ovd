<?php
/**
 * Copyright (C) 2008,2009 Ulteo SAS
 * http://www.ulteo.com
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
require_once(dirname(__FILE__).'/../../includes/core.inc.php');

class ApplicationDB_sql extends ApplicationDB {
	public function __construct(){
		$prefs = Preferences::getInstance();
		$mysql_conf = $prefs->get('general', 'mysql');
		if (is_array($mysql_conf)) {
			@define('APPLICATION_TABLE', $mysql_conf['prefix'].'application');
		}
	}

	public function import($id_){
		Logger::debug('main', "ApplicationDB_sql::import($id_)");
		$sql2 = SQL::getInstance();
		$res = $sql2->DoQuery('SELECT * FROM @1 WHERE @2=%3',APPLICATION_TABLE,'id',$id_);
		if ($res !== false){
			if ($sql2->NumRows($res) == 1){
				$row = $sql2->FetchResult($res);
				$a = $this->generateApplicationFromRow($row);
				if ($this->isOK($a))
					return $a;
			}
		}
		return NULL;
	}

	// todo ugly
	public function search($app_name,$app_description,$app_type,$app_path_exe){
	Logger::debug('main',"ApplicationDB_sql::search ('".$app_name."','".$app_description."','".$app_type."','".$app_path_exe."')");
// 		echo "ApplicationDB_sql::search ('".$app_name."','".$app_description."','".$app_type."','".$app_path_exe."')\n";
		$sql2 = SQL::getInstance();
		$res = $sql2->DoQuery('SELECT @1 FROM @2 WHERE
		@3 = %4 AND @5 = %6 AND @7 = %8 AND @9 = %10', 'id', APPLICATION_TABLE, 'name', $app_name, 'description', $app_description, 'type', $app_type, 'executable_path', $app_path_exe);
		if ($res !== false){
			if ($sql2->NumRows($res) > 0){
				$row = $sql2->FetchResult($res);
				return $this->import($row['id']);
			}
		}
		return NULL;
	}

	public function getList($sort_=false, $type_=NULL){
		Logger::debug('main', "ApplicationDB_sql::getList(sort=$sort_, type=$type_)");
		$sql2 = SQL::getInstance();
		if (is_null($type_))
			$res = $sql2->DoQuery('SELECT * FROM @1',APPLICATION_TABLE);
		else
			$res = $sql2->DoQuery('SELECT * FROM @1 WHERE @2=%3',APPLICATION_TABLE, 'type', $type_);
		
		if ($res !== false){
			$result = array();
			$rows = $sql2->FetchAllResults($res);
			foreach ($rows as $row){
				$a = $this->generateApplicationFromRow($row);
				if ($this->isOK($a))
					$result [$a->getAttribute('id')]= $a;
			}
			// do we need to sort alphabetically ?
			if ($sort_) {
				usort($result, "application_cmp");
			}
			return $result;
		}
		else {
			// not the right argument
			return NULL;
		}
	}

	public function isWriteable(){
		return true;
	}

	public function isOK($app_){
		$minimun_attribute = array('id','name','type','executable_path','published');
		if (is_object($app_)){
			foreach ($minimun_attribute as $attribute){
				if ($app_->hasAttribute($attribute) == false)
					return false;
			}
			return true;
		}
		else
			return false;
	}

	public function generateApplicationFromRow($row){
		if ((!isset($row['id'])) || (!isset($row['name'])) || (!isset($row['type'])) || (!isset($row['executable_path'])) || (!isset($row['published']))) {
			// no right attribute, we do nothing
			Logger::info('main','ApplicationDB_sql::getList app not insert'); // todo right the content
			return NULL;
		}
		else{
			if (!isset($row['package']))
				$row['package'] = NULL;
			if (!isset($row['icon_path']))
				$row['icon_path'] = NULL;
			if ( $row['type'] == 'weblink') {
				$r = new Application_weblink($row['id'], $row['name'],$row['description'], $row['executable_path']);
				
				unset($row['id']);
				unset($row['name']);
				unset($row['description']);
				unset($row['type']);
				unset($row['executable_path']);
				unset($row['package']);
				unset($row['icon_path']);
				unset($row['desktopfile']);
				unset($row['mimetypes']);
				
			}
			else {
				$r = new Application($row['id'], $row['name'],$row['description'], $row['type'], $row['executable_path'], $row['package'], $row['icon_path'], $row['mimetypes'], $row['published']);
				
			}
			foreach ($row as $key => $value){
				$r->setAttribute($key,$value);
			}
			return $r;
		}
	}
	
	public static function configuration() {
		return array();
	}

	public static function prefsIsValid($prefs_, &$log=array()) {
		if (!defined('APPLICATION_TABLE'))
			return false;
		$mysql_conf = $prefs_->get('general', 'mysql');
		if (!is_array($mysql_conf)) {

			return false;
		}
		$sql2 = SQL::newInstance($mysql_conf['host'], $mysql_conf['user'], $mysql_conf['password'], $mysql_conf['database'], $mysql_conf['prefix']);
		$ret = $sql2->DoQuery('SHOW TABLES FROM @1 LIKE %2',$mysql_conf['database'],APPLICATION_TABLE);
		if ($ret !== false) {
			$ret2 = $sql2->NumRows($ret);
			if ($ret2 == 1) {
				return true;
			}
			else {
				Logger::error('main', 'APPLICATIONSDB_SQL table \''.APPLICATION_TABLE.'\' does not exist');
				return false;
			}
		}
		else {
			Logger::error('main', 'APPLICATIONSDB_SQL table \''.APPLICATION_TABLE.'\' does not exist(2)');
			return false;
		}
	}

	public static function prettyName() {
		return _('MySQL');
	}

	public static function isDefault() {
		return true;
	}
}
