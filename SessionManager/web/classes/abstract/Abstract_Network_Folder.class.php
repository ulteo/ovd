<?php
/**
 * Copyright (C) 2012 Ulteo SAS
 * http://www.ulteo.com
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

/**
 * Abstraction layer between the Network_Folder instances and the SQL backend.
 */
class Abstract_Network_Folder {
	public static $table = 'network_folders';
	
	public static function init($prefs_) {
		Logger::debug('main', 'Starting Abstract_Network_Folder::init');
		
		$sql_conf = $prefs_->get('general', 'sql');
		$SQL = SQL::newInstance($sql_conf);
		
		$network_folders_table_structure = array(
			'id'				=>	'varchar(255)',
			'server'			=>	'varchar(255)',
			'status'			=>	'int(2)',
		);
		
		$ret = $SQL->buildTable(self::$table, $network_folders_table_structure, array('id'));
		
		if (! $ret) {
			Logger::error('main', 'Unable to create MySQL table \''.self::$table.'\'');
			return false;
		}
		
		Logger::debug('main', 'MySQL table \''.self::$table.'\' created');
		return true;
	}
	
	public static function exists($id_) {
		Logger::debug('main', 'Starting Abstract_Network_Folder::exists for \''.$id_.'\'');
		
		$SQL = SQL::getInstance();
		
		$SQL->DoQuery('SELECT 1 FROM #1 WHERE @2 = %3 LIMIT 1', self::$table, 'id', $id_);
		
		return ($SQL->NumRows() == 1);
	}
	
	public static function load($id_) {
		Logger::debug('main', 'Starting Abstract_Network_Folder::load for \''.$id_.'\'');
		
		$SQL = SQL::getInstance();
		
		$SQL->DoQuery('SELECT * FROM #1 WHERE @2 = %3 LIMIT 1', self::$table, 'id', $id_);
		
		if ($SQL->NumRows() == 0) {
			Logger::error('main', "Abstract_Network_Folder::load($id_) failed: NumRows == 0");
			return false;
		}
		
		$row = $SQL->FetchResult();
		
		$buf = self::generateFromRow($row);
		
		return $buf;
	}
	
	public static function save($Network_Folder_) {
		Logger::debug('main', 'Starting Abstract_Network_Folder::save for \''.$Network_Folder_->id.'\'');
		
		$SQL = SQL::getInstance();

		$id = $Network_Folder_->id;

		if (! Abstract_Network_Folder::exists($id)) {
			Logger::debug('main', "Abstract_Network_Folder::save($Network_Folder_) Network_Folder does NOT exist, we must create it");
			
			if (! Abstract_Network_Folder::create($Network_Folder_)) {
				Logger::error('main', "Abstract_Network_Folder::save($Network_Folder_) failed to create Network_Folder");
				return false;
			}
		}
		
		return $SQL->DoQuery('UPDATE #1 SET @2=%3,@4=%5 WHERE @6 = %7 LIMIT 1', self::$table, 'server', $Network_Folder_->server, 'status', $Network_Folder_->status, 'id', $id);
	}
	
	private static function create($Network_Folder_) {
		Logger::debug('main', 'Starting Abstract_Network_Folder::create for \''.$Network_Folder_->id.'\'');

		if (Abstract_Network_Folder::exists($Network_Folder_->id)) {
			Logger::error('main', 'Abstract_Network_Folder::create(\''.$Network_Folder_->id.'\') Network_Folder already exists');
			return false;
		}
		
		$SQL = SQL::getInstance();
		$SQL->DoQuery('INSERT INTO #1 (@2) VALUES (%3)', self::$table, 'id', $Network_Folder_->id);
		
		return true;
	}
	
	public static function delete($id_) {
		Logger::debug('main', 'Starting Abstract_Network_Folder::delete for \''.$id_.'\'');
		
		$SQL = SQL::getInstance();
		
		$SQL->DoQuery('DELETE FROM #1 WHERE @2 = %3 LIMIT 1', self::$table, 'id', $id_);
		
		$res = $SQL->AffectedRows();
		if ($res < 1) {
			Logger::error('main', "Abstract_Network_Folder::delete($id_) Network_Folder does not exist (NumRows == 0)");
			return false;
		}
		
		return true;
	}
	
	private static function generateFromRow($row_) {
		if (array_key_exists('id', $row_) == false || array_key_exists('server', $row_) == false || array_key_exists('status', $row_) == false) {
			Logger::error('main', 'Abstract_Network_Folder::generateFromRow row not ok, row '.serialize($row_));
			return NULL;
		}
		
		return new NetworkFolder($row_['id'], $row_['server'], (int)$row_['status']);
	}
	
	public static function load_all() {
		Logger::debug('main', 'Starting Abstract_Network_Folder::load_all');
		
		$SQL = SQL::getInstance();
		
		$SQL->DoQuery('SELECT * FROM #1', self::$table);
		$rows = $SQL->FetchAllResults();
		
		$network_folders = array();
		foreach ($rows as $row) {
			$Network_Folder = self::generateFromRow($row);
			if (! is_object($Network_Folder))
				continue;
			
			$network_folders[] = $Network_Folder;
		}
		
		return $network_folders;
	}
	
	public static function load_partial($offset_=NULL, $start_=NULL) {
		Logger::debug('main', 'Starting Abstract_Network_Folder::load_partial('.$offset_.', '.$start_.')');
		
		$SQL = SQL::getInstance();
		
		$query = 'SELECT * FROM #1';
		if (! is_null($offset_))
			$query .= ' LIMIT'.((! is_null($start_))?$start_.',':'').$offset_;
		
		$SQL->DoQuery($query, self::$table);
		$rows = $SQL->FetchAllResults();
		
		$network_folders = array();
		foreach ($rows as $row) {
			$Network_Folder = self::generateFromRow($row);
			if (! is_object($Network_Folder))
				continue;
			
			$network_folders[] = $Network_Folder;
		}
		
		return $network_folders;
	}
	
	public static function load_orphans() {
		Logger::debug('main', 'Starting Abstract_Network_Folder::load_orphans()');
		
		$sharedfolderdb = null;
		if (Preferences::moduleIsEnabled('SharedFolderDB')) {
			$sharedfolderdb = SharedFolderDB::getInstance();
		}
		
		$profiles = null;
		if (Preferences::moduleIsEnabled('ProfileDB')) {
			$profiledb = ProfileDB::getInstance();
		}
		
		$SQL = SQL::getInstance();
		
		if (is_null($sharedfolderdb) && is_null($profiledb)) {
			$SQL->DoQuery('SELECT * FROM #1;', self::$table);
		}
		else if (is_null($sharedfolderdb)) {
			$SQL->DoQuery('SELECT * FROM #1 WHERE @2 NOT IN ( SELECT @2 FROM #3 );', self::$table, 'id', ProfileDB_internal::$table);
		}
		else if (is_null($profiledb)) {
			$SQL->DoQuery('SELECT * FROM #1 WHERE @2 NOT IN ( SELECT @2 FROM #3 );', self::$table, 'id', SharedFolderDB_internal::$table);
		}
		else {
			$SQL->DoQuery('SELECT * FROM #1 WHERE @2 NOT IN ( SELECT @2 FROM #3 ) AND @2 NOT IN ( SELECT @2 FROM #4 )', self::$table, 'id', ProfileDB_internal::$table, SharedFolderDB_internal::$table);
		}
		
		$rows = $SQL->FetchAllResults();
		
		$network_folders = array();
		foreach ($rows as $row) {
			$Network_Folder = self::generateFromRow($row);
			if (! is_object($Network_Folder))
				continue;
			
			$network_folders[] = $Network_Folder;
		}
		
		return $network_folders;
	}
	
	public static function countByStatus($status_=NULL) {
		Logger::debug('main', "Starting Abstract_Network_Folder::countByStatus($status_)");
		
		$SQL = SQL::getInstance();
		
		$query = 'SELECT 1 FROM #1';
		if (! is_null($status_))
			$query .= ' WHERE @2 = %3';
		
		$SQL->DoQuery($query, self::$table, 'status', $status_);
		
		return $SQL->NumRows();
	}

	public static function countByServer($server_id_) {
		$SQL = SQL::getInstance();
		
		$SQL->DoQuery('SELECT 1 FROM #1 WHERE @2 = %3', self::$table, 'server', $server_id_);
		
		return $SQL->NumRows();
	}
	
	public static function getByServer($server_id_, $offset_=NULL, $start_=NULL) {
		$SQL = SQL::getInstance();
		
		$query = 'SELECT * FROM #1 WHERE @2 LIKE %3';
		if (! is_null($offset_))
			$query .= 'LIMIT '.((! is_null($start_))?$start_.',':'').$offset_;
		
		$SQL->DoQuery($query, self::$table, 'servers', '%'.$server_id_.'%');
		$rows = $SQL->FetchAllResults();
		
		$network_folders = array();
		foreach ($rows as $row) {
			$Network_Folder = self::generateFromRow($row);
			if (! is_object($Network_Folder))
				continue;
			
			$network_folders[] = $Network_Folder;
		}
		
		return $network_folders;
	}
	
	public static function getByStatus($status_) {
		$SQL = SQL::getInstance();
		
		$SQL->DoQuery('SELECT * FROM #1 WHERE @2 = %3', self::$table, 'status', $status_);
		$rows = $SQL->FetchAllResults();
		
		$network_folders = array();
		foreach ($rows as $row) {
			$Network_Folder = self::generateFromRow($row);
			if (! is_object($Network_Folder))
				continue;
			
			$network_folders[] = $Network_Folder;
		}
		
		return $network_folders;
	}
}
