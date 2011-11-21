<?php
/**
 * Copyright (C) 2008-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2008-2011
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2008-2010
 * Author Antoine WALTER <anw@ulteo.com> 2008
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
if (!function_exists('ldap_connect'))
	die_error('Please install LDAP support for PHP',__FILE__,__LINE__);

if (! defined('LDAP_INVALID_CREDENTIALS'))
	define('LDAP_INVALID_CREDENTIALS', 0x0031);

class LDAP {
	private $link=NULL;
	private $buf_errno;
	private $buf_error;

	private $hosts = array();
	private $port;
	private $login;
	private $password;
	private $suffix;
	private $userbranch;
	private $options = array();
	private $attribs = array();

	public function __construct($config_){
		Logger::debug('main', 'LDAP - construct');
		if (isset($config_['hosts']))
			$this->hosts = $config_['hosts'];
		if (isset($config_['port']))
			$this->port = $config_['port'];
		if (isset($config_['login']))
			$this->login = $config_['login'];
		if (isset($config_['password']))
			$this->password = $config_['password'];
		if (isset($config_['suffix']))
			$this->suffix = $config_['suffix'];
		if (isset($config_['userbranch']))
			$this->userbranch = $config_['userbranch'];
		if (isset($config_['options']))
			$this->options = $config_['options'];

	}
	public function __sleep() {
		$this->disconnect();
	}

	public function __wakeup() {
		$this->connect();
	}

	private function check_link() {
		if (is_null($this->link)){
			$this->connect();
		}
	}

	private function connect_on_one_host($host, &$log=array()) {
		Logger::debug('main', 'LDAP - connect_on_one_host(\''.$host.'\', \''.$this->port.'\')');
		$buf = false;
		$buf = @ldap_connect($host, $this->port);
		if (!$buf) {
			Logger::error('main', 'Link to LDAP server failed. Please try again later.');
			$log['LDAP connect'] = false;
			return false;
		}
		$log['LDAP connect'] = true;

		$this->link = $buf;
		foreach ($this->options as $an_option => $an_value) {
			@ldap_set_option($this->link, constant($an_option), $an_value);
		}

		if ($this->login == '') {
			$buf_bind = $this->bind();
			if ($buf_bind === false) {
				Logger::error('main', 'LDAP::connect bind anonymous failed');
				$log['LDAP anonymous bind'] = false;
			}
			else
				$log['LDAP anonymous bind'] = true;
		}
		else {
			$buf_bind = $this->bind($this->login, $this->password);
			if ($buf_bind === false) {
				Logger::error('main', 'LDAP::connect bind failed');
				$log['LDAP bind'] = false;
			}
			else
				$log['LDAP bind'] = true;
		}
		return $buf_bind;
	}

	public function connect(&$log=array()) {
		Logger::debug('main', 'LDAP - connect(\''.serialize($this->hosts).'\', \''.$this->port.'\')');
		$buf = false;
		foreach ($this->hosts as $host) {
			if ($host === '')
				continue;
			$buf = $this->connect_on_one_host($host, $log);
			if ($buf !== false) {
				break;
			}
		}
		return $buf;
	}

	public function disconnect() {
		Logger::debug('main', 'LDAP - disconnect()');

		@ldap_close($this->link);
	}

	private function bind($dn_=NULL, $pwd_=NULL){
		Logger::debug('main', "LDAP - bind('".$dn_."')");
		$buf = @ldap_bind($this->link, $dn_, $pwd_);

		if (!$buf) {
			Logger::error('main', "LDAP::bind bind with user '$dn_' failed : (error:".$this->errno().')');
			
			$searchbase_array = array();
			if ($this->userbranch != '') {
				$searchbase_array []= $this->userbranch;
			}
			if ($this->suffix != '') {
				$searchbase_array []= $this->suffix;
			}
			$searchbase = implode(',', $searchbase_array);
			
			$protocol_version = '';
			if (array_key_exists('LDAP_OPT_PROTOCOL_VERSION', $this->options))
				$protocol_version = '-P '.$this->options['LDAP_OPT_PROTOCOL_VERSION'];
			$ldapsearch = 'ldapsearch -x -h "'.$this->hosts[0].'" -p '.$this->port.' '.$protocol_version.' -W -D "'.$dn_.'" -LLL -b "'.$searchbase.'"';
			Logger::error('main', 'LDAP - failed to validate the configuration please try this bash command : '.$ldapsearch);
			return false;
		}

		return $buf;
	}

	public function errno() {
		Logger::debug('main', 'LDAP - errno()');

		$this->check_link();

		if ($this->buf_errno)
			return $this->buf_errno;

		return @ldap_errno($this->link);
	}

	public function error() {
		Logger::debug('main', 'LDAP - error()');

		$this->check_link();

		if ($this->buf_error)
			return $this->buf_error;

		return @ldap_error($this->link);
	}

	public function error_string() {
		Logger::debug('main', 'LDAP - error_string()');
		
		$this->check_link();
		
		@ldap_get_option($this->link, LDAP_OPT_ERROR_STRING, $error);
		return $error;
	}
	
	public function search($filter_, $attribs_=NULL, $limit_=0) {
		$this->check_link();
		if ($this->userbranch == '' or is_null($this->userbranch)) {
			$searchbase = $this->suffix;
		}
		else {
			$searchbase = $this->userbranch.','.$this->suffix;
		}
		Logger::debug('main', 'LDAP - search(\''.$filter_.'\',\''.$attribs_.'\',\''.$searchbase.'\')');

		if (is_null($attribs_))
			$attribs_ = $this->attribs;

		$ret = @ldap_search($this->link, $searchbase, $filter_, $attribs_, 0, $limit_);

		if (is_resource($ret))
			return $ret;

		return false;
	}

	public function searchDN($filter_, $attribs_=NULL) {
		$this->check_link();
		if ($this->suffix != '')
			$searchbase =$this->userbranch.','.$this->suffix;
		else
			$searchbase =$this->userbranch;
		
		Logger::debug('main', 'LDAP - searchDN(\''.$filter_.'\',\''.$attribs_.'\',\''.$searchbase.'\')');

		if (is_null($attribs_))
			$attribs_ = $this->attribs;

		$buf = explode_with_escape(',', $filter_, 2);

		$ret = @ldap_search($this->link, $buf[1], $buf[0], $attribs_);

		if (is_resource($ret))
			return $ret;

		return false;
	}

	public function get_entries($search_) {
		Logger::debug('main', 'LDAP - get_entries()');

		$this->check_link();

		if (!is_resource($search_)) {
			Logger::error('main', 'LDAP::get_entries: search_ is not a resource (type: '.gettype($search_).')');
			return false;
		}

		$ret = array();
		for ($entryID=ldap_first_entry($this->link, $search_); $entryID != false; $entryID = ldap_next_entry($this->link, $entryID)) {
			$info = ldap_get_attributes($this->link, $entryID);
			$dn = ldap_get_dn($this->link, $entryID);
			if ( $dn !== false)
				$ret[$dn] = $info;
		}
		return $ret;
	}

	public function count_entries($result_) {
		Logger::debug('main', 'LDAP - count_entries()');

		$this->check_link();

		if (!is_resource($result_))
			return false;

		$ret = @ldap_count_entries($this->link, $result_);

		if (is_numeric($ret))
			return $ret;

		return false;
	}
	
	public function branch_exists($branch) {
		$this->check_link();
		if ($branch == '') {
			$dn = $this->suffix;
		}
		else {
			$dn = $branch.','.$this->suffix;
		}
		$ret = @ldap_read($this->link, $dn, "(objectclass=*)");
		if (is_resource($ret))
			return true;
		return false;
	}
}
