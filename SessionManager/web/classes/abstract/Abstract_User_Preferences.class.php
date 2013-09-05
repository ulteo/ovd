<?php
/**
 * Copyright (C) 2011-2013 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2011
 * Author Jocelyn DELALANDE <j.delalande@ulteo.com> 2012
 * Author Julien LANGLOIS <julien@ulteo.com> 2012, 2013
 * Author David PHAM-VAN <d.pham-van@ulteo.com> 2012
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

/**
 * Abstraction layer between the User_Preferences instances and the SQL backend.
 */
class Abstract_User_Preferences {
	private static $table = 'user_preferences';
	
	public static function init($prefs_) {
		Logger::debug('main', 'Abstract_User_Preferences::init');
		
		$sql_conf = $prefs_->get('general', 'sql');
		$SQL = SQL::newInstance($sql_conf);
		
		$user_preferences_table_structure = array(
			'login'		=>	'varchar(1024)',
// 			'type'		=>	'varchar(255)',
			'key'		=>	'varchar(255)',
			'container'	=>	'varchar(255)',
			'element_id'=>	'varchar(255)',
			'value'		=>	'text'
		);
		
		$ret = $SQL->buildTable(self::$table, $user_preferences_table_structure, array());
		if (! $ret) {
			Logger::error('main', "Unable to create MySQL table '".self::$table."'");
			return false;
		}
		
		Logger::debug('main', "MySQL table '".self::$table."' created");
		return true;
	}
	
	public static function loadByUserLogin($login_, $key_, $container_) {
		$ret = array();
		$sql = SQL::getInstance();
		$res = $sql->DoQuery('SELECT @1,@2,@3,@4,@5 FROM #6 WHERE @1 = %7 AND @2 = %8 AND @3 = %9', 'login', 'key', 'container', 'element_id', 'value', self::$table, $login_, $key_, $container_);
		
		if ($res !== true) {
			Logger::error('main', "Abstract_User_Preferences::loadByUserLogin($login_,$key_,$container_) sql request failed");
		}
		else {
			$rows = $sql->FetchAllResults();
			foreach ($rows as $row) {
				$ret[$row['element_id']] = new UserGroup_Preferences($row['login'], $row['key'], $row['container'], $row['element_id'], json_unserialize($row['value']));
			}
		}
		
		return $ret;
	}
	
	public static function save($user_prefs_) {
		$sql = SQL::getInstance();
		return $res = $sql->DoQuery('INSERT INTO #1 (@2,@3,@4,@5,@6) VALUES (%7,%8,%9,%10,%11)', self::$table, 'login', 'key', 'container', 'element_id', 'value', $user_prefs_->login, $user_prefs_->key, $user_prefs_->container, $user_prefs_->element_id, json_serialize($user_prefs_->value));
	}
	
	public static function delete($userlogin_, $key_, $container_, $element_id_) {
		$sql = SQL::getInstance();
		return $sql->DoQuery('DELETE FROM #1 WHERE @2 = %3 AND @4 = %5 AND @6 = %7 AND @8 = %9', self::$table, 'login', $userlogin_, 'key', $key_, 'container', $container_, 'element_id', $element_id_);
	}
	
	public static function deleteByUserLogins($userlogins_) {
		if (! is_array($userlogins_) || count($userlogins_) == 0) {
			return false;
		}
		
		$sql = SQL::getInstance();
		
		$logins2 = array();
		foreach($userlogins_ as $login) {
			array_push($logins2, '"'.$sql->CleanValue($login).'"');
		}
		
		return $sql->DoQuery('DELETE FROM #1 WHERE @2 IN ('.implode(', ',$logins2).')', self::$table, 'login');
	}

	public static function delete_all() {
		$sql = SQL::getInstance();
		return $sql->DoQuery('DELETE FROM #1', self::$table);
	}
	
	public static function get_users() {
		$sql = SQL::getInstance();
		$res = $sql->DoQuery('SELECT COUNT(*) FROM #1 GROUP BY @2', self::$table, 'login');
		if ($res !== true) {
			Logger::error('main', "Abstract_User_Preferences::get_users sql request failed");
			return array();
		}
		
		$users = array();
		$rows = $sql->FetchArrayAll();
		foreach ($rows as $row) {
			array_push($users, $row['login']);
		}
		
		return $users;
	}
}
