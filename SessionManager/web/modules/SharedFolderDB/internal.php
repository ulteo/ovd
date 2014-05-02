<?php
/**
 * Copyright (C) 2009-2014 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2009-2010
 * Author Julien LANGLOIS <julien@ulteo.com> 2012, 2013, 2014
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
require_once(dirname(__FILE__).'/../../includes/core.inc.php');

class SharedFolderDB_internal  extends SharedFolderDB {
	public static $table = 'shared_folder';
	public static $table_publication = 'shared_folder_publication';
	
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
		Logger::debug('main', 'SharedFolderDB::internal::init');
		
		$sql_conf = $prefs_->get('general', 'sql');
		$SQL = SQL::newInstance($sql_conf);
		
		$SharedFolder_table_structure = array(
			'id'				=>	'varchar(255)', //'id'			=>	'int(8) NOT NULL auto_increment',
			'name'			=>	'varchar(255)',
		);
		
		$ret = $SQL->buildTable(self::$table, $SharedFolder_table_structure, array('id'));
		
		$SharedFolder_publication_table_structure = array(
			'id'			=>	'varchar(255)',
			'group'			=>	'varchar(255)',
			'mode'			=>	'varchar(4)',
		);
		
		$ret = $SQL->buildTable(self::$table_publication, $SharedFolder_publication_table_structure, array());
		
		Logger::debug('main', "SharedFolderDB::internal::init SQL table '".self::$table."' created");
		return true;
	}
	
	public function exists($id_) {
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
		Logger::debug('main', "SharedFolderDB_internal::import($id_)");
		$SQL = SQL::getInstance();
		
		$SQL->DoQuery('SELECT * FROM #1 as p, #2 as n WHERE p.@3 = n.@3 AND p.@3 = %4 LIMIT 1', self::$table, Abstract_Network_Folder::$table, 'id', $id_);
		$total = $SQL->NumRows();
		
		if ($total == 0) {
			Logger::error('main', "SharedFolderDB::internal::import($id_) failed: NumRows == 0");
			return false;
		}
		
		$row = $SQL->FetchResult();
		$buf = self::generateFromRow($row);
		return $buf;
	}
	
	public function importFromServer($server_id_) {
		Logger::debug('main', "SharedFolderDB::internal::importFromServer($server_id_)");
		$SQL = SQL::getInstance();
		
		$SQL->DoQuery('SELECT * FROM #1 as p, #2 as n WHERE p.@3 = n.@3 AND @4 = %5', self::$table, Abstract_Network_Folder::$table, 'id', 'server', $server_id_);
		$rows = $SQL->FetchAllResults();
		
		$sharedfolders = array();
		foreach ($rows as $row) {
			$sharedfolder = self::generateFromRow($row);
			if (! is_object($sharedfolder))
				continue;
			
			$sharedfolders[$sharedfolder->id] = $sharedfolder;
		}
		
		return $sharedfolders;
	}
	
	public function importFromName($a_name_) {
		Logger::debug('main', "SharedFolderDB::internal::importFromName($a_name_)");
		$SQL = SQL::getInstance();
		
		$SQL->DoQuery('SELECT * FROM #1 as p, #2 as n WHERE p.@3 = n.@3 AND @4 = %5 LIMIT 1', self::$table, Abstract_Network_Folder::$table, 'id', 'name', $a_name_);
		$rows = $SQL->FetchAllResults();
		
		$sharedfolders = array();
		foreach ($rows as $row) {
			$sharedfolder = self::generateFromRow($row);
			if (! is_object($sharedfolder))
				continue;
			
			$sharedfolders[$sharedfolder->id] = $sharedfolder;
		}
		
		return $sharedfolders;
	}
	
	public function importFromUsergroup($group_id_) {
		Logger::debug('main', "SharedFolderDB::internal::importFromUsergroup($group_id_)");
		$SQL = SQL::getInstance();
		
		$SQL->DoQuery('SELECT * FROM #1 as s, #2 as n, #3 as p WHERE s.@4 = n.@4 AND s.@4 = p.@4 AND @5 = %6', 
			self::$table, Abstract_Network_Folder::$table, self::$table_publication, 'id', 'group', $group_id_);
		$rows = $SQL->FetchAllResults();

		$sharedfolders = array();
		foreach ($rows as $row) {
			$sharedfolder = self::generateFromRow($row);
			if (! is_object($sharedfolder))
				continue;
			
			$mode = $row["mode"];
			if (! array_key_exists($mode, $sharedfolders)) {
				$sharedfolders[$mode] = array();
			}
			
			$sharedfolders[$mode][$sharedfolder->id] = $sharedfolder;
		}
		return $sharedfolders;
	}
	
	public function getList($order_=false) {
		Logger::debug('main', 'SharedFolderDB::internal::getList()');
		$SQL = SQL::getInstance();
		
		$SQL->DoQuery('SELECT * FROM #1 as p, #2 as n WHERE p.@3 = n.@3', self::$table, Abstract_Network_Folder::$table, 'id');
		$rows = $SQL->FetchAllResults();
		
		$sharedfolders = array();
		foreach ($rows as $row) {
			$sharedfolder = self::generateFromRow($row);
			if (! is_object($sharedfolder))
				continue;
			
			$sharedfolders[$sharedfolder->id] = $sharedfolder;
		}
		
		return $sharedfolders;
	}
	
	public function count() {
		Logger::debug('main', 'SharedFolderDB::internal::count()');
		
		$SQL = SQL::getInstance();
		
		$SQL->DoQuery('SELECT 1 FROM #1', self::$table);
		$nb_rows = $SQL->NumRows();
		
		return $nb_rows;
	}
	
	public function countOnServer($id_) {
		Logger::debug('main', "SharedFolderDB::internal::countOnServer($id_)");
		
		$SQL = SQL::getInstance();
		
		$SQL->DoQuery('SELECT * FROM #1 as p, #2 as n WHERE p.@3 = n.@3 AND @4=%5', self::$table, Abstract_Network_Folder::$table, 'id', 'server', $id_);
		$nb_rows = $SQL->NumRows();
		
		return $nb_rows;
	}
	
	protected function generateFromRow($row_) {
		if (array_key_exists('id', $row_) == false || array_key_exists('name', $row_) == false || array_key_exists('server', $row_) == false || array_key_exists('status', $row_) == false) {
			Logger::error('main', 'SharedFolderDB::internal::generateFromRow row not ok, row '.serialize($row_));
			return NULL;
		}
		
		return new SharedFolder($row_['id'], $row_['name'], $row_['server'], (int)$row_['status']);
	}
	
	private function add($sharedfolder_) {
		$SQL = SQL::getInstance();
		
		if (is_null($sharedfolder_->id)) {
			$sharedfolder_->id = 'sf_'.gen_unique_string(); // $SQL->InsertId();
		}
		
		if (is_null($sharedfolder_->name) || $sharedfolder_->name === '') {
			$sharedfolder_->name = $sharedfolder_->id;
		}
		
		$SQL->DoQuery('INSERT INTO #1 (@2,@3) VALUES (%4,%5)', self::$table, 'id', 'name', $sharedfolder_->id, $sharedfolder_->name);
		Abstract_Network_Folder::save($sharedfolder_);
		
		return $sharedfolder_->id;
	}
	
	public function remove($sharedfolder_id_) {
		Logger::debug('main', "SharedFolderDB::internal::remove($sharedfolder_id_)");
		$sharedfolder = $this->import($sharedfolder_id_);
		if (is_object($sharedfolder) == false ) {
			Logger::error('main', "SharedFolderDB::internal::remove($sharedfolder_id_) failed, unable to import sharedfolder");
			return false;
		}
		
		if (self::invalidate($sharedfolder_id_) == false)
			return false;
		
		if (Abstract_Network_Folder::delete($sharedfolder_id_) == false)
			return false;
		
		return true;
	}
	
	public function invalidate($sharedfolder_id_) {
		Logger::debug('main', "SharedFolderDB::internal::invalidate($sharedfolder_id_)");
		
		$SQL = SQL::getInstance();
		$SQL->DoQuery('DELETE FROM #1 WHERE @2 = %3', self::$table_publication, 'id', $sharedfolder_id_);
		$SQL->DoQuery('DELETE FROM #1 WHERE @2 = %3 LIMIT 1', self::$table, 'id', $sharedfolder_id_);
		
		return true;
	}
	
	public function addToServer($sharedfolder_, $an_fs_server_) {
		Logger::debug('main', "SharedFolderDB::internal::addToServer($sharedfolder_, $an_fs_server_)");
		if (is_object($sharedfolder_) == false) {
			Logger::error('main', 'SharedFolderDB::internal sharedfolder parameter is not an object (content: '.serialize($sharedfolder_));
			return false;
		}
		if (is_object($an_fs_server_) == false) {
			Logger::error('main', 'SharedFolderDB::internal fs parameter is not an object (content: '.serialize($an_fs_server_));
			return false;
		}
		
		$sharedfolder_->server = $an_fs_server_->id;
		if ($this->exists($sharedfolder_->id) === false) {
			// we save the object first
			$ret = $this->add($sharedfolder_);
// 			if ($ret !== true) {
// 				Logger::error('main', "SharedFolderDB::internal failed to add $sharedfolder_ to the DB");
// 				return false;
// 			}
		}
		
		// do the request to the server
		$ret = $an_fs_server_->createNetworkFolder($sharedfolder_->id);
		if (! $ret) {
			ErrorManager::report('SharedFolderDB::internal Unable to create shared folder on file server "'.$sharedfolder_->server.'"');
			$this->remove($sharedfolder_);
			return false;
		}
		return true;
	}
	
	public static function update($sharedfolder_) {
		Logger::debug('main', 'SharedFolderDB::internal::update for \''.$sharedfolder_->id.'\'');
		$SQL = SQL::getInstance();
		
		$SQL->DoQuery('UPDATE #1 SET @2=%3 WHERE @4=%5 LIMIT 1', self::$table, 'name', $sharedfolder_->name, 'id', $sharedfolder_->id);
		
		return true;
	}
	
	public function addUserGroupToSharedFolder($usergroup_, $sharedfolder_, $mode_) {
		$SQL = SQL::getInstance();
		
		if (! is_object($usergroup_)) {
			Logger::error('main', "SharedFolderDB::internal::addUserGroupToSharedFolder, parameter 'usergroup' is not correct, usergroup: ".serialize($usergroup_));
			return false;
		}
		if (! is_object($sharedfolder_)) {
			Logger::error('main', "SharedFolderDB::internal::addUserGroupToSharedFolder, parameter 'sharedfolder' is not correct, NetworkFolder: ".serialize($sharedfolder_));
			return false;
		}
		return $SQL->DoQuery('INSERT INTO #1 (@2,@3,@4) VALUES (%5,%6,%7)', self::$table_publication, 'id', 'group', 'mode', $sharedfolder_->id, $usergroup_->getUniqueID(), $mode_);
	}
	
	public function delUserGroupToSharedFolder($usergroup_, $sharedfolder_) {
		$SQL = SQL::getInstance();
		
		if (! is_object($usergroup_)) {
			Logger::error('main', "SharedFolderDB::internal::delUserGroupToSharedFolder, parameter 'usergroup' is not correct, usergroup: ".serialize($usergroup_));
			return false;
		}
		if (! is_object($sharedfolder_)) {
			Logger::error('main', "SharedFolderDB::internal::delUserGroupToSharedFolder, parameter 'sharedfolder' is not correct, networkfolder_: ".serialize($sharedfolder_));
			return false;
		}
		
		return $SQL->DoQuery('DELETE FROM #1 WHERE @2 = %3 AND @4 = %5LIMIT 1', self::$table_publication, 'id', $sharedfolder_->id, 'group', $usergroup_->getUniqueID());
	}
	
	public function get_publications() {
		Logger::debug('main', 'SharedFolderDB::internal::get_publications');
		$SQL = SQL::getInstance();
		
		$SQL->DoQuery('SELECT * FROM #1', self::$table_publication);
		$rows = $SQL->FetchAllResults();
		
		$publications = array();
		foreach ($rows as $row) {
			array_push($publications, array('group' => $row['group'], 'share' => $row['id'], 'mode' => $row['mode']));
		}
		
		return $publications;
	}
	
	public function get_usersgroups($sharedfolder_) {
		Logger::debug('main', 'SharedFolderDB::internal::get_usersgroups('.$sharedfolder_->id.')');
		$SQL = SQL::getInstance();
		
		$SQL->DoQuery('SELECT * FROM #1 WHERE @2= %3', self::$table_publication, 'id', $sharedfolder_->id);
		$rows = $SQL->FetchAllResults();
		
		$groups = array();
		foreach ($rows as $row) {
			$groups[$row['group']] = $row['mode'];
		}
		
		return $groups;
	}
	
	public function clear_publications() {
		Logger::debug('main', "SharedFolderDB::internal::clear_publications");
               
		$SQL = SQL::getInstance();
		$SQL->DoQuery('DELETE FROM #1', self::$table_publication);
		
		return true;
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
