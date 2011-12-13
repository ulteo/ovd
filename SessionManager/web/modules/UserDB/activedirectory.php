<?php
/**
 * Copyright (C) 2008-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2008-2011
 * Author Julien LANGLOIS <julien@ulteo.com> 2011
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

	public function authenticate($user_, $password_){
		Logger::debug('main','UserDB::activedirectory::authenticate '.$user_->getAttribute('login'));
		$conf_ldap2 = $this->config;
		$conf_ldap2['login'] = $user_->getAttribute('login').'@'.$this->config_ad['domain'];
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
					'real_login'    => 'sAMAccountName',
					'countrycode' => 'c', // in ISO-3166 see http://en.wikipedia.org/wiki/ISO_3166-1_alpha-2
					'objectclass' => 'objectClass'
		);

		$config_ldap = array(
			'hosts' =>  $config['hosts'],
			'suffix' => $ldap_suffix,

			'login' => $config['login'].'@'.$config['domain'],
			'password' => $config['password'],

			'port'	=> '389',
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
		$c = new ConfigElement_list('hosts', _('Server host address'), _('The address of your Active Directory server.'), _('The address of your Active Directory server.'),  array());
		$ret []= $c;
		$c = new ConfigElement_input('domain', _('Domain name'), _('Domain name used by Active Directory'), _('Domain name used by Active Directory'), NULL);
		$ret []= $c;
		$c = new ConfigElement_input('login', _('Administrator DN'), _('The user login that must be used to access the database (to list users accounts).'), _('The user login that must be used to access the database (to list users accounts).'), NULL);
		$ret []= $c;
		$c = new ConfigElement_password('password', _('Administrator password'), _('The user password that must be used to access the database (to list users accounts).'), _('The user password that must be used to access the database (to list users accounts).'), NULL);
		$ret []= $c;
		$c = new ConfigElement_dictionary('match', _('match'), _('match'), _('match'), array());
		$ret []= $c;
		$c = new ConfigElement_select('accept_expired_password', _('Accept expired password'), _('Authorize a user connection even if the password has expired, to have the Windows server perform the password renew process'), _('Authorize a user connection even if the password has expired, to have the Windows server perform the password renew process'), 0);
		$c->setContentAvailable(array(0=>_('no'),1=>_('yes')));
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

	public static function prettyName() {
		return _('Active Directory');
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
}
