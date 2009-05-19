<?php
/**
 * Copyright (C) 2009 Ulteo SAS
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
			die_error(_('Module UserGroupDB must be enabled'),__FILE__,__LINE__);
		
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
		if (!is_array($infos))
			return NULL;
		if (isset($infos[0])) {
			$info = $infos[0];
			if ( isset($info['dn']) && $info['cn']) {
				if (is_string($info['dn']) && isset($info['cn']) && is_array($info['cn']) && isset($info['cn'][0]) ) {
					// return  = new UsersGroup($info['dn'], $info['cn'][0], '', true);
					if (isset($info['description'][0]))
						$description = $info['description'][0];
					else
						$description = '';
					return  new UsersGroup($info['cn'][0], $info['cn'][0], $description, true);
				}
			}
		}
		return NULL;
	}
	
	public function getList() {
		Logger::debug('main','UserGroupDB::ldap_posix::getList');
		
		$prefs = Preferences::getInstance();
		if (! $prefs)
			die_error('get Preferences failed',__FILE__,__LINE__);
		
		$mods_enable = $prefs->get('general','module_enable');
		if (! in_array('UserGroupDB',$mods_enable))
			die_error(_('Module UserGroupDB must be enabled'),__FILE__,__LINE__);
		
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
		foreach ($infos as $info){
			if ( isset($info['dn']) && $info['cn']) {
				if (is_string($info['dn']) && isset($info['cn']) && is_array($info['cn']) && isset($info['cn'][0]) ) {
					if (isset($info['description'][0]))
						$description = $info['description'][0];
					else
						$description = '';
					
					$groups[$info['dn']] = new UsersGroup($info['cn'][0], $info['cn'][0], $description, true);
				}
			}
		}
		return array_unique(array_merge($groups, parent::getListDynamic()));
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
}
 
