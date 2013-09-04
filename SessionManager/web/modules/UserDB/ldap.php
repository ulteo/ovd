<?php
/**
 * Copyright (C) 2008-2013 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2008-2010
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
require_once(dirname(__FILE__).'/../../includes/core.inc.php');

class UserDB_ldap  extends UserDB {
	public $config;
	protected $cache_userlist=NULL;
	protected $cache_users=NULL;
	protected $cache_userlist_dn=NULL;
	
	public function __construct () {
		$prefs = Preferences::getInstance();
		if (! $prefs)
			die_error('get Preferences failed',__FILE__,__LINE__);
		$this->config = $prefs->get('UserDB','ldap');
		
		$this->cache_users = array();
		$this->cache_userlist_dn = array();
		
	}
	public function import($login_){
		Logger::debug('main','UserDB::ldap::import('.$login_.')');
		if (is_array($this->cache_userlist) && isset($this->cache_userlist[$login_])) {
			if ($this->isOK($this->cache_userlist[$login_]))
				return $this->cache_userlist[$login_];
			else
				return NULL;
		}
		
		if (is_array($this->cache_users) && isset($this->cache_users[$login_])) {
			if ($this->isOK($this->cache_users[$login_]))
				return $this->cache_users[$login_];
			else
				return NULL;
		}
		
		$ldap = new LDAP($this->makeLDAPconfig());
		$sr = $ldap->search($this->config['match']['login'].'='.$login_, NULL, 1);
		if ($sr === false) {
			Logger::error('main', "UserDB_ldap::import($login_) ldap failed (usually timeout on server)");
			return NULL;
		}
		$infos = $ldap->get_entries($sr);
		if (!is_array($infos)) {
			Logger::error('main', "UserDB_ldap::import($login_) get_entries failed (does user exist ?)");
			return NULL;
		}
		if ($infos == array()) {
			Logger::error('main', "UserDB_ldap::import($login_) get_entries is empty");
			return NULL;
		}
		$keys = array_keys($infos);
		$dn = $keys[0];
		$info = $infos[$dn];
		$u = $this->generateUserFromRow($info);
		$u->setAttribute('dn',  $dn);
		$u = $this->cleanupUser($u);
		$this->cache_users[$login_] = $u;
		if ($this->isOK($u))
			return $u;
		else
			return NULL;
	}

	public function imports($logins_){
		Logger::debug('main','UserDB::ldap::imports(['.implode(', ', $logins_).'])');
		if (count($logins_) == 0) {
			return array();
		}
		
		$result = array();
		$users_filter = array();
		foreach($logins_ as $login) {
			if (is_array($this->cache_userlist) && isset($this->cache_userlist[$login])) {
				if ($this->isOK($this->cache_userlist[$login])) {
					$result[$login] = $this->cache_userlist[$login];
				}
			}
			else if (is_array($this->cache_users) && isset($this->cache_users[$login])) {
				if ($this->isOK($this->cache_users[$login])) {
					$result[$login] = $this->cache_users[$login];
				}
			}
			else {
				array_push($users_filter, '('.$this->config['match']['login'].'='.$login.')');
			}
		}
		
		if (count($users_filter) == 0) {
			return $result;
		}
		
		$res2 = $this->import_from_filter('(|'.implode('', $users_filter).')');
		return array_merge($result, $res2);
	}

	public function import_from_filter($filter_) {
		$filter = LDAP::join_filters(array($this->generateFilter(), $filter_), '&');
		
		$ldap = new LDAP($this->makeLDAPconfig());
		$sr = $ldap->search($filter, array_values($this->config['match']));
		if ($sr === false) {
			Logger::error('main', 'UserDB::ldap::imports search failed');
			return NULL;
		}
		
		$infos = $ldap->get_entries($sr);
		$result = array();
		foreach ($infos as $dn => $info) {
			$u = $this->generateUserFromRow($info);
			$u->setAttribute('dn',  $dn);
			
			$u = $this->cleanupUser($u);
			$this->cache_users[$u->getAttribute('login')] = $u;
			if ($this->isOK($u))
				$result[$u->getAttribute('login')]= $u;
			else {
				if ($u->hasAttribute('login'))
					Logger::info('main', 'UserDB::ldap::imports user \''.$u->getAttribute('login').'\' not ok');
				else
					Logger::info('main', 'UserDB::ldap::imports user does not have login');
			}
		}
		return $result;
	}

	public function importFromDN($dn_) {
		Logger::debug('main','UserDB::ldap::fromDN('.$dn_.')');
		
		if (is_array($this->cache_userlist_dn) && isset($this->cache_userlist_dn[$dn_])) {
			if ($this->isOK($this->cache_userlist_dn[$dn_]))
				return $this->cache_userlist_dn[$dn_];
			else
				return NULL;
		}

		$config = $this->config;
		$ldap = new LDAP($config);
		$sr = $ldap->searchDN($dn_, NULL, 1);
		if ($sr === false) {
			Logger::error('main','UserDB_ldap::fromDN ldap failed (mostly timeout on server)');
			return NULL;
		}
		$infos = $ldap->get_entries($sr);
		if (count($infos) == 0) {
			Logger::error('main','UserDB_ldap::fromDN ldap returned empty result');
			return NULL;
		}

		$keys = array_keys($infos);
		$dn = $keys[0];
		$info = $infos[$dn];
		$u = $this->generateUserFromRow($info);
		$u->setAttribute('dn',  $dn);
		$u = $this->cleanupUser($u);
		$this->cache_userlist_dn[$dn_] = $u;
		if ($this->isOK($u)) {
			return $u;
		}
		else
			return NULL;
	}
	
	public function getList() {
		Logger::debug('main','USERDB::ldap::getList');
		if (!is_array($this->cache_userlist)) {
			$users = $this->getList_nocache();
			$this->cache_userlist = $users;
		}
		else {
			$users = $this->cache_userlist;
		}
		
		return $users;
	}

	public function getList_nocache() {
		Logger::debug('main','UserDB::ldap::getList_nocache');
		$users = array();

		$ldap = new LDAP($this->makeLDAPconfig());
		$filter = $this->generateFilter();
		$sr = $ldap->search($filter, NULL);
		if ($sr === false) {
			Logger::error('main', 'UserDB::ldap::getList_nocache search failed');
			return NULL;
		}
		$infos = $ldap->get_entries($sr);
		foreach ($infos as $dn => $info) {
			$u = $this->generateUserFromRow($info);
			$u->setAttribute('dn',  $dn);
			
			$u = $this->cleanupUser($u);
			if ($this->isOK($u))
				$users[$u->getAttribute('login')]= $u;
			else {
				if ($u->hasAttribute('login'))
					Logger::info('main', 'UserDB::ldap::getList_nocache user \''.$u->getAttribute('login').'\' not ok');
				else
					Logger::info('main', 'UserDB::ldap::getList_nocache user does not have login');
			}
		}
		return $users;
	}
	
	public function getUsersContains($contains_, $attributes_=array('login', 'displayname'), $limit_=0) {
		$users = array();
		$ldap = new LDAP($this->makeLDAPconfig());
		$contains = '*';
		if ( $contains_ != '')
			$contains .= $contains_.'*';
		$contains = preg_replace('/\*\*+/', '*', $contains); // ldap does not handle multiple star characters
		
		$filter = '(&'.$this->generateFilter().'(|';
		foreach ($attributes_ as $attribute) {
			$filter .= '('.$this->config['match'][$attribute].'='.$contains.')';
		}
		$filter .= '))'; 
		$sr = $ldap->search($filter, array_values($this->config['match']), $limit_);
		if ($sr === false) {
			Logger::error('main', 'UserDB::ldap::getUsersContaint search failed');
			return array(array(), false);
		}
		$sizelimit_exceeded = $ldap->errno() === 4; // LDAP_SIZELIMIT_EXCEEDED => 0x04 
		
		$infos = $ldap->get_entries($sr);
		foreach ($infos as $dn => $info) {
			$u = $this->generateUserFromRow($info);
			$u->setAttribute('dn',  $dn);
			
			$u = $this->cleanupUser($u);
			if ($this->isOK($u))
				$users []= $u;
			else {
				if ($u->hasAttribute('login'))
					Logger::info('main', 'UserDB::ldap::getUsersContaint user \''.$u->getAttribute('login').'\' not ok');
				else
					Logger::info('main', 'UserDB::ldap::getUsersContaint user does not have login');
			}
		}
		
		return array($users, $sizelimit_exceeded);
	}
	
	public function generateFilter() {
		$filter = '('.$this->config['match']['login'].'=*)';
		if (isset($this->config['filter'])) {
			if ($this->config['filter'] != '') {
				$filter = html_entity_decode($this->config['filter']);
			}
		}
		return $filter;
	}
	
	protected function generateUserFromRow($row_) {
		$u = new User();
		foreach ($this->config['match'] as $attribut => $match_ldap) {
			if (isset($row_[$match_ldap])) {
				if (is_array($row_[$match_ldap])) {
					unset($row_[$match_ldap]['count']);
					if (count($row_[$match_ldap]) == 1) {
						$u->setAttribute($attribut, $row_[$match_ldap][0]);
					}
					else {
						$u->setAttribute($attribut, $row_[$match_ldap]);
					}
				}
				else {
					$u->setAttribute($attribut, $row_[$match_ldap]);
				}
			}
		}
		
		if (is_array($u->getAttribute('login'))) {
			$u->setAttribute('login', array_pop($u->getAttribute('login')));
		}
		
		if ($u->hasAttribute('displayname') == false) {
			Logger::debug('main', 'UserDB::ldap::generateUserFromRow user '.$u->getAttribute('login').' does not have a displayname, generate one');
			$u->setAttribute('displayname', $u->getAttribute('login'));
		}
		else if (is_array($u->getAttribute('displayname'))) {
			$u->setAttribute('displayname', array_pop($u->getAttribute('displayname')));
		}
		
		return $u;
	}
	
	protected function cleanupUser($u){
		if (is_object($u)){
			if ($u->hasAttribute('homedir')){
				// replace \ by / in homedir
				$u->setAttribute('homedir',str_replace('\\','/',$u->getAttribute('homedir')));

				// create  fileserver from homedir
				// Matchs on "//myserveur.mydomain.net/path/to/home"
				$r = '`\/\/([\w-_\.]+)(\/.+)`';
				if (preg_match($r, $u->getAttribute('homedir'), $matches)) {
					$u->setAttribute('fileserver',$matches[1]);
				}
			}
		}
		return $u;
	}

	public function isWriteable(){
		return false;
	}

	public function canShowList(){
		return true;
	}

	public function needPassword(){
		return true;
	}

	public function authenticate($user_,$password_){
		Logger::debug('main','UserDB::ldap::authenticate '.$user_->getAttribute('login'));
		$conf_ldap2 = $this->config;
		if (($user_->hasAttribute('dn')) && ($user_->getAttribute('dn') !== ''))
			$conf_ldap2['login'] = $user_->getAttribute('dn');
		else
			$conf_ldap2['login'] = $user_->getAttribute('login');
		$conf_ldap2['password'] = $password_;

		$LDAP2 = new LDAP($conf_ldap2);
		$a = $LDAP2->connect();
		$LDAP2->disconnect();
		if ( $a == false)
			Logger::debug('main','USERDB::LDAP::authenticate \''.$user_->getAttribute('login').'\' is not authenticate');
		else
			Logger::debug('main','USERDB::LDAP::authenticate '.$user_->getAttribute('login').' result '.$a);
		return $a;
	}

	public static function configuration() {
		$ret = array();
		$c = new ConfigElement_list('hosts', array('servldap.example.com'));
		$ret []= $c;
		$c = new ConfigElement_input('port', '389');
		$ret []= $c;
		$c = new ConfigElement_input('login', '');
		$ret []= $c;
		$c = new ConfigElement_password('password', '');
		$ret []= $c;
		$c = new ConfigElement_input('suffix', 'dc=servldap,dc=example,dc=com');
		$ret []= $c;
		$c = new ConfigElement_dictionary('options', array('LDAP_OPT_PROTOCOL_VERSION' => '3', 'LDAP_OPT_REFERRALS' => 0));
		$ret []= $c;
		
		$c = new ConfigElement_input('filter', '');
		$ret []= $c;
		$c = new ConfigElement_dictionary('match', array('login' => 'uid', 'uid' => 'uidnumber',  'displayname' => 'displayName', 'distinguishedname' => 'distinguishedname'));
		$ret []= $c;
		
		$c = new ConfigElement_input('ou', ''); // optionnal
		$ret []= $c;
		
		$c = new ConfigElement_dictionary('extra', array());
		$ret []= $c;

		return $ret;
	}

	public static function prefsIsValid($prefs_, &$log=array()) {
		$config_ldap = $prefs_->get('UserDB','ldap');
		$LDAP2 = new LDAP($config_ldap);
		$ret = $LDAP2->connect($log);
		$LDAP2->disconnect();
		if ($ret === false) {
			return false;
		}
		
		if (is_null(LDAP::join_filters(array($config_ldap['filter']), '|'))) {
			$log['LDAP user filter'] = false;
			return false;
		}
		
		$log['LDAP user filter'] = true;
		if (! array_keys_exists_not_empty(array('login', 'displayname'), $config_ldap['match'])) {
			$log['LDAP users match'] = false;
			return false;
		}
		
		$log['LDAP users match'] = true;
		return true;
	}
	
	public function makeLDAPconfig($config_=NULL) {
		if (is_null($config_) === false) {
			return $config_;
		}
		
		$configLDAP = array_merge(array(), $this->config);
		if (array_keys_exists_not_empty(array('ou'), $configLDAP)) {
			$configLDAP['suffix'] = $configLDAP['ou'].','.$configLDAP['suffix'];
		}
		
		return $configLDAP;
	}

	public static function isValidDN($dn_) {
		$base_pattern = '[a-zA-Z]+=[^,=]+';

		return (preg_match('/^'.$base_pattern.'(,'.$base_pattern.')*$/', $dn_) == 1);
	}

	public static function isDefault() {
		return false;
	}

	public function getAttributesList() {
		return array_keys($this->config['match']);
	}
	
	public function add($user_){
		return false;
	}

	public function remove($user_){
		return false;
	}

	public function modify($user_){
		return false;
	}

	public static function init($prefs_) {
		return true;
	}

	public static function enable() {
		return true;
	}
}
