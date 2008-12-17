<?php
/**
 * Copyright (C) 2008 Ulteo SAS
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

class UserDB_activedirectory  extends UserDB_ldap{
	public $config_ldap;
	public function __construct(){
		$this->config = array();
		$this->config_ldap =  $this->makeLDAPconfig();
	}

	public function import($login_){
		// dirty hack
		$config2 = $this->config;
		$this->config = $this->config_ldap;
		$u = parent::import($login_);
		$this->config = $config2;

		return $this->cleanupUser($u);
	}

	public function getList(){
		$users = array();
		$ldap = new LDAP($this->config_ldap);
		$sr = $ldap->search($this->config_ldap['match']['login'].'=*', NULL);
		if ($sr === false) {
			Logger::error('main','UserDB_activedirectory::getList ldap failed (mostly timeout on server)');
			return NULL;
		}
		$infos = $ldap->get_entries($sr);
		foreach ($infos as $info){
			$u = new User();
			foreach ($this->config_ldap['match'] as $attribut => $match_ldap){
				if (isset($info[$match_ldap][0]))
					$u->setAttribute($attribut,$info[$match_ldap][0]);
			}
			if ($u->hasAttribute('uid') == false)
				$u->setAttribute('uid',str2num($u->getAttribute('login')));
			$u = $this->cleanupUser($u);
			if ($this->isOK($u))
				$users []= $u;
		}
		return $users;
	}

	private function cleanupUser($u){
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

	protected function makeLDAPconfig($prefs_=NULL) {
		if (is_NULL($prefs_)) {
			$prefs_ = Preferences::getInstance();
			if (! $prefs_)
				die_error(_('get Preferences failed'),__FILE__,__LINE__);
		}
		$conf_AD = $prefs_->get('UserDB','activedirectory');

		if (is_null($conf_AD))
			die_error('Active Directory configuration missing2',__FILE__,__LINE__);

		$this->config = $conf_AD;
// 		if (is_null($conf_ldap))
// 			die_error('Active Directory configuration missing3',__FILE__,__LINE__);

		$minimum_keys = array ('host', 'domain', 'login', 'password', 'domain', 'ou');
		foreach ($minimum_keys as $m_key){
			if (!isset($this->config[$m_key]))
				die_error('Active Directory configuration not valid (missing \''.$m_key.'\' key)2',__FILE__,__LINE__);
		}
		$ldap_suffix = self::domain2suffix($this->config['domain']);
		if (! $ldap_suffix)
			die_error('Active Directory configuration not valid (domain2suffix error)2',__FILE__,__LINE__);

		if (strtolower(substr($this->config['ou'], 0, 3)) == 'ou=')
			$user_branch = $this->config['ou'];
		else
			$user_branch ='OU='.$this->config['ou'];
		
		if (strtolower(substr($this->config['login'], 0, 3)) == 'cn=')
			$login = $this->config['login'];
		else
			$login ='cn='.$this->config['login'].','.$user_branch;
		
		if (substr($login, -1*strlen($ldap_suffix)) != $ldap_suffix)
			$login .= ','.$ldap_suffix;

		$config_ldap = array(
			'host' =>  $this->config['host'],
			'suffix' => $ldap_suffix,

			'login' => $login,
			'password' => $this->config['password'],

			'port'	=> '389',
			'userbranch'	=> $user_branch,
			'uidprefix' => 'cn',
			'protocol_version' => 3,
			'match' => array(
					'login'	=> 'cn',
					'displayname'	=> 'displayname',
					'homedir'	=> 'homedirectory'
				)
			);
		return $config_ldap;
	}

	protected function domain2suffix($domain_) {
		$domain_ = strtolower($domain_);
		$buf = explode('.', $domain_);
		if (! count($buf))
			return;

		$str='';
		foreach($buf as $d)
			$str.='dc='.$d.',';

		$str = substr($str, 0,-1);
		return $str;
	}

	public function authenticate($user_,$password_){
		Logger::debug('main','UserDB::activedirectory::authenticate '.$user_->getAttribute('login'));
		// dirty hack
		$config2 = $this->config;
		$this->config = $this->config_ldap;
		$ret = parent::authenticate($user_,$password_);
		$this->config = $config2;
		return $ret;
	}

	public function configuration(){
		$ret = array();
		$c = new config_element('host', _('Server host address'), _('The address of your AD.'), _('The address of your AD.'), NULL, NULL, 1);
		$ret []= $c;
		$c = new config_element('domain', _('Domain name'), _('Domain name use by AD'), _('Domain name use by AD'), NULL, NULL, 1);
		$ret []= $c;
		$c = new config_element('login', _('User login'), _('The user login that must be used to access the database (to list users account).'), _('The user login that must be used to access the database (to list users account).'), NULL, NULL, 1);
		$ret []= $c;
		$c = new config_element('password', _('User password'), _('The user password that must be used to access the database (to list users account).'), _('The user password that must be used to access the database (to list users account).'), NULL, NULL, 7);
		$ret []= $c;
		$c = new config_element('ou', _('(OU)'), _('OU'), _('OU'), NULL, NULL, 1);
		$ret []= $c;
		return $ret;
	}

	public function prefsIsValid($prefs_) {
		$config_AD = $prefs_->get('UserDB','activedirectory');
		$config_ldap = self::makeLDAPconfig($prefs_);
		$LDAP2 = new LDAP($config_ldap);
		$ret = $LDAP2->connect();
		$LDAP2->disconnect();
		
		return ($ret === true);
	}

	public static function prettyName() {
		return _('Active Directory');
	}

	public static function init() {
		return true;
	}


}
