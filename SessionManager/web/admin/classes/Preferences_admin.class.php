<?php
/**
 * Copyright (C) 2008-2013 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2008
 * Author David PHAM-VAN <d.pham-van@ulteo.com> 2012
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
require_once(dirname(__FILE__).'/../includes/core.inc.php');
require_once(dirname(__FILE__).'/../../includes/defaults.inc.php');

class Preferences_admin extends Preferences {
	public function __construct($element_form_=array(), $partial=false, $init_=false){
		$this->conf_file = SESSIONMANAGER_CONFFILE_SERIALIZED;
		$this->elements = array();
		$this->elements_by_uid = array();
		$this->initialize();
		try {
			$filecontents = $this->getConfFileContents();
			if (is_array($filecontents)) {
				$this->mergeWithConfFile($filecontents);
			}
		}
		catch (Exception $e) {
		}
		$this->mergeWithConfFile($element_form_);
		$sql_conf = $this->get('general', 'sql');
		$sql = SQL::newInstance($sql_conf);
		if ($sql->CheckLink(false, false)) {
			$this->load_from_db();
		}
	}

	public function deleteConfFile() {
		@unlink($this->conf_file);
	}
	public function backup(){
		init_db($this);
		$this->deleteConfFile();
		$filecontents = array();
		$db_content = array();
		
		foreach($this->elements_by_uid as $k => $element) {
			if (self::keys_match_patterns($k, self::$must_read_from_conf)) {
				$filecontents[$k] = $element->content;
			}
			else {
				$db_content[$k] = $element->content;
			}
		}
		
		$ret = file_put_contents($this->conf_file, json_serialize($filecontents), LOCK_EX);
		if ($ret === FALSE) {
			die_error('Unable to save settings file '.$this->conf_file, __FILE__, __LINE__);
		}
		
		$sql_conf = $this->get('general', 'sql');
		$sql = SQL::newInstance($sql_conf);
		if ($sql->CheckLink(false, false)) {
			$ret = Abstract_Preferences::save_general($db_content);
		}
		
		return $ret;
	}

	public function isValid() {
		Logger::debug('main', 'PREFERENCESADMIN::isValid');

		if (!function_exists('curl_init'))
			return _('Please install CURL support for PHP');

		$sql_conf = $this->get('general', 'sql');
		if (!is_array($sql_conf)) {
			Logger::error('main', 'PREFERENCESADMIN::isValid db conf failed');
			return _('SQL configuration not valid(2)');
		}
		$sql2 = SQL::newInstance($sql_conf);
		$db_ok = $sql2->CheckLink(false);
		if ( $db_ok === false) {
			Logger::error('main', 'PREFERENCESADMIN::isValid db link failed');
			return _('SQL configuration not valid');
		}
		// now we can initialize the system (sql DB ...)
		$ret = init_db($this);
		if ($ret !== true) {
			Logger::error('main', 'init_db failed');
			return _('Initialization failed');
		}
		
		$modules_ok = true;
		$modules_enable = $this->get('general', 'module_enable');
		foreach ($modules_enable as $module_name) {
			if (! is_null($this->get($module_name,'enable'))) {
				$enable = $this->get($module_name,'enable');
				if (is_string($enable)) {
					$mod_name = $module_name.'_'.$enable;
					$ret_eval = call_user_func(array($mod_name, 'prefsIsValid'), $this);
					if ($ret_eval !== true) {
						Logger::error('main', 'prefs is not valid for module \''.$mod_name.'\'');
						$modules_ok = false;
						return _('prefs is not valid for module').' ('.$mod_name.')'; // TODO
					}
				}
				else if (is_array($enable)) {
					foreach ($enable as $sub_module) {
						$mod_name = $module_name.'_'.$sub_module;
						$ret_eval = call_user_func(array($mod_name, 'prefsIsValid'), $this);
						if ($ret_eval !== true) {
							Logger::error('main', 'prefs is not valid for module \''.$mod_name.'\'');
							$modules_ok = false;
							return _('prefs is not valid for module').' ('.$mod_name.')'; // TODO
						}
					}
				}
			}
			else {
				Logger::info('main', 'preferences::isvalid module \''.$module_name.'\' not enable');
			}
		}

		if ( $modules_ok === false) {
			Logger::error('main', 'PREFERENCESADMIN::isValid modules false');
			return _('Modules configuration not valid');
		}
		
		return true;
	}

	public function set($key_, $container_, $value_) {
		$ele = &$this->elements[$key_][$container_];
		if (is_object($ele)) {
			$ele->content = $value_;
		}
		else if (is_array($ele) && is_array($value_)) {
			foreach ($value_ as $k => $e) {
				if (array_key_exists($k, $this->elements[$key_][$container_])) {
					$ele = &$this->elements[$key_][$container_][$k];
					$ele->content = $e;
				}
			}
		}
	}
}
