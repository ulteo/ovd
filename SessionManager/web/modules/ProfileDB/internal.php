<?php
/**
 * Copyright (C) 2009-2012 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2009 - 2011
 * Author Julien LANGLOIS <julien@ulteo.com> 2011-2012
 * Author David LECHEVALIER <david@ulteo.com> 2012
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

class ProfileDB_internal extends ProfileDB  {
	public static $table="profile";
	
	public static function prettyName() {
		return _('Internal');
	}
	
	public static function isDefault() {
		return true;
	}
	
	public static function enable() {
		return true;
	}
	
	public static function prefsIsValid($prefs_, &$log=array()) {
		return true;
	}
	
	public static function configuration() {
		return array();
	}
	
	public static function init($prefs_) {
		Logger::debug('main', 'ProfileDB::internal::init');
		
		$sql_conf = $prefs_->get('general', 'sql');
		$SQL = SQL::newInstance($sql_conf);
		
		$Profile_table_structure = array('id' => 'varchar(255)');
		
		$ret = $SQL->buildTable(self::$table, $Profile_table_structure, array('id'));
		
		Logger::debug('main', "ProfileDB::internal::init SQL table '".self::$table."' created");
		return true;
	}
	
	public function exists($id_) {
		Logger::debug('main', "ProfileDB::internal::exists($id_)");
		if (is_null($id_)) {
			return false;
		}
		
		$SQL = SQL::getInstance();
		
		$SQL->DoQuery('SELECT @1 FROM #2 WHERE @1 = %3 LIMIT 1', 'id', self::$table, $id_);
		$total = $SQL->NumRows();
		
		return ($total == 1);
	}
	
	public function isInternal() {
		return true;
	}
	
	public function import($id_) {
		Logger::debug('main', "ProfileDB::internal::import($id_)");
		$SQL = SQL::getInstance();
		
		$SQL->DoQuery('SELECT * FROM #1 as p, #2 as n WHERE p.@3 = n.@3 AND p.@3 = %4 LIMIT 1', self::$table, Abstract_Network_Folder::$table, 'id', $id_);
		$total = $SQL->NumRows();
		
		if ($total == 0) {
			Logger::error('main', "ProfileDB::internal::load($id_) failed: NumRows == 0");
			return false;
		}
		
		$row = $SQL->FetchResult();
		$buf = self::generateFromRow($row);
		return $buf;
	}
	
	public function importFromUser($login_) {
		$liaisons = Abstract_Liaison::load('UserProfile', $login_, NULL);
		if (is_array($liaisons) == false) {
			Logger::error('main', "ProfileDB::internal::importFromUser($login_) problem with liaison");
			return false;
		}
		$profiles = array();
		foreach ($liaisons as $liaison) {
			$profile = self::import($liaison->group);
			if (! is_object($profile))
				continue;
			
			$profiles[$profile->id] = $profile;
		}
		return $profiles;
	}
	
	public function importFromServer($server_id_) {
		Logger::debug('main', "ProfileDB::internal::importFromServer($server_id_)");
		$SQL = SQL::getInstance();
		
		$SQL->DoQuery('SELECT * FROM #1 as p, #2 as n WHERE p.@3 = n.@3 AND @4 = %5', self::$table, Abstract_Network_Folder::$table, 'id', 'server', $server_id_);
		$rows = $SQL->FetchAllResults();
		
		$profiles = array();
		foreach ($rows as $row) {
			$profile = self::generateFromRow($row);
			if (! is_object($profile))
				continue;
			
			$profiles[$profile->id] = $profile;
		}
		
		return $profiles;
	}
	
	public function getList($order_=false) {
		Logger::debug('main', 'ProfileDB::internal::getList()');
		$SQL = SQL::getInstance();
		
		$SQL->DoQuery('SELECT * FROM #1 as p, #2 as n WHERE p.@3 = n.@3', self::$table, Abstract_Network_Folder::$table, 'id');
		$rows = $SQL->FetchAllResults();
		
		$profiles = array();
		foreach ($rows as $row) {
			$profile = self::generateFromRow($row);
			if (! is_object($profile))
				continue;
			
			$profiles[$profile->id] = $profile;
		}
		
		return $profiles;
	}
	
	public function count() {
		Logger::debug('main', 'ProfileDB::internal::count()');
		
		$SQL = SQL::getInstance();
		
		$SQL->DoQuery('SELECT 1 FROM #1', self::$table);
		$nb_rows = $SQL->NumRows();
		
		return $nb_rows;
	}
	
	public function countOnServer($id_) {
		Logger::debug('main', "ProfileDB::internal::countOnServer($id_)");
		
		$SQL = SQL::getInstance();
		
		$SQL->DoQuery('SELECT * FROM #1 as p, #2 as n WHERE p.@3 = n.@3 AND @4=%5', self::$table, Abstract_Network_Folder::$table, 'id', 'server', $id_);
		$nb_rows = $SQL->NumRows();
		
		return $nb_rows;
	}
	
	protected function generateFromRow($row_) {
		if (array_key_exists('id', $row_) == false || array_key_exists('server', $row_) == false || array_key_exists('status', $row_) == false) {
			Logger::error('main', 'ProfileDB::internal::generateFromRow row not ok, row '.serialize($row_));
			return NULL;
		}
		
		return new Profile($row_['id'], $row_['server'], (int)$row_['status']);
	}
	
	public function addToServer($profile_, $an_fs_server_) {
		Logger::debug('main', "ProfileDB::internal::addToServer($profile_, $an_fs_server_)");
		if (is_object($profile_) == false) {
			Logger::error('main', 'ProfileDB::internal sharedfolder parameter is not an object (content: '.serialize($profile_));
			return false;
		}
		if (is_object($an_fs_server_) == false) {
			Logger::error('main', 'ProfileDB::internal fs parameter is not an object (content: '.serialize($an_fs_server_));
			return false;
		}
		
		$profile_->server = $an_fs_server_->id;
		if ($this->exists($profile_->id) === false) {
			// we save the object first
			$ret = $this->add($profile_);
// 			if ($ret !== true) {
// 				Logger::error('main', "ProfileDB::internal failed to add $profile_ to the DB");
// 				return false;
// 			}
		}
		
		// do the request to the server
		$ret = $an_fs_server_->createNetworkFolder($profile_->id);
		if (! $ret) {
			ErrorManager::report('ProfileDB::internal Unable to create shared folder on file server "'.$profile_->server.'"');
			
			$this->remove($profile_);
			return false;
		}
		return true;
	}
	
	private function add($profile_) {
		$SQL = SQL::getInstance();
		
		if (is_null($profile_->id)) {
			$profile_->id = 'p_'.gen_unique_string(); // $SQL->InsertId();
		}
		
		$SQL->DoQuery('INSERT INTO #1 (@2) VALUES (%3)', self::$table, 'id', $profile_->id);
		Abstract_Network_Folder::save($profile_);
		
		return $profile_->id;
	}
	
	public function remove($profile_id_) {
		Logger::debug('main', "ProfileDB::internal::remove($profile_id_)");
		$profile = $this->import($profile_id_);
		if (is_object($profile) == false ) {
			Logger::error('main', "ProfileDB::internal::remove($profile_id_) failed, unable to import profile");
			return false;
		}
		
		if (self::invalidate($profile_id_) == false)
			return false;
		
		if (Abstract_Network_Folder::delete($profile_id_) == false)
			return false;
		
		return true;
	}
	
	public function invalidate($profile_id_) {
		Logger::debug('main', "ProfileDB::internal::invalidate($profile_id_)");
		
		Abstract_Liaison::delete('UserProfile', NULL, $profile_id_);
		$SQL = SQL::getInstance();
		$SQL->DoQuery('DELETE FROM #1 WHERE @2 = %3 LIMIT 1', self::$table, 'id', $profile_id_);
		
		return true;
	}
	
	public function addUserToProfile($user_, $profile_) {
		if (! is_object($user_)) {
			Logger::error('main', "ProfileDB::internal::addUserToProfile, parameter 'user' is not correct, user: ".serialize($user_));
			return false;
		}
		if (! is_object($profile_)) {
			Logger::error('main', "ProfileDB::internal::addUserToProfile, parameter 'profile' is not correct, profile: ".serialize($profile_));
			return false;
		}
		return Abstract_Liaison::save('UserProfile', $user_->getAttribute('login'), $profile_->id);
	}
	
	public static function update($profile_) {
		Logger::debug('main', 'ProfileDB::internal::update for \''.$profile_->id.'\'');
		
		return Abstract_Network_Folder::save($profile_);
	}
	
	public function chooseFileServer() {
		$available_servers = Abstract_Server::load_available_by_role_sorted_by_load_balancing(Server::SERVER_ROLE_FS);
		if (is_array($available_servers)) {
			$server = array_shift($available_servers);
			if (is_object($server)) {
				return $server;
			}
		}
		return false;
	}
}
 
