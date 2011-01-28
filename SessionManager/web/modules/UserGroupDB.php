<?php
/**
 * Copyright (C) 2009-2011 Ulteo SAS
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

class UserGroupDB extends Module {
	protected $instance_type; // static, dynamic
	protected static $instance=NULL;
	public function __construct() {
		$prefs = Preferences::getInstance();
		if (! $prefs)
			die_error('get Preferences failed',__FILE__,__LINE__);
		
		$mods_enable = $prefs->get('general','module_enable');
		if (! in_array('UserGroupDB',$mods_enable))
			die_error(_('UserGroupDB module must be enabled'),__FILE__,__LINE__);
		
		$this->instance_type = array();
		$mod_usergroup_name = 'UserGroupDB_'.$prefs->get('UserGroupDB','enable');
		$a_userGroupDB = new $mod_usergroup_name();
		$this->instance_type['static'] = $a_userGroupDB;
		
		if (Preferences::moduleIsEnabled('UserGroupDBDynamic')) {
			$this->instance_type['dynamic'] = UserGroupDBDynamic::getInstance();
		}
		if (Preferences::moduleIsEnabled('UserGroupDBDynamicCached')) {
			$this->instance_type['dynamiccached'] = UserGroupDBDynamicCached::getInstance();
		}
	}
	public static function getInstance() {
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	public function __toString() {
		$ret = get_class($this).'(';
		foreach ($this->instance_type as $key => $value) {
			$ret .= '\''.$key.'\':\''.$value->prettyName().'\' ';
		}
		$ret .= ')';
		return $ret;
	}
	public function import($id_) {
		if (is_base64url($id_)) {
			$id_ = base64url_decode($id_);
		}
		Logger::debug('main', 'UserGroupDB::import('.$id_.')');
		foreach ($this->instance_type as $key => $value) {
			if (str_startswith($id_, $key.'_'))
				return $value->import(substr($id_, strlen($key)+1));
		}
		return NULL; // not found
	}
	public function getList($sort_=false) {
		Logger::debug('main', 'UserGroupDB::getList');
		$result = array();
		foreach ($this->instance_type as $key => $value) {
			$buffer = $value->getList();
			if (is_array($buffer) === false) {
				Logger::debug('main', 'UserGroupDB::getList instance '.$this->instance_type.' did not return an array (returned: '.serialize($buffer).')');
				continue;
			}
			
			$result = array_merge($result, $buffer);
		}
		$unique = array_unique($result);
		
		if ($sort_) {
			usort($unique, "usergroup_cmp");
		}
		return $unique;
	}
	public function isWriteable() {
		if (!array_key_exists('static', $this->instance_type))
			return false;
		$buf = $this->instance_type['static'];
		return $buf->isWriteable();
	}
	public function canShowList() {}
	
	public function isDynamic() {
		if (array_key_exists('dynamic', $this->instance_type)) {
			return true;
		}
		
		if (array_key_exists('dynamiccached', $this->instance_type)) {
			return true;
		}
		
		return false;
	}
	
	// admin function
	public static function init($prefs_) {}
	public function add($usergroup_) {
		return $this->call_method('add', $usergroup_);
	}
	public function remove($usergroup_) {
		return $this->call_method('remove', $usergroup_);
	}
	public function update($usergroup_) {
		return $this->call_method('update', $usergroup_);
	}
	protected function call_method($method_name_, $usergroup_) {
		Logger::debug('main', 'UserGroupDB::call_method method '.$method_name_);
		if (!array_key_exists($usergroup_->type, $this->instance_type)) {
			Logger::error('main', 'UserGroupDB::call_method method \''.$method_name_.'\' type \''.$usergroup_->type.'\' not implemented');
			// do a die_error ?
			return NULL;
		}
		$method_to_call = array($this->instance_type[$usergroup_->type]);
		if (!method_exists($method_to_call[0], $method_name_)) {
			Logger::error('main', 'UserGroupDB::call_method \''.$usergroup_->type.'\',\''.$method_name_.'\'  does not exist');
			return NULL;
		}
		$method_to_call []= $method_name_;
		Logger::debug('main', 'UserGroupDB::call_method \''.$usergroup_->type.'\',\''.$method_name_.'\'');
		return call_user_func($method_to_call, $usergroup_); // [instance, method], parameter
	}
	
	public static function enable() {
		return self::call_static_method('enable');
	}
	public static function configuration() {
		return self::call_static_method('configuration');
	}
	public static function prefsIsValid($prefs_, &$log=array()) {
		return self::call_static_method('prefsIsValid', $prefs_ , $log);
	}
	public static function prettyName() {
		return self::call_static_method('prettyName');
	}
	public static function isDefault() {
		return self::call_static_method('isDefault');
	}
	public static function liaisonType() {
		return self::call_static_method('liaisonType');
	}
	
	protected static function call_static_method($method_name_, $prefs_=NULL, &$log=array()) {
		if (is_null($prefs_))
			$prefs = Preferences::getInstance();
		else
			$prefs = $prefs_;
		if (! $prefs)
			die_error('get Preferences failed',__FILE__,__LINE__);
		
		$mods_enable = $prefs->get('general','module_enable');
		if (! in_array('UserGroupDB',$mods_enable))
			die_error(_('UserGroupDB module must be enabled'),__FILE__,__LINE__);
		
		$mod_usergroup_name = 'UserGroupDB_'.$prefs->get('UserGroupDB','enable');
		$a_userGroupDB = new $mod_usergroup_name();
		return $a_userGroupDB->$method_name_($prefs, $log);
	}
	
	public function getGroupsContains($contains_, $attributes_=array('name', 'description'), $limit_=0) {
		$groups = array();
		
		$groups1 = array();
		$sizelimit_exceeded = false;
		$limit_to_get = $limit_;
		foreach ($this->instance_type as $key => $value) {
			list($groups1, $sizelimit_exceeded1) = $value->getGroupsContains($contains_, $attributes_, $limit_to_get);
			if (is_array($groups1) === false) {
				Logger::debug('main', 'UserGroupDB::getGroupsContains instance '.$this->instance_type.' did not return an array (returned: '.serialize($groups1).')');
				continue;
			}
			$groups = array_merge($groups, $groups1);
			
			$sizelimit_exceeded = $sizelimit_exceeded or $sizelimit_exceeded1;
			if ( $sizelimit_exceeded == true)
				break;
			
			$limit_to_get = $limit_to_get - count($groups1);
			if ( $limit_to_get == 0) {
				break;
			}
			else if ( $limit_to_get < 0) {
				$limit_to_get = $limit_to_get + count($groups1); // revert
			}
		}
		return array(array_unique($groups), $sizelimit_exceeded);
	}
}
