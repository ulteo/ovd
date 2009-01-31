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
	public $config_ad;
	public function __construct(){
		$prefs = Preferences::getInstance();
		if (! $prefs)
			die_error('get Preferences failed',__FILE__,__LINE__);
		$this->config_ad = $prefs->get('UserDB','activedirectory');

		if (is_null($this->config_ad))
			die_error('Active Directory configuration missing2',__FILE__,__LINE__);


		$this->config =  $this->makeLDAPconfig();
	}

	public function import($login_){
		$u = parent::import($login_);

		return $this->cleanupUser($u);
	}

	public function getList(){
		$users = array();
		$ldap = new LDAP($this->config);
		$sr = $ldap->search($this->config['match']['login'].'=*', NULL);
		if ($sr === false) {
			Logger::error('main','UserDB_activedirectory::getList ldap failed (mostly timeout on server)');
			return NULL;
		}
		$infos = $ldap->get_entries($sr);
		foreach ($infos as $info){
			$u = new User();
			foreach ($this->config['match'] as $attribut => $match_ldap){
				if (isset($info[$match_ldap])) {
					unset($info[$match_ldap]['count']);
					if (count($info[$match_ldap]) == 1) {
						$u->setAttribute($attribut,$info[$match_ldap][0]);
					}
					else {
						$u->setAttribute($attribut,$info[$match_ldap]);
					}
				}
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

	public function makeLDAPconfig($config_=NULL) {
		if ($config_ != NULL)
			$config = $config_;
		else
			$config = $this->config_ad;

		$ldap_suffix = self::domain2suffix($config['domain']);
		if (! $ldap_suffix)
			die_error('Active Directory configuration not valid (domain2suffix error)2',__FILE__,__LINE__);

		if (! str_endswith($config['login'], $ldap_suffix))
			$config['login'] .= ','.$ldap_suffix;

		$config_ldap = array(
			'host' =>  $config['host'],
			'suffix' => $ldap_suffix,

			'login' => $config['login'],
			'password' => $config['password'],

			'port'	=> '389',
			'userbranch'	=> $config['ou'],
			'uidprefix' => 'cn',
			'protocol_version' => 3,
			'match' => array(
					'login'	=> 'cn',
					'displayname'	=> 'displayname',
					'homedir'	=> 'homedirectory',
					'real_login'    => 'samaccountname',
					 'memberof' => 'memberof'
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
		$ret = parent::authenticate($user_,$password_);
		return $ret;
	}

	public function configuration(){
		$ret = array();
		$c = new ConfigElement('host', _('Server host address'), _('The address of your Active Directory server.'), _('The address of your Active Directory server.'), NULL, NULL, ConfigElement::$INPUT);
		$ret []= $c;
		$c = new ConfigElement('domain', _('Domain name'), _('Domain name used by Active Directory'), _('Domain name used by Active Directory'), NULL, NULL, ConfigElement::$INPUT);
		$ret []= $c;
		$c = new ConfigElement('login', _('Administrator DN'), _('The user login that must be used to access the database (to list users accounts).'), _('The user login that must be used to access the database (to list users accounts).'), NULL, NULL, ConfigElement::$INPUT);
		$ret []= $c;
		$c = new ConfigElement('password', _('Administrator password'), _('The user password that must be used to access the database (to list users accounts).'), _('The user password that must be used to access the database (to list users accounts).'), NULL, NULL, ConfigElement::$PASSWORD);
		$ret []= $c;
		$c = new ConfigElement('ou', _('User branch DN'), _('User branch DN'), _('User branch DN'), 'cn=Users', NULL, ConfigElement::$INPUT);
		$ret []= $c;
		return $ret;
	}

	public function prefsIsValid($prefs_=NULL) {
		$config_AD = $prefs_->get('UserDB','activedirectory');

		$minimum_keys = array ('host', 'domain', 'login', 'password', 'domain', 'ou');
		foreach ($minimum_keys as $m_key){
			if (!isset($config_AD[$m_key]))
				return false;
		}

		$ldap_suffix = self::domain2suffix($config_AD['domain']);
		if (! $ldap_suffix)
			return false;

		if (! UserDB_ldap::isValidDN($ldap_suffix))
			return false;

		if (! UserDB_ldap::isValidDN($config_AD['login']))
			return false;

		$config_ldap = self::makeLDAPconfig($config_AD);
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

	public static function isDefault() {
		return false;
	}
}
