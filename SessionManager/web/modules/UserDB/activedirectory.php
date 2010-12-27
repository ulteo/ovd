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
		parent::__construct();
		$prefs = Preferences::getInstance();
		if (! $prefs)
			die_error('get Preferences failed',__FILE__,__LINE__);
		$this->config_ad = $prefs->get('UserDB','activedirectory');

		if (is_null($this->config_ad))
			die_error('Active Directory configuration missing2',__FILE__,__LINE__);

		$this->config =  $this->makeLDAPconfig();
	}

	public function makeLDAPconfig($config_=NULL) {
		if ($config_ != NULL)
			$config = $config_;
		else
			$config = $this->config_ad;

		$ldap_suffix = domain2suffix($config['domain']);
		if (! $ldap_suffix)
			die_error('Active Directory configuration not valid (domain2suffix error)2',__FILE__,__LINE__);

		if (! str_endswith($config['login'], $ldap_suffix))
			$config['login'] .= ','.$ldap_suffix;


		$match_minimal  = array(
					'login'	=> 'sAMAccountName',
					'displayname'	=> 'displayName',
					'real_login'    => 'sAMAccountName',
					'countrycode' => 'c' // in ISO-3166 see http://en.wikipedia.org/wiki/ISO_3166-1_alpha-2
		);

		$config_ldap = array(
			'hosts' =>  $config['hosts'],
			'suffix' => $ldap_suffix,

			'login' => $config['login'],
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

		if (! UserDB_ldap::isValidDN($config_AD['login'])) {
			$log['isValidDN for \''.$config_AD['login'].'\''] = false;
			return false;
		}
		$log['isValidDN for \''.$config_AD['login'].'\''] = true;

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
}
