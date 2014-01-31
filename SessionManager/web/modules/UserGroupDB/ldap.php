<?php
/**
 * Copyright (C) 2009-2013 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2009-2011
 * Author Julien LANGLOIS <julien@ulteo.com> 2013
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
class UserGroupDB_ldap {
	protected $cache_import;
	protected $preferences;
	
	public function __construct() {
		$this->cache_import = array();
		
		$prefs = Preferences::getInstance();
		if (! $prefs)
			die_error('get Preferences failed',__FILE__,__LINE__);
		
		$a_pref = $prefs->get('UserGroupDB', 'ldap');
		if (is_array($a_pref)) {
			$this->preferences = $a_pref;
		}
		else { // ugly...
			$this->preferences = array();
		}
	}
	
	public function __toString() {
		$ret = get_class($this).'()';
		return $ret;
	}
	
	public function canShowList() {
		return true;
	}
	
	public function isDynamic() {
		return false;
	}
	
	public function makeLDAPconfig($config_=NULL) {
		if (is_null($config_) === false) {
			return $config_;
		}
		else {
			$userDBAD = UserDB::getInstance();
			if (method_exists($userDBAD, 'makeLDAPconfig') === false) {
				Logger::error('main', 'UserGroupDB::ldap::makeLDAPconfig makeLDAPconfig is not avalaible');
				return NULL;
			}
			
			$configLDAP = $userDBAD->makeLDAPconfig();
			if (array_keys_exists_not_empty(array('ou'), $this->preferences)) {
				$configLDAP['suffix'] = $this->preferences['ou'].','.$configLDAP['suffix'];
			}
			
			return $configLDAP;
		}
	}
	
	public function get_prefs() {
		return $this->preferences;
	}
	
	public function getGroupsContains($contains_, $attributes_=array('name', 'description'), $limit_=0, $user_=null) {
		$groups = array();
		
		$filters = array($this->preferences['filter']);
		if ( $contains_ != '') {
			$contains = preg_replace('/\*\*+/', '*', '*'.$contains_.'*'); // ldap does not handle multiple star characters
			$filter_contain_rules = array();
			$missing_attribute_nb = 0;
			foreach ($attributes_ as $attribute) {
				if (! array_key_exists($attribute, $this->preferences['match']) || strlen($this->preferences['match'][$attribute])==0) {
					$missing_attribute_nb++;
					continue;
				}
				
				array_push($filter_contain_rules, $this->preferences['match'][$attribute].'='.$contains);
			}
			
			if ($missing_attribute_nb == count($attributes_)) {
				return array(array(), false);
			}
			
			array_push($filters, LDAP::join_filters($filter_contain_rules, '|'));
		}
		
		$sizelimit_exceeded_user = false;
		if (! is_null($user_)) {
			if (in_array('group_field', $this->preferences['group_match_user'])) {
				if ($this->preferences['group_field_type'] == 'user_dn') {
					$value = $user_->getAttribute('dn');
				}
				else {
					$value = $user_->getAttribute('login');
				}
				
				$filter_user = $this->preferences['group_field'].'='.$value;
			}
			else {
				$field = $this->preferences['user_field'];
				
				$userDB = UserDB::getInstance();
				$configLDAP = $userDB->config;
				$ldap = new LDAP($configLDAP);
				$sr = $ldap->searchDN($user_->getAttribute('dn'), array($field));
				if ($sr === false) {
					Logger::error('main','UserGroupDB::ldapimport_by_user ldap failed (mostly timeout on server)');
					return array();
				}
				
				$infos = $ldap->get_entries($sr);
				if (!is_array($infos) || $infos === array()) {
					return array();
				}
				
				$keys = array_keys($infos);
				$dn = $keys[0];
				$info = $infos[$dn];
				if (is_array($info[$field])) {
					if (isset($info[$field]['count'])) {
						unset($info[$field]['count']);
					}
					
					$memberof = $info[$field];
				}
				else {
					$memberof = array($info[$field]);
				}
				
				while(count($memberof) > $limit_) {
					$sizelimit_exceeded_user = true;
					array_pop($memberof);
				}
				
				$filter_user_rules = array();
				if ($this->preferences['user_field_type'] == 'group_dn') {
					foreach($memberof as $dn) {
						list($rdn, $sub) = explode_with_escape(',', $dn, 2);
						array_push($filter_user_rules, '('.$rdn.')');
					}
				}
				else {
					$filters = array();
					foreach($memberof as $name) {
						array_push($filter_user_rules, '('.$this->preferences['match']['name'].'='.$name.')');
					}
				}
				
				$filter_user = LDAP::join_filters($filter_user_rules, '|');
			}
			
			array_push($filters, $filter_user);
		}
		
		$filter = LDAP::join_filters($filters, '&');
		
		$ldap = new LDAP($this->makeLDAPconfig());
		$sr = $ldap->search($filter, array_values($this->preferences['match']), $limit_);
		if ($sr === false) {
			Logger::error('main', 'UsersGroupDB::ldap::getUsersContaint search failed');
			return array(array(), false);
		}
		$sizelimit_exceeded = $ldap->errno() === 4; // LDAP_SIZELIMIT_EXCEEDED => 0x04 
		
		$infos = $ldap->get_entries($sr);
		
		foreach ($infos as $dn => $info) {
			if (! is_null($user_) && isset($memberof)) {
				if (! in_array($dn, $memberof)) {
					continue;
				}
			}
			
			$ug = $this->generateUsersGroupFromRow($info, $dn, $this->preferences['match']);
			$groups[$dn] = $ug;
		}
		return array($groups, ($sizelimit_exceeded_user or $sizelimit_exceeded));
	}
	
	public function get_filter_groups_member($group_) {
		Logger::debug('main', 'UsersGroupDB::get_filter_groups_member ('.$group_->getUniqueID().')');
		
		$result = array();
		if (in_array('user_field', $this->preferences['group_match_user'])) {
			if ($this->preferences['user_field_type'] == 'group_dn') {
				$value = $group_->id;
			}
			else {
				$value = $group_->name;
			}
			
			$result['filter'] = $this->preferences['user_field'].'='.$value;
		}
		else {
			$field = $this->preferences['group_field'];
			$configLDAP = $this->makeLDAPconfig();
			$ldap = new LDAP($configLDAP);
			$sr = $ldap->searchDN($group_->id, array($field));
			if ($sr === false) {
				Logger::error('main', 'UsersGroupDB::get_filter_groups_member search failed for ('.$group_->name.')');
				return array('users' => array());
			}
			
			$infos = $ldap->get_entries($sr);
			if (!is_array($infos) || $infos === array()) {
				return array('users' => array());
			}
			
			$keys = array_keys($infos);
			$dn = $keys[0];
			$info = $infos[$dn];
			if (! array_key_exists($field, $info)) {
				return null;
			}
			
			if (is_array($info[$field])) {
				if (isset($info[$field]['count'])) {
					unset($info[$field]['count']);
				}
				
				$members = $info[$field];
			}
			else {
				$members = array($info[$field]);
			}
			
			$filter_rdn_rules = array();
			if ($this->preferences['group_field_type'] == 'user_dn') {
				$result['dns'] = $members;
				foreach($members as $dn) {
					$expl = explode_with_escape(',', $dn, 2);
					$rdn = $expl[0];
					array_push($filter_rdn_rules, $rdn);
				}
				
				$result['filter'] = LDAP::join_filters($filter_rdn_rules, '|');
			}
			else {
				$result['users'] = $members;
			}
		}
		
		return $result;
	}
	
	public function get_groups_including_user_from_list($groups_dn, $user_) {
		$groups_result = array();
		# Be sure to use the simpliest method
		# AD recursive group search can cause some problems with the other method
		if (in_array('group_field', $this->preferences['group_match_user'])) {
			$filters = array();
			$filter_rdn_rules = array();
			foreach($groups_dn as $group_dn) {
				$expl = explode_with_escape(',', $group_dn, 2);
				$rdn = $expl[0];
				array_push($filter_rdn_rules, $rdn);
			}
			
			array_push($filters, LDAP::join_filters($filter_rdn_rules, '|'));
			
			if ($this->preferences['group_field_type'] == 'user_dn') {
				$item = $user_->getAttribute('dn');
			}
			else {
				$item = $user_->getAttribute('login');
			}
			
			array_push($filters, $this->preferences['group_field'].'='.$item);
			
			$filter = LDAP::join_filters($filters, '&');
			$groups2 = $this->import_from_filter($filter);
			foreach($groups2 as $group_id => $group) {
				if (! in_array($group_id, $groups_dn)) {
					continue;
				}
				
				$groups_result[$group->id] = $group;
			}
		}
		else { // user_field
			$groups = $this->imports($groups_dn);
			
			$field = $this->preferences['user_field'];
			$configLDAP = $this->makeLDAPconfig();
			// get userdb ldap config instead!!!
		
			$ldap = new LDAP($configLDAP);
			$sr = $ldap->searchDN($user_->getAttribute('dn'), array($field));
			if ($sr === false) {
				return array();
			}
			
			$infos = $ldap->get_entries($sr);
			if (!is_array($infos) || $infos === array()) {
				return array();
			}
			
			$keys = array_keys($infos);
			$dn = $keys[0];
			$info = $infos[$dn];
			if (! array_key_exists($field, $info)) {
				return array();
			}
			
			if (is_array($info[$field])) {
				if (isset($info[$field]['count'])) {
					unset($info[$field]['count']);
				}
				
				$memberof = $info[$field];
			}
			else {
				$memberof = array($info[$field]);
			}
			
			foreach($groups as $group) {
				if ($this->preferences['user_field_type'] == 'group_dn') {
					$item = $group->id;
				}
				else {
					$item = $group->name;
				}
				
				if (! in_array($item, $memberof)) {
					continue;
				}
				
				$groups_result[$group->id] = $group;
			}
		}
		
		return $groups_result;
	}
	
	public function isWriteable() {
		return false;
	}
	
	public static function prefsIsValid($prefs_, &$log=array()) {
		$prefs2 = $prefs_->get('UserGroupDB','ldap');
		
		if (is_null(LDAP::join_filters(array($prefs2['filter']), '|'))) {
			$log['LDAP usersgroup filter'] = false;
			return false;
		}
		
		$log['LDAP usersgroup filter'] = true;
		if (! array_keys_exists_not_empty(array('name'), $prefs2['match'])) {
			$log['LDAP usersgroup match'] = false;
			return false;
		}
		
		$log['LDAP usersgroup match'] = true;
		
		$group_match_user_enabled_count = 0;
		if (in_array('user_field', $prefs2['group_match_user'])) {
			if (strlen($prefs2['user_field']) == 0) {
				$log['LDAP group_match_user user_member'] = false;
				return false;
			}
			
			$group_match_user_enabled_count++;
		}
		if (in_array('group_field', $prefs2['group_match_user'])) {
			if (strlen($prefs2['group_field']) == 0) {
				$log['LDAP group_match_user group_membership'] = false;
				return false;
			}
			
			$group_match_user_enabled_count++;
		}
		
		if ($group_match_user_enabled_count == 0) {
			$log['LDAP group_match_user'] = false;
			return false;
		}
		
		return true;
	}
	
	public function import($id1_) {
		Logger::debug('main',"UserGroupDB::ldap::import (id = $id1_)");
		
		if (is_base64url($id1_))
			$id = base64url_decode($id1_);
		else
			$id = $id1_;
		
		if (array_key_exists($id, $this->cache_import)) {
			return $this->cache_import[$id];
		}
		else {
			$ug = $this->import_nocache($id);
			$this->cache_import[$id] = $ug;
			return $ug;
		}
	}
	
	protected function import_nocache($id_) {
		Logger::debug('main',"UserGroupDB::ldap::import_nocache (id = $id_)");
		$configLDAP = $this->makeLDAPconfig();
		
		if (! str_endswith(strtolower($id_), strtolower($configLDAP['suffix']))) {
			Logger::error('main', "UserGroupDB::ldap::import_nocache unable to import '$id_' because not same LDAP suffix");
			return NULL;
		}
		
		$expl = explode_with_escape(',', $id_, 2);
		$rdn = $expl[0];
		$configLDAP['suffix'] = $expl[1];
		$filter = LDAP::join_filters(array($this->preferences['filter'], $rdn), '&');
		
		$ldap = new LDAP($configLDAP);
		$sr = $ldap->search($rdn, array_values($this->preferences['match']));
		if ($sr === false) {
			Logger::error('main', "UserGroupDB::ldap::import_nocache search failed for ($id_)");
			return NULL;
		}
		
		$infos = $ldap->get_entries($sr);
		if (!is_array($infos) || $infos === array())
			return NULL;
		
		$keys = array_keys($infos);
		$dn = $keys[0];
		$info = $infos[$dn];
		return $this->generateUsersGroupFromRow($info, $dn, $this->preferences['match']);
	}

	public function imports($ids_) {
		Logger::debug('main','UserGroupDB::ldap::imports (['.implode(', ', $ids_).'])');
		if (count($ids_) == 0) {
			return array();
		}
		
		$result = array();
		$ids_filter = array();
		foreach($ids_ as $dn) {
			if (array_key_exists($dn, $this->cache_import)) {
				$g = $this->cache_import[$dn];
				$result[$g->getUniqueID()] = $g;
			}
			else if (strstr($dn, ',') !== false) {
				list($rdn, $subpath) = explode(',', $dn, 2);
				array_push($ids_filter, '('.$rdn.')');
			}
		}
		
		if (count($ids_filter) == 0) {
			return $result;
		}
		
		$ids_filter = LDAP::join_filters($ids_filter, '|');
		
		$res2 = $this->import_from_filter($ids_filter);
		foreach ($res2 as $dn => $g) {
			if (! in_array($dn, $ids_)) {
				continue;
			}
			
			$result[$dn] = $g;
			
		}
		return $result;
	}
	
	 public function import_from_filter($filter_) {
		$filter = LDAP::join_filters(array($this->preferences['filter'], $filter_), '&');
		
		$configLDAP = $this->makeLDAPconfig();
		$ldap = new LDAP($configLDAP);
		$sr = $ldap->search($filter, array_values($this->preferences['match']));
		if ($sr === false) {
			Logger::error('main', 'UserGroupDB::ldap::import_from_filter search failed');
			return NULL;
		}
		
		$result = array();
		
		$infos = $ldap->get_entries($sr);
		if (! is_array($infos))
			return $result;
		
		foreach ($infos as $dn => $info) {
			$g = $this->generateUsersGroupFromRow($info, $dn, $this->preferences['match']);
			if (! is_object($g)) {
				continue;
			}
			
			$result[$dn] = $g;
		}
		
		return $result;
	}
	
	protected function generateUsersGroupFromRow($info, $dn_, $match_) {
		$extras = array();
		$buf = array();
		foreach ($match_ as $attribut => $match_ldap) {
			if (isset($info[$match_ldap][0])) {
				$buf[$attribut] = $info[$match_ldap][0];
			}
			if (isset($info[$match_ldap]) && is_array($info[$match_ldap])) {
				if (isset($info[$match_ldap]['count']))
					unset($info[$match_ldap]['count']);
				$extras[$attribut] = $info[$match_ldap];
			}
			else {
				$extras[$attribut] = array();
			}
		}
		if (!isset($buf['description']))
			$buf['description'] = '';
		else if (is_array($buf['description'])) {
			$buf['description'] = array_pop($buf['description']);
		}
		
		if (!isset($buf['name']))
			$buf['name'] = $dn_;
		else if (is_array($buf['name'])) {
			$buf['name'] = array_pop($buf['name']);
		}
		
		$ug = new UsersGroup($dn_, $buf['name'], $buf['description'], true);
		$ug->extras = $extras;
		$this->cache_import[$dn_] = $ug;
		return $ug;
	}
	
	public static function configuration() {
		$ret = array();
		$c = new ConfigElement_input('filter', '(objectClass=posixGroup)');
		$ret []= $c;
		$c = new ConfigElement_dictionary('match', array(
			'name' => 'cn',
			'description' => 'description',
		));
		$ret []= $c;
		
		$c = new ConfigElement_multiselect('group_match_user', array('group_field'));
		$c->setContentAvailable(array('user_field', 'group_field'));
		$ret []= $c;
		
		$c = new ConfigElement_input('user_field', 'member');
		$ret []= $c;
		$c = new ConfigElement_select('user_field_type', 'group_dn');
		$c->setContentAvailable(array(
			'group_dn',
			'group_name',
		));
		$ret []= $c;
		
		$c = new ConfigElement_input('group_field', 'memberUid');
		$ret []= $c;
		$c = new ConfigElement_select('group_field_type', 'user_login');
		$c->setContentAvailable(array(
			'user_dn',
			'user_login',
		));
		$ret []= $c;
		
		$c = new ConfigElement_input('ou', ''); // optionnal
		$ret []= $c;
		return $ret;
	}
	
	public static function isDefault() {
		return false;
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
