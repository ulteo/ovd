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
class UserGroupDB_ldap_posix extends UserGroupDB_ldap_memberof{

	public function import($id_) {
		Logger::debug('main',"UserGroupDB::ldap_posix::import (id = $id_)");
		
		$prefs = Preferences::getInstance();
		if (! $prefs)
			die_error('get Preferences failed',__FILE__,__LINE__);
		
		$mods_enable = $prefs->get('general','module_enable');
		if (! in_array('UserGroupDB',$mods_enable))
			die_error(_('UserGroupDB module must be enabled'),__FILE__,__LINE__);
		
		$configLDAP = $prefs->get('UserDB','ldap');
		$conf = $prefs->get('UserGroupDB', $prefs->get('UserGroupDB','enable'));
		if (! is_array($conf)) {
			Logger::error('main', "UserGroupDB_ldap_posix::import UserGroupDB::$mod_usergroup_name have not configuration");
			die_error("UserGroupDB::$mod_usergroup_name have not configuration",__FILE__,__LINE__);
		}
		
		if (isset($conf['group_dn'])) {
			$configLDAP['userbranch'] = $conf['group_dn'];
		}
		else {
			Logger::error('main', "UserGroupDB_ldap_posix::import  UserGroupDB::$mod_usergroup_name have not correct configuration");
			die_error("UserGroupDB::$mod_usergroup_name have not correct configuration",__FILE__,__LINE__);
		}
		
		$ldap = new LDAP($configLDAP);
		$sr = $ldap->search('cn='.$id_, NULL);
		$infos = $ldap->get_entries($sr);
		$keys = array_keys($infos);
		if (!is_array($infos) || $infos === array())
			return NULL;
		$dn = $keys[0];
		$info = $infos[$dn];
		return $this->generateUsersGroupFromRow($info, $dn);
	}
	
	public function getList($sort_=false) {
		Logger::debug('main','UserGroupDB::ldap_posix::getList');
		
		$prefs = Preferences::getInstance();
		if (! $prefs)
			die_error('get Preferences failed',__FILE__,__LINE__);
		
		$mods_enable = $prefs->get('general','module_enable');
		if (! in_array('UserGroupDB',$mods_enable))
			die_error(_('UserGroupDB module must be enabled'),__FILE__,__LINE__);
		
		$configLDAP = $prefs->get('UserDB','ldap');
		
		$conf = $prefs->get('UserGroupDB', $prefs->get('UserGroupDB','enable'));
		if (! is_array($conf)) {
			Logger::error('main', "UserGroupDB_ldap_posix::getList  UserGroupDB::$mod_usergroup_name have not configuration");
			die_error("UserGroupDB_ldap_posix::getList UserGroupDB::$mod_usergroup_name have not configuration",__FILE__,__LINE__);
		}
		
		if (isset($conf['group_dn'])) {
			$configLDAP['userbranch'] = $conf['group_dn'];
		}
		else {
			Logger::error('main', "UserGroupDB_ldap_posix::getList  UserGroupDB::$mod_usergroup_name have not correct configuration");
			die_error("UserGroupDB_ldap_posix::getList UserGroupDB::$mod_usergroup_name have not correct configuration",__FILE__,__LINE__);
		}
		
		$ldap = new LDAP($configLDAP);
		$sr = $ldap->search('cn=*', NULL);
		$infos = $ldap->get_entries($sr);
		$groups = array();
		if (! is_array($infos))
			return $groups;
		foreach ($infos as $dn => $info) {
			$g = $this->generateUsersGroupFromRow($info, $dn);
			if (is_object($g))
				$groups[$dn] = $g;
		}
		if ($sort_) {
			usort($groups, "usergroup_cmp");
		}
		
		return $groups;
	}
	
	protected function generateUsersGroupFromRow($row_, $dn_) {
		if (! isset($row_['cn'])) {
			return NULL;
		}
		$cn = NULL;
		$description = NULL;
		if ( is_string($row_['cn'])) {
			$cn = $row_['cn'];
		}
		elseif (is_array($row_['cn'])) {
			if (isset($row_['cn'][0])) {
				$cn = $row_['cn'][0];
			}
		}
		
		if ( isset($row_['description'])) {
			if ( is_string($row_['description'])) {
				$description = $row_['description'];
			}
			else if (is_array($row_['description'])) {
				if (isset($row_['description'][0])) {
					$description = $row_['description'][0];
				}
			}
		}
		return new UsersGroup($cn, $cn, $description, true);
	}
	
	public static function configuration() {
		$c = new ConfigElement_input('group_dn', _('Group Branch DN'), _('Use LDAP User Groups using Posix group, Group Branch DN:'), _('Use LDAP User Groups using Posix group, Group Branch DN:'),'');
		return array($c);
	}
	
	public static function prettyName() {
		return _('LDAP using posix group');
	}
	
	public static function isDefault() {
		return false;
	}
	
	public static function liaisonType() {
		return 'ldap_posix';
	}
	
	public function add($usergroup_){
		return false;
	}
	
	public function remove($usergroup_){
		if ($usergroup_->isDefault()) {
			// unset the default usergroup
			$prefs = new Preferences_admin();
			$mods_enable = $prefs->set('general', 'user_default_group', '');
			$prefs->backup();
		}
		return true;
	}
	
	public function update($usergroup_){
		return true;
	}
	
	public static function init($prefs_) {
		return true;
	}
	
	public static function enable() {
		return true;
	}
}
 
