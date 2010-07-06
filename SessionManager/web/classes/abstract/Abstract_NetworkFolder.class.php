<?php
/**
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2010
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

class Abstract_NetworkFolder {
	public static function init($prefs_) {
		Logger::debug('main', 'Abstract_NetworkFolder::init');
		
		$sql_conf = $prefs_->get('general', 'sql');
		$SQL = SQL::newInstance($sql_conf);
		
		$NetworkFolder_table_structure = array(
			'id'			=>	'int(8) NOT NULL auto_increment',
			'path'			=>	'varchar(255) NOT NULL',
			'server'		=>	'varchar(255) NOT NULL',
			'status'		=>	'varchar(255) NOT NULL',
		);
		
		$ret = $SQL->buildTable($sql_conf['prefix'].'NetworkFolder', $NetworkFolder_table_structure, array('id'));
		
		Logger::debug('main', "SQL table '".$sql_conf['prefix']."NetworkFolder' created");
		return true;
	}
	
	public static function exists($id_) {
		Logger::debug('main', "Abstract_NetworkFolder::exists($id_)");
		if (is_null($id_)) {
			return false;
		}
		
		$SQL = SQL::getInstance();
		
		$SQL->DoQuery('SELECT @1 FROM @2 WHERE @1 = %3 LIMIT 1', 'id', $SQL->prefix.'NetworkFolder', $id_);
		$total = $SQL->NumRows();
		
		return ($total == 0);
	}
	
	public static function load($id_) {
		Logger::debug('main', "Abstract_NetworkFolder::load($id_)");
		$SQL = SQL::getInstance();
		
		$SQL->DoQuery('SELECT * FROM @1 WHERE @2 = %3 LIMIT 1', $SQL->prefix.'NetworkFolder', 'id', $id_);
		$total = $SQL->NumRows();
		
		if ($total == 0) {
			Logger::error('main', "Abstract_NetworkFolder::load($id_) failed: NumRows == 0");
			return false;
		}
		
		$row = $SQL->FetchResult();
		$buf = self::generateFromRow($row);
		return $buf;
	}
	
	public static function save($NetworkFolder_) {
		Logger::debug('main', 'Starting Abstract_NetworkFolder::save for \''.$NetworkFolder_->id.'\'');
		$SQL = SQL::getInstance();
		
		$exists = Abstract_NetworkFolder::exists($NetworkFolder_->id);
		if (! $exists) {
			$buf = Abstract_NetworkFolder::create($NetworkFolder_);
			if ($buf === false) {
				Logger::error('main', 'Abstract_NetworkFolder::save failed to create NetworkFolder');
				return false;
			}
		} else {
			Logger::debug('main', 'Abstract_NetworkFolder::save NetworkFolder already exists');
			return self::update($NetworkFolder_);
		}
		
		return true;
	}
	
	private static function create($NetworkFolder_) {
		$SQL = SQL::getInstance();
		
		$SQL->DoQuery('INSERT INTO @1 (@2,@3,@4) VALUES (%5,%6,%7)', $SQL->prefix.'NetworkFolder', 'path', 'server', 'status', $NetworkFolder_->path, $NetworkFolder_->server,  $NetworkFolder_->status);
		
		$NetworkFolder_->id = $SQL->InsertId();
		return $NetworkFolder_->id;
	}
	
	public static function delete($NetworkFolder_) {
		Abstract_Liaison::delete('UserNetworkFolder', NULL, $NetworkFolder_->id);
		$SQL = SQL::getInstance();
		$SQL->DoQuery('DELETE FROM @1 WHERE @2 = %3 LIMIT 1', $SQL->prefix.'NetworkFolder', 'id', $id);
		
		return true;
	}
	
	private static function generateFromRow($row_) {
		if (array_key_exists('id', $row_) == false || array_key_exists('path', $row_) == false || array_key_exists('server', $row_) == false || array_key_exists('status', $row_) == false) {
			Logger::error('main', 'Abstract_NetworkFolder::generateFromRow row not ok, row '.serialize($row_));
			return NULL;
		}
		
		$obj = new NetworkFolder();
		$obj->id = $row_['id'];
		$obj->path = $row_['path'];
		$obj->server = $row_['server'];
		$obj->status = $row_['status'];
		
		return $obj;
	}
	
	public static function load_all() {
		Logger::debug('main', 'Abstract_NetworkFolder::load_all()');
		$SQL = SQL::getInstance();
		
		$SQL->DoQuery('SELECT * FROM @1', $SQL->prefix.'NetworkFolder');
		$rows = $SQL->FetchAllResults();
		
		$networkfolders = array();
		foreach ($rows as $row) {
			$networkfolder = self::generateFromRow($row);
			if (! is_object($networkfolder))
				continue;
			
			$networkfolders[$NetworkFolder->id] = $networkfolder;
		}
		
		return $networkfolders;
	}
	
	public static function load_from_user($login_) {
		$liaisons = Abstract_Liaison::load('UserNetworkFolder', $login_, NULL);
		if (is_array($liaisons) == false) {
			Logger::error('main', "Abstract_NetworkFolder::load_from_user($login_) problem with liaison");
			return false;
		}
		$networkfolders = array();
		foreach ($liaisons as $liaison) {
			$networkfolder = self::load($liaison->group);
			if (! is_object($networkfolder))
				continue;
			
			$networkfolders[$networkfolder->id] = $networkfolder;
		}
		return $networkfolders;
	}
	
	public static function load_from_server($fqdn_) {
		Logger::debug('main', "Abstract_NetworkFolder::load_from_server($fqdn_)");
		
		$SQL = SQL::getInstance();
		
		$SQL->DoQuery('SELECT * FROM @1 WHERE @2=%3', $SQL->prefix.'NetworkFolder', 'server', $fqdn_);
		$rows = $SQL->FetchAllResults();
		
		$networkfolders = array();
		foreach ($rows as $row) {
			$networkfolder = self::generateFromRow($row);
			if (! is_object($networkfolder))
				continue;
				
			$networkfolders[$networkfolder->id] = $networkfolder;
		}
		
		return $networkfolders;
	}
	
	public static function update($NetworkFolder_) {
		Logger::debug('main', 'Abstract_NetworkFolder::update for \''.$NetworkFolder_->id.'\'');
		$SQL = SQL::getInstance();
		
		$SQL->DoQuery('UPDATE @1 SET @2=%3, @4=%5, @6=%7) WHERE @8=%9 LIMIT 1', $SQL->prefix.'NetworkFolder', 'path', $NetworkFolder_->path, 'server', $NetworkFolder_->server, 'status', $NetworkFolder_->status, 'id', $NetworkFolder_->id);
		
		return true;
	}
	
	public static function add_user_to_NetworkFolder($user_, $NetworkFolder_) {
		if (! (is_object($user_) && $user_->hasAttribute('login'))) {
			Logger::error('main', "Abstract_NetworkFolder::add_user_from_NetworkFolder, parameter 'user' is not correct, user: ".serialize($user_));
			return false;
		}
		if (! is_object($NetworkFolder_)) {
			Logger::error('main', "Abstract_NetworkFolder::add_user_from_NetworkFolder, parameter 'NetworkFolder_' is not correct, NetworkFolder_: ".serialize($NetworkFolder_));
			return false;
		}
		return Abstract_Liaison::add('UserNetworkFolder', $user_->getAttribute('login', $NetworkFolder_->id));
	}
	
	public static function delete_user_from_NetworkFolder($user_, $NetworkFolder_) {
		if (! (is_object($user_) && $user_->hasAttribute('login'))) {
			Logger::error('main', "Abstract_NetworkFolder::delete_user_from_NetworkFolder, parameter 'user' is not correct, user: ".serialize($user_));
			return false;
		}
		if (! is_object($NetworkFolder_)) {
			Logger::error('main', "Abstract_NetworkFolder::delete_user_from_NetworkFolder, parameter 'NetworkFolder_' is not correct, NetworkFolder_: ".serialize($NetworkFolder_));
			return false;
		}
		return Abstract_Liaison::delete('UserNetworkFolder', $user_->getAttribute('login', $NetworkFolder_->id));
	}
}
