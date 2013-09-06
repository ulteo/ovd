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
	
	public function getGroupsContains($contains_, $attributes_=array('name', 'description'), $limit_=0) {
		$groups = array();
		$configLDAP = $this->makeLDAPconfig();
		
		$ldap = new LDAP($configLDAP);
		$contains = '*';
		if ($contains_ != '')
			$contains .= $contains_.'*';
		
		$missing_attribute_nb = 0;
		$filter_attr = array();
		foreach ($attributes_ as $attribute) {
			if (! array_key_exists($attribute, $this->preferences['match']) || strlen($this->preferences['match'][$attribute])==0) {
				$missing_attribute_nb++;
				continue;
			}
			
			array_push($filter_attr, '('.$this->preferences['match'][$attribute].'='.$contains.')');
		}
		
		if ($missing_attribute_nb == count($attributes_)) {
			return array(array(), false);
		}
		
		$filter_attr = LDAP::join_filters($filter_attr, '|');
		$filter = LDAP::join_filters(array($this->preferences['filter'], $filter_attr), '&');
		$sr = $ldap->search($filter, array_values($this->preferences['match']), $limit_);
		if ($sr === false) {
			Logger::error('main', 'UsersGroupDB::ldap::getUsersContaint search failed');
			return array(array(), false);
		}
		$sizelimit_exceeded = $ldap->errno() === 4; // LDAP_SIZELIMIT_EXCEEDED => 0x04 
		
		$infos = $ldap->get_entries($sr);
		
		foreach ($infos as $dn => $info) {
			$ug = $this->generateUsersGroupFromRow($info, $dn, $this->preferences['match']);
			$groups[$dn] = $ug;
		}
		return array($groups, $sizelimit_exceeded);
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
	
	public static function liaisonType() {
		return array(array('type' => 'UsersGroup', 'owner' => 'ldap'));
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
 
