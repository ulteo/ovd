<?php
/**
 * Copyright (C) 2009-2010 Ulteo SAS
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

class SharedFolderDB_internal  extends SharedFolderDB {
	public static $table="shared_folder";
	
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
		Logger::debug('main', 'SharedFolderDB::internal::init');
		
		$sql_conf = $prefs_->get('general', 'sql');
		$SQL = SQL::newInstance($sql_conf);
		
		$SharedFolder_table_structure = array(
			'id'				=>	'varchar(255)', //'id'			=>	'int(8) NOT NULL auto_increment',
			'name'			=>	'varchar(255)',
			'server'		=>	'varchar(255)',
			'status'		=>	'varchar(255)',
		);
		
		$ret = $SQL->buildTable($sql_conf['prefix'].SharedFolderDB_internal::$table, $SharedFolder_table_structure, array('id'));
		
		Logger::debug('main', "SharedFolderDB::internal::init SQL table '".$sql_conf['prefix'].SharedFolderDB_internal::$table."' created");
		return true;
	}
	
	public function exists($id_) {
		if (is_null($id_)) {
			return false;
		}
		
		$SQL = SQL::getInstance();
		
		$SQL->DoQuery('SELECT @1 FROM @2 WHERE @1 = %3 LIMIT 1', 'id', $SQL->prefix.self::$table, $id_);
		$total = $SQL->NumRows();
		
		return ($total == 1);
	}
	
	public function import($id_) {
		Logger::debug('main', "SharedFolderDB_internal::import($id_)");
		$SQL = SQL::getInstance();
		
		$SQL->DoQuery('SELECT * FROM @1 WHERE @2 = %3 LIMIT 1', $SQL->prefix.self::$table, 'id', $id_);
		$total = $SQL->NumRows();
		
		if ($total == 0) {
			Logger::error('main', "SharedFolderDB::internal::import($id_) failed: NumRows == 0");
			return false;
		}
		
		$row = $SQL->FetchResult();
		$buf = self::generateFromRow($row);
		return $buf;
	}
	
	public function importFromServer($server_fdqn_) {
		Logger::debug('main', "SharedFolderDB::internal::importFromServer($server_fdqn_)");
		$SQL = SQL::getInstance();
		
		$SQL->DoQuery('SELECT * FROM @1 WHERE @2 = %3', $SQL->prefix.self::$table, 'server', $server_fdqn_);
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
		
		$SQL->DoQuery('SELECT * FROM @1 WHERE @2=%3', $SQL->prefix.self::$table, 'name', $a_name_);
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
		$liaisons = Abstract_Liaison::load('UserGroupSharedFolder', $group_id_, NULL);
		if (is_array($liaisons) == false) {
			Logger::error('main', "SharedFolderDB::internal::importFromUsergroup($group_id_) problem with liaison");
			return false;
		}
		$sharedfolders = array();
		foreach ($liaisons as $liaison) {
			$sharedfolder = $this->import($liaison->group);
			if (! is_object($sharedfolder))
				continue;
			
			$sharedfolders[$sharedfolder->id] = $sharedfolder;
		}
		return $sharedfolders;
	}
	
	public function getList($order_=false) {
		Logger::debug('main', 'SharedFolderDB::internal::getList()');
		$SQL = SQL::getInstance();
		
		$SQL->DoQuery('SELECT * FROM @1', $SQL->prefix.self::$table);
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
		
		$SQL->DoQuery('SELECT 1 FROM @1', $SQL->prefix.self::$table);
		$nb_rows = $SQL->NumRows();
		
		return $nb_rows;
	}
	
	public function countOnServer($fqdn_) {
		Logger::debug('main', "SharedFolderDB::internal::countOnServer($fqdn_)");
		
		$SQL = SQL::getInstance();
		
		$SQL->DoQuery('SELECT 1 FROM @1 WHERE @2=%3', $SQL->prefix.self::$table, 'server', $fqdn_);
		$nb_rows = $SQL->NumRows();
		
		return $nb_rows;
	}
	
	protected function generateFromRow($row_) {
		if (array_key_exists('id', $row_) == false || array_key_exists('name', $row_) == false || array_key_exists('server', $row_) == false || array_key_exists('status', $row_) == false) {
			Logger::error('main', 'SharedFolderDB::internal::generateFromRow row not ok, row '.serialize($row_));
			return NULL;
		}
		
		$obj = new SharedFolder();
		$obj->id = $row_['id'];
		$obj->name = $row_['name'];
		$obj->server = $row_['server'];
		$obj->status = $row_['status'];
		if ($obj->name === '') {
			$obj->name = $obj->id;
		}
		
		return $obj;
	}
	
	private function add($sharedfolder_) {
		$SQL = SQL::getInstance();
		
		if (is_null($sharedfolder_->id)) {
			$sharedfolder_->id = 'sf_'.gen_unique_string(); // $SQL->InsertId();
			$SQL->DoQuery('INSERT INTO @1 (@2,@3,@4,@5) VALUES (%6,%7,%8,%9)', $SQL->prefix.self::$table, 'id', 'name', 'server', 'status', $sharedfolder_->id, $sharedfolder_->name, $sharedfolder_->server,  $sharedfolder_->status);
			
			if (is_null($sharedfolder_->name) || $sharedfolder_->name === '') {
				$sharedfolder_->name = $sharedfolder_->id;
			}
		}
		else {
			$SQL->DoQuery('INSERT INTO @1 (@2,@3,@4) VALUES (%5,%6,%7)', $SQL->prefix.self::$table, 'id', 'name', 'server', 'status', $sharedfolder_->id, $sharedfolder_->name, $sharedfolder_->server,  $sharedfolder_->status);
		}
		
		if (is_null($sharedfolder_->name) || $sharedfolder_->name === '') {
			$sharedfolder_->name = $sharedfolder_->id;
		}
		
		return $sharedfolder_->id;
	}
	
	public function remove($sharedfolder_id_) {
		Logger::debug('main', "SharedFolderDB::internal::remove($sharedfolder_id_)");
		$sharedfolder = $this->import($sharedfolder_id_);
		if (is_object($sharedfolder) == false ) {
			Logger::error('main', "SharedFolderDB::internal::remove($sharedfolder_id_) failed, unable to import sharedfolder");
			return false;
		}
		Abstract_Liaison::delete('UserGroupSharedFolder', NULL, $sharedfolder->id);
		$SQL = SQL::getInstance();
		$SQL->DoQuery('DELETE FROM @1 WHERE @2 = %3 LIMIT 1', $SQL->prefix.self::$table, 'id', $sharedfolder->id);

		$server = Abstract_Server::load($sharedfolder->server);
		$server->deleteNetworkFolder($sharedfolder->id, true);
		
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
		
		$sharedfolder_->server = $an_fs_server_->fqdn;
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
			popup_error(sprintf(_("SharedFolderDB::internal Unable to create shared folder on file server '%s'"), $sharedfolder_->server));
			$this->remove($sharedfolder_);
			return false;
		}
		return true;
	}
	
	public static function update($sharedfolder_) {
		Logger::debug('main', 'SharedFolderDB::internal::update for \''.$sharedfolder_->id.'\'');
		$SQL = SQL::getInstance();
		
		$SQL->DoQuery('UPDATE @1 SET @2=%3, @4=%5, @6=%7 WHERE @8=%9 LIMIT 1', $SQL->prefix.self::$table, 'name', $sharedfolder_->name, 'server', $sharedfolder_->server, 'status', $sharedfolder_->status, 'id', $sharedfolder_->id);
		
		return true;
	}
	
	public function addUserGroupToSharedFolder($usergroup_, $sharedfolder_) {
		if (! is_object($usergroup_)) {
			Logger::error('main', "SharedFolderDB::internal::addUserGroupToSharedFolder, parameter 'usergroup' is not correct, usergroup: ".serialize($usergroup_));
			return false;
		}
		if (! is_object($sharedfolder_)) {
			Logger::error('main', "SharedFolderDB::internal::addUserGroupToSharedFolder, parameter 'sharedfolder' is not correct, NetworkFolder: ".serialize($sharedfolder_));
			return false;
		}
		return Abstract_Liaison::save('UserGroupSharedFolder', $usergroup_->getUniqueID(), $sharedfolder_->id);
	}
	
	public function delUserGroupToSharedFolder($usergroup_, $sharedfolder_) {
		if (! is_object($usergroup_)) {
			Logger::error('main', "SharedFolderDB::internal::delUserGroupToSharedFolder, parameter 'usergroup' is not correct, usergroup: ".serialize($usergroup_));
			return false;
		}
		if (! is_object($sharedfolder_)) {
			Logger::error('main', "SharedFolderDB::internal::delUserGroupToSharedFolder, parameter 'sharedfolder' is not correct, networkfolder_: ".serialize($sharedfolder_));
			return false;
		}
		return Abstract_Liaison::delete('UserGroupSharedFolder', $usergroup_->getUniqueID(), $sharedfolder_->id);
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
