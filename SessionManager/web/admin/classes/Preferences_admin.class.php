<?php
/**
 * Copyright (C) 2008-2011 Ulteo SAS
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
require_once(dirname(__FILE__).'/../includes/core.inc.php');
require_once(dirname(__FILE__).'/../../includes/defaults.inc.php');

class Preferences_admin extends Preferences {
	public function __construct($element_form_=array(), $partial=false, $init_=false){
		$this->conf_file = SESSIONMANAGER_CONFFILE_SERIALIZED;
		$this->elements = array();
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
	}

	public function deleteConfFile() {
		@unlink($this->conf_file);
	}
	public function backup(){
		init_db($this);
		$this->deleteConfFile();
		$filecontents = array();
		foreach($this->elements as $key1 => $value1) {
			if (is_array($this->elements[$key1])) {
				foreach($value1 as $key2 => $value2) {
					if (is_array($value2)) {
						foreach($value2 as $key3 => $value3) {
							if (is_array($value3)) {
								foreach($value3 as $key4 => $value4) {
									$buf = $this->elements[$key1][$key2][$key3][$key4];
									if (!isset($filecontents[$key1][$key2][$key3]))
										$filecontents[$key1][$key2][$key3] = array();
									$filecontents[$key1][$key2][$key3][$key4] = $buf->content;
								}
							}
							else {
								$buf = $this->elements[$key1][$key2][$key3];
								if (!isset($filecontents[$key1][$key2]))
									$filecontents[$key1][$key2] = array();
								$filecontents[$key1][$key2][$key3] = $buf->content;
							}
						}
					}
					else {
						$buf = $this->elements[$key1][$key2];
						if (!isset($filecontents[$key1]))
							$filecontents[$key1] = array();
						$filecontents[$key1][$key2] = $buf->content;
					}
				}
			}
			else {
				$buf = $this->elements[$key1];
				$filecontents[$key1] = $buf->content;
			}
		}
		return file_put_contents($this->conf_file,serialize($filecontents), LOCK_EX);
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

	public function addPrettyName($key_,$prettyName_) {
		$this->prettyName[$key_] = $prettyName_;
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
