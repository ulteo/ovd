<?php
/**
 * Copyright (C) 2008-2014 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2008-2011
 * Author Julien LANGLOIS <julien@ulteo.com> 2011, 2013
 * Author David LECHEVALIER <david@ulteo.com> 2014
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

define('AD_LDAP_ERROR_PASSWORD_EXPIRED',     0x532);
define('AD_LDAP_ERROR_PASSWORD_MUST_CHANGE', 0x773);


class UserDB_activedirectory  extends UserDB_ldap{
	public $config_ad;
	public function __construct(){
		parent::__construct();
		$prefs = Preferences::getInstance();
		if (! $prefs)
			die_error('get Preferences failed',__FILE__,__LINE__);
		$this->config_ad = $prefs->get('UserDB','activedirectory');

		if (is_null($this->config_ad))
			die_error('Active Directory configuration missing2',__FILE__,__LINE__);

		$this->config =  $this->makeLDAPconfig();
	}

	public function import($login_){
		Logger::debug('main','UserDB::activedirectory::import('.$login_.')');
		
		$atpos = strpos($login_, '@');
		if ($atpos !== false) {
			$login = substr($login_, 0, $atpos);
			$domain = strtoupper(substr($login_, $atpos+1));
		}
		else {
			$login = $login_;
			$domain = null;
		}
		
		$user = parent::import($login);
		if (is_null($user)) {
			return $user;
		}
		
		if ($domain != null) {
			$user_domain = self::suffix2domain($user->getAttribute('dn'));
			if ($user_domain == $domain || $user_domain == $domain.'.'.strtoupper($this->config_ad['domain'])) {
				$user->setAttribute('domain', $user_domain);
			}
			else if (strtolower($login_) == strtolower($user->getAttribute('principal'))) {
				$user->setAttribute('domain', $domain);
			}
			else {
				Logger::error('main','Bad domain name in given login ('.$domain.') rather than result domain for user: '.$user_domain);
				return null;
			}
		}
		
		return $user;
	}
	
	public function authenticate($user_, $password_){
		Logger::debug('main','UserDB::activedirectory::authenticate '.$user_->getAttribute('login'));
		
		$conf_ldap2 = $this->config;
		$conf_ldap2['login'] = $user_->getAttribute('login').'@';
		
		if ($user_->hasAttribute('domain')) {
			$conf_ldap2['login'].= $user_->getAttribute('domain');
		}
		else {
			$conf_ldap2['login'].= $this->config_ad['domain'];
		}
		
		$conf_ldap2['password'] = $password_;
		
		$LDAP2 = new LDAP($conf_ldap2);
		$a = $LDAP2->connect();
		if ($a == false) {
			if ($this->checkLDAPReturnedExpiredPasswordError($LDAP2)) {
				Logger::info('main','USERDB::activedirectory::authenticate '.$user_->getAttribute('login').' expired password');
				if ($this->config_ad['accept_expired_password'] == 1)
					$a = true;
			}
		}
		$LDAP2->disconnect();
		if ($a == false)
			Logger::debug('main','USERDB::activedirectory::authenticate \''.$user_->getAttribute('login').'\' is not authenticate');
		else
			Logger::debug('main','USERDB::activedirectory::authenticate '.$user_->getAttribute('login').' result '.$a);
		return $a;
	}

	public function makeLDAPconfig($config_=NULL) {
		if ($config_ != NULL)
			$config = $config_;
		else
			$config = $this->config_ad;

		$ldap_suffix = domain2suffix($config['domain']);
		if (! $ldap_suffix)
			die_error('Active Directory configuration not valid (domain2suffix error)2',__FILE__,__LINE__);

		$match_minimal  = array(
					'login'	=> 'sAMAccountName',
					'displayname'	=> 'displayName',
					'principal' => 'userPrincipalName',
		);

		$config_ldap = array(
			'hosts' =>  $config['hosts'],
			'suffix' => $ldap_suffix,

			'login' => $config['login'].'@'.$config['domain'],
			'password' => $config['password'],

			'port'  => $config['port'],
			'use_ssl' =>  $config['use_ssl'],
			'options' => array(
				'LDAP_OPT_PROTOCOL_VERSION' => '3',
				'LDAP_OPT_REFERRALS' => 0,
				),
			'filter' => '(&(objectCategory=person)(objectClass=user))',
			'match' => array_merge($match_minimal, $config['match'])
			);
		return $config_ldap;
	}

	public static function configuration() {
		$ret = array();
		$c = new ConfigElement_list('hosts',  array());
		$ret []= $c;
		$c = new ConfigElement_input('domain', NULL);
		$ret []= $c;
		$c = new ConfigElement_input('login', NULL);
		$ret []= $c;
		$c = new ConfigElement_password('password', NULL);
		$ret []= $c;
		$c = new ConfigElement_input('port', '389');
		$ret []= $c;
		$c = new ConfigElement_select('use_ssl', 0);
		$c->setContentAvailable(array(0,1));
		$ret []= $c;
		$c = new ConfigElement_dictionary('match', array());
		$ret []= $c;
		$c = new ConfigElement_select('accept_expired_password', 0);
		$c->setContentAvailable(array(0, 1));
		$ret []= $c;
		return $ret;
	}

	public static function prefsIsValid($prefs_, &$log=array()) {
		$config_AD = $prefs_->get('UserDB','activedirectory');

		$minimum_keys = array('hosts', 'domain', 'login', 'password', 'domain');
		foreach ($minimum_keys as $m_key){
			if (!isset($config_AD[$m_key])) {
				$log['config_AD has key '.$m_key] = false;
				return false;
			}
// 			else {
// 				$log['config_AD has key '.$m_key] = true;
// 			}
		}

		$ldap_suffix = domain2suffix($config_AD['domain']);
		if (! $ldap_suffix) {
			$log ['domain2suffix for \''.$config_AD['domain'].'\''] = false;
			return false;
		}
		$log ['domain2suffix for \''.$config_AD['domain'].'\''] = true;

		if (! UserDB_ldap::isValidDN($ldap_suffix)) {
			$log['isValidDN for \''.$ldap_suffix.'\''] = false;
			return false;
		}
		$log['isValidDN for \''.$ldap_suffix.'\''] = true;

		$config_ldap = self::makeLDAPconfig($config_AD);
		$LDAP2 = new LDAP($config_ldap);
		$ret = $LDAP2->connect($log);
		if ( $ret === false) {
// 			$log['LDAP connect to \''.$config_ldap['host'].'\''] = false;
			return false;
		}

// 		$log['Connect to AD'] = true;

		$LDAP2->disconnect();
		return true;
	}

	public static function isDefault() {
		return false;
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
	
	function checkLDAPReturnedExpiredPasswordError($ldap_) {
		Logger::debug('main','UserDB::activedirectory::checkLDAPReturnedExpiredPasswordError');
		$errno = $ldap_->errno();
		if ($errno != LDAP_INVALID_CREDENTIALS)
			return false;
		
		$error_string = $ldap_->error_string();
		$ret = preg_match('/80090308: LdapErr:.*data (\w+).*/', $error_string, $matches);
		if ($ret == 0)
			return false;
		
		if (! in_array(hexdec($matches[1]), array(AD_LDAP_ERROR_PASSWORD_EXPIRED, AD_LDAP_ERROR_PASSWORD_MUST_CHANGE)))
			return false;
		
		return true;
	}
	
	public static function suffix2domain($dn_, $separator='DC') {
		$buf = explode(',', $dn_);
		if (! count($buf))
			return null;
		
		$build = array();
		foreach($buf as $s) {
			if (! str_startswith($s, $separator.'='))
				continue;
			
			$build[] = strtoupper(substr($s, strlen($separator)+1));
		}
		
		$str = implode('.', $build);
		return $str;
	}
}
