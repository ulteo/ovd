<?php
/**
 * Copyright (C) 2008-2010 Ulteo SAS
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
		
		$ldap = new LDAP($this->config);
		$sr = $ldap->search($this->config['match']['login'].'='.$login_, NULL);
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
		$sr = $ldap->searchDN($dn_, NULL);
		if ($sr === false) {
			Logger::error('main','UserDB_ldap::fromDN ldap failed (mostly timeout on server)');
			return NULL;
		}
		$infos = $ldap->get_entries($sr);
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
	
	public function getList($sort_=false) {
		Logger::debug('main','USERDB::ldap::getList');
		if (!is_array($this->cache_userlist)) {
			$users = $this->getList_nocache();
			$this->cache_userlist = $users;
		}
		else {
			$users = $this->cache_userlist;
		}
		// do we need to sort alphabetically ?
		if ($sort_ && is_array($users)) {
			usort($users, "user_cmp");
		}
		return $users;
	}

	public function getList_nocache() {
		Logger::debug('main','UserDB::ldap::getList_nocache');
		$users = array();

		$ldap = new LDAP($this->config);
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
		$ldap = new LDAP($this->config);
		$contains = '*';
		if ( $contains_ != '')
			$contains .= $contains_.'*';
		$contains = preg_replace('/\*\*+/', '*', $contains); // ldap does not handle multiple star characters
		
		$filter = '(&'.$this->generateFilter().'(|';
		foreach ($attributes_ as $attribute) {
			$filter .= '('.$this->config['match'][$attribute].'='.$contains.')';
		}
		$filter .= '))'; 
		$sr = $ldap->search($filter, NULL, $limit_);
		if ($sr === false) {
			Logger::error('main', 'UserDB::ldap::getUsersContaint search failed');
			return NULL;
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
		usort($users, "user_cmp");
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
		if ($u->hasAttribute('displayname') == false) {
			Logger::debug('main', 'UserDB::ldap::generateUserFromRow user '.$u->getAttribute('login').' does not have a displayname, generate one');
			$u->setAttribute('displayname', $u->getAttribute('login'));
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
		$c = new ConfigElement_list('hosts', _('Server host address'), _('The address of your LDAP server.'), _('The address of your LDAP server.'), array('servldap.example.com'));
		$ret []= $c;
		$c = new ConfigElement_input('port', _('Server port'), _('The port number used by your LDAP server.'), _('The port use by your LDAP server.'),'389');
		$ret []= $c;
		$c = new ConfigElement_input('login', _('User login'), _('The user login that must be used to access the database (to list users accounts).'), _('The user login that must be used to access the database (to list users accounts).'),'');
		$ret []= $c;
		$c = new ConfigElement_password('password', _('User password'), _('The user password that must be used to access the database (to list users accounts).'), _('The user password that must be used to access the database (to list users accounts).'),'');
		$ret []= $c;
		$c = new ConfigElement_input('suffix','suffix','suffix','suffix','dc=servldap,dc=example,dc=com');
		$ret []= $c;
		$c = new ConfigElement_input('userbranch','userbranch','userbranch','userbranch','ou=People');
		$ret []= $c;
		$c = new ConfigElement_dictionary('options', _('Options given to ldap object'),  _('Options given to ldap object.'), _('Options given to ldap object.'), array('LDAP_OPT_PROTOCOL_VERSION' => '3', 'LDAP_OPT_REFERRALS' => 0));
		$ret []= $c;
		$c = new ConfigElement_dictionary('match',_('Matching'), _('Matching'), _('Matching'), array('login' => 'uid', 'uid' => 'uidnumber',  'displayname' => 'displayname', 'distinguishedname' => 'distinguishedname'));
		$ret []= $c;
		
		$c = new ConfigElement_input('filter', _('Filter (optional)'), _('Filter, example (&(distinguishedname=mike*)(uid=42*))'), _('Filter, example (&(distinguishedname=mike*)(uid=42*))'), '');
		$ret []= $c;

		$c = new ConfigElement_dictionary('extra',_('extra'), _('extra'), _('extra'), array());
		$ret []= $c;

		return $ret;
	}

	public static function prefsIsValid($prefs_, &$log=array()) {
		$config_ldap = $prefs_->get('UserDB','ldap');
		$LDAP2 = new LDAP($config_ldap);
		$ret = $LDAP2->connect($log);
		if ($ret === false) {
			return false;
		}
		$ret = $LDAP2->branch_exists($config_ldap['userbranch']);
		if ( $ret == false) {
			$log['LDAP user branch'] = false;
			$LDAP2->disconnect();
			return false;
		}
		else {
			$log['LDAP user branch'] = true;

		}
		return true;
	}
	
	public function makeLDAPconfig($config_=NULL) {
		if (is_null($config_) === false) {
			return $config_;
		}
		return $this->config;
	}

	public static function isValidDN($dn_) {
		$base_pattern = '[a-zA-Z]+=[^,=]+';

		return (preg_match('/^'.$base_pattern.'(,'.$base_pattern.')*$/', $dn_) == 1);
	}

	public static function prettyName() {
		return _('Lightweight Directory Access Protocol (LDAP)');
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
