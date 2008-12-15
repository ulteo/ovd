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
require_once(dirname(__FILE__).'/../includes/core.inc.php');
require_once(dirname(__FILE__).'/../../includes/defaults.inc.php');

class Preferences_admin extends Preferences {
	public function __construct($element_form_=array()){
		Logger::debug('admin','ADMIN_PREFERENCES::construct');
		$this->conf_file = SESSIONMANAGER_CONFFILE_SERIALIZED;
		$this->prettyName = array();
		$this->initialize();
		if (is_writable2($this->conf_file)) {
			if (file_exists($this->conf_file)) {
				if ( $element_form_ == array())
					$this->constructFromFile();
				else
					$this->prefs = $element_form_;
			}
			else
				$this->prefs = $element_form_;
		}
		$this->constructFromArray();

	}



	public function initialize(){
		Logger::debug('admin','ADMIN_PREFERENCES::initialize');
		$this->elements = array();


		$this->addPrettyName('general',_('General configuration'));
		$c = new config_element('main_title', _('Heading title'), _('You can customize the Heading/title here.'), _('You can customize the Heading/title here.'), DEFAULT_PAGE_TITLE, NULL, 1);
		$this->add($c,'general');

		$c = new config_element('logo_url',_('Logo URL'),_('You can customize the logo by entering a new path or replacing the corresponding image. Use a 90 pixels high image in png or jpeg format. Example: media/image/header.png'),_('You can customize the logo by entering a new path or replacing the corresponding image. Use a 90 pixels high image in png or jpeg format. Example: media/image/header.png'),DEFAULT_LOGO_URL ,NULL,1);
		$this->add($c,'general');

		$c = new config_element('authorized_fqdn', _('Authorized network domain'), _('Enter the list of authorized network domains that can self-declare an Application Server to the administration console. Example: *.office.mycorporation.com'), _('Enter the list of authorized network domains that can self-declare an Application Server to the administration console. Example: *.office.mycorporation.com'), array('*.office.ulteo.com','*.ulteo.com'), NULL, 5);
		$this->add($c,'general');

		//fqdn_private_address : array('dns' => ip);
		$c = new config_element('fqdn_private_address', _('Name/IP Address association (name <-> ip)'), _('Enter a private addresses you wish to associate to a specific IP in case of issue with the DNS configuration or to override a reverse address result. Example: pong.office.ulteo.com (field 1) 192.168.0.113 (field 2)'), _('Enter a private addresses you wish to associate to a specific IP in case of issue with the DNS configuration or to override a reverse address result. Example: pong.office.ulteo.com (field 1) 192.168.0.113 (field 2)'), array(), NULL, 4);
		$this->add($c,'general');

		$c = new config_element('disable_fqdn_check', _('Disable reverse FQDN checking'), _('Enable this option if you don\'t want to check that the result of the reverse FQDN address fits the one that was registered.'), _('Enable this option if you don\'t want to check that the result of the reverse FQDN address fits the one that was registered.'), 0, array(0=>_('no'),1=>_('yes')), 2);
		$this->add($c,'general');

		$c = new config_element('log_flags', _('Debug options list'), _('Select debug options you want to enable.'), _('Select debug options you want to enable.'), array('info','warning','error','critical'),array('debug' => _('debug'),'info' => _('info'), 'warning' => _('warning'),'error' => _('error'),'critical' => _('critical')), 3);
		$this->add($c,'general');

// 		$c = new config_element('locale','locale','locale_des','fr_FR.UTF8@euro',NULL,1);
// 		$this->add('general',$c);
//
// 		$c = new config_element('start_app','start_app','start_app_des','',NULL,1);
// 		$this->add('general',$c);

		$c = new config_element('show_list_users', _('Display user list'), _('Display the list of users from the corporate directory in the login box. If the list is not displayed, the user must enter his login name.'), _('Display the list of users from the corporate directory in the login box. If the list is not displayed, the user must enter his login name.'),0,array(0=>_('no'),1=>_('yes')),2);
		$this->add($c,'general');

		$c = new config_element('advanced_settings_startsession', _('Advanced settings options'), _('Choose Advanced Settings options you want to make available to users before they launch a session.'), _('Choose Advanced Settings options you want to make available to users before they launch a session.'), array('testapplet'),array('language' => _('language'), 'server' => _('server'), 'size' => _('size'), 'quality' => _('quality'), 'timeout' => _('timeout'), 'application' => _('application'), 'persistent' => _('persistent'), 'debug' => _('debug'), 'testapplet' => _('ssh/ping applet test')),3);
		$this->add($c,'general');

		$c = new config_element('user_authenticate_sso', _('Use SSO for user authentification'), _('Use SSO for user authentification'), _('Use SSO for user authentification'), 0, array(0=>_('no'),1=>_('yes')), 2);

		$this->add($c,'general');
		$c = new config_element('user_authenticate_trust', _('SERVER variable for SSO'), _('SERVER variable for SSO'), _('SERVER variable for SSO'), 'REMOTE_USER', NULL, 1);
		$this->add($c,'general');

		$c = new config_element('persistent_session', _('Use persistent session'), _('Use persistent session'), _('Use persistent session'), 0, array(0=>_('no'),1=>_('yes')), 2);
		$this->add($c,'general');

		$c = new config_element('session_timeout_msg', _('Session timeout message'), _('Session timeout message'), _('Session timeout message'), 'Dear user,\n\nYour session is going to end in 3 minutes.\n\nPLEASE SAVE ALL YOUR DATA NOW !', NULL, 1);
		$this->add($c,'general');

		$c = new config_element('action_when_as_not_ready', _('Action when an AS status is not ready anymore'), _('Action when an AS status is not ready anymore'), _('Action when an AS status is not ready anymore'), 1, array(0=>_('Do nothing'),1=>_('Switch to maintenance')), 2);
		$this->add($c,'general');

		$this->addPrettyName('mysql',_('MySQL configuration'));
		$c = new config_element('host', _('Database host address'), _('The address of your database host. This database contains adminstrations console data. Example: localhost or db.mycorporate.com.'), _('The address of your database host. This database contains adminstrations console data. Example: localhost or db.mycorporate.com.'),'' ,NULL,1);
		$this->add($c,'general','mysql');
		$c = new config_element('user', _('Database username'), _('The username that must be used to access the database.'), _('The user name that must be used to access the database.'),'',NULL,1);
		$this->add($c,'general','mysql');
		$c = new config_element('password',_('Database password'), _('The user password that must be used to access the database.'), _('The user password that must be used to access the database.'),'',NULL,1);
		$this->add($c,'general','mysql');
		$c = new config_element('database', _('Database name'), _('The name of the database.'), _('The name of the database.'), '',NULL,1);
		$this->add($c,'general','mysql');
		$c = new config_element('prefix', _('Table prefix'), _('The table prefix for the database.'), _('The table prefix for the database.'), 'ulteo_','ulteo_',1);
		$this->add($c,'general','mysql');

		$this->getPrefsModule();
		$this->getPrefsPlugins();
	}

	public function backup(){
		@unlink($this->conf_file);
		return file_put_contents($this->conf_file,serialize($this->prefs));
	}

	public function add($value_,$key_,$contener_=NULL){
		if (!is_null($contener_)) {
			if (!isset($this->elements[$key_])) {
				$this->elements[$key_] = array();
			}
			else {
				if (is_object($this->elements[$key_])) {
					$val = $this->elements[$key_];
					$this->elements[$key_] = array();
					$this->elements[$key_][$val->id]= $val;
				}
			}
			if (!isset($this->elements[$key_][$contener_])) {
				$this->elements[$key_][$contener_] = array();
			}
			// already something on [$key_][$contener_]
			if (is_array($this->elements[$key_][$contener_]))
				$this->elements[$key_][$contener_][$value_->id]= $value_;
			else {
				$val = $this->elements[$key_][$contener_];
				$this->elements[$key_][$contener_] = array();
				$this->elements[$key_][$contener_][$val->id]= $val;
				$this->elements[$key_][$contener_][$value_->id]= $value_;
			}
		}
		else {
			if (isset($this->elements[$key_])) {
				// already something on [$key_]
				if (is_array($this->elements[$key_]))
					$this->elements[$key_][$value_->id]= $value_;
				else {
					$val = $this->elements[$key_];
					$this->elements[$key_] = array();
					$this->elements[$key_][$val->id]= $val;
					$this->elements[$key_][$value_->id]= $value_;
				}
			}
			else {
				$this->elements[$key_] = $value_;
			}
		}
	}

	public function getPrefsPlugins(){
		$plugs = new Plugins();
		$p2 = $plugs->getAvalaiblePlugins();
		// we remove all diseable Plugins
		foreach ($p2 as $plugin_dir2 => $plu2) {
			foreach ($plu2 as $plugin_name2 => $plugin_name2_value){
				if ($plugin_dir2 == 'plugins')
					$plugin_enable2 = eval('return Plugin_'.$plugin_name2.'::enable();');
				else
					$plugin_enable2 = eval('return '.$plugin_dir2.'_'.$plugin_name2.'::enable();');

				if ($plugin_enable2 !== true)
					unset($p2[$plugin_dir2][$plugin_name2]);
			}
		}
		$plugins_prettyname = array();
		foreach ($p2['plugins'] as $plugin_name => $plu6) {
			$plugin_prettyname = eval('return '.'Plugin_'.$plugin_name.'::prettyName();');
			if (is_null($plugin_prettyname))
				$plugin_prettyname = $plugin_name;
			$plugins_prettyname[$plugin_name] = $plugin_prettyname;
		}

		$c = new config_element('plugin_enable', _('Modules activation'), _('Choose the modules you want to enable.'), _('Choose the modules you want to enable.'), array(), $plugins_prettyname, 3);
		$this->addPrettyName('plugins',_('Plugins configuration'));
		$this->add($c,'plugins');
		unset($p2['plugins']);

		foreach ($p2 as $key1 => $value1){
			$plugins_prettyname = array();
			foreach ($value1 as $plugin_name => $plu6) {
				$plugin_prettyname = eval('return '.$key1.'_'.$plugin_name.'::prettyName();');
				if (is_null($plugin_prettyname))
					$plugin_prettyname = $plugin_name;
				$plugins_prettyname[$plugin_name] = $plugin_prettyname;
			}
			$c = new config_element($key1,$key1,'plugins '.$key1,'plugins '.$key1,array(),$plugins_prettyname,2);
			$this->add($c,'plugins');
		}
	}

	public function getPrefsModule(){
		$avalaible_module = $this->getAvalaibleModule();
		// we remove all diseable modules
		foreach ($avalaible_module as $mod2 => $sub_mod2){
			foreach ($sub_mod2 as $sub_mod_name2 => $sub_mod_pretty2){
				$enable1 = 'return '.'admin_'.$mod2.'_'.$sub_mod_name2.'::enable();';
				$enable =  eval($enable1);
				if ($enable !== true)
					unset ($avalaible_module[$mod2][$sub_mod_name2]);
			}

		}
		$modules_prettyname = array();
		foreach ($avalaible_module as $module_name => $sub_module)
			$modules_prettyname[$module_name] = $module_name;

		$c = new config_element('module_enable',_('Modules options'), _('Choose the modules you want to enable.'), _('Choose the modules you want to enable.'), array('UserDB','ApplicationDB'), $modules_prettyname, 3);
		$this->add($c,'general');
		foreach ($avalaible_module as $mod => $sub_mod){
			if (in_array('sql',array_keys($sub_mod)))
				$c = new config_element('enable',$mod,$mod,$mod,'sql',$sub_mod,2);
			else
				$c = new config_element('enable',$mod,$mod,$mod,NULL,$sub_mod,2);
			//dirty hack (if this->elements[mod] will be empty)
			if (!isset($this->elements[$mod]))
				$this->elements[$mod] = array();
			$this->add($c,$mod);
			$this->addPrettyName($mod,'Module '.$mod);

			foreach ($sub_mod as $sub_mod_name => $sub_mod_pretty){
				$module_name= $mod.'_'.$sub_mod_name;
				$mod_conf = 'return '.$module_name.'::configuration();';
				$list_conf = eval($mod_conf);
				if (is_array($list_conf)) {
					foreach ($list_conf as $l_conf){
						$this->add($l_conf,$mod,$sub_mod_name);
					}
				}
			}
		}
	}

	protected function getAvalaibleModule(){
		$ret = array();
		$files = glob(MODULES_DIR.'/*');
		foreach ($files as $path){
			if (is_dir($path)) {
				$files2 = glob($path.'/*');
				foreach ($files2 as $file2){
					if (is_file($file2)) {
						$pathinfo = pathinfo_filename($file2);
						if (!isset($ret[basename($pathinfo["dirname"])])){
							$ret[basename($pathinfo["dirname"])] = array();
						}
						if ($pathinfo['extension'] == 'php') {
							$pretty_name = eval('return '.basename($pathinfo["dirname"]).'_'.$pathinfo["filename"].'::prettyName();');
							if ( is_null($pretty_name))
								$pretty_name = $pathinfo["filename"];
							$ret[basename($pathinfo["dirname"])][$pathinfo["filename"]] = $pretty_name;
						}
					}
				}
			}
			if (is_file($path)) {
				$pathinfo = pathinfo_filename($path);
				if (!isset($ret['module'])){
					$ret['module'] = array();
				}
				if ($pathinfo['extension'] == 'php') {
					// TODO : prettyname
					$ret['module'][$pathinfo["filename"]] = $pathinfo["filename"];
				}
			}
		}
		return $ret;
	}

	public function isValid() {
		Logger::debug('admin','PREFERENCESADMIN::isValid');
		$mysql_conf = $this->get('general', 'mysql');
		if (!is_array($mysql_conf)) {
			Logger::error('admin','PREFERENCESADMIN::isValid db conf failed');
			return _('SQL configuration not valid(2)');
		}
		$sql2 = MySQL::newInstance($mysql_conf['host'], $mysql_conf['user'], $mysql_conf['password'], $mysql_conf['database']);
		$db_ok = $sql2->CheckLink(false);
		if ( $db_ok === false) {
			Logger::error('admin','PREFERENCESADMIN::isValid db link failed');
			return _('SQL configuration not valid');
		}
		// now we can initialize the system (mysql DB ...)
		$ret = init_db($this);
		if ($ret !== true) {
			Logger::error('admin','init_db failed');
			return _('Initialization failed');
		}

		$plugins_ok = true;
		$plugins_enable = $this->get('plugins', 'plugin_enable');
		foreach ($plugins_enable as $plugin_name) {
			$ret_eval = eval('return Plugin_'.strtolower($plugin_name).'::prefsIsValid($this);');
			if ($ret_eval !== true) {
				Logger::error('admin','prefs is not valid for plugin \''.$plugin_name.'\'');
				$plugins_ok = false;
				return _('prefs is not valid for plugin').' ('.$plugin_name.')'; // TODO
			}
		}
		$plugins_FS = $this->get('plugins', 'FS');
		// for now we can use one FS at the same time
		if (!is_null($plugins_FS)) {
			//foreach ($plugins_FS['FS'] as $plugin_name) {
				$ret_eval = eval('return FS_'.strtolower($plugins_FS).'::prefsIsValid($this);');
				if ($ret_eval !== true) {
					Logger::error('admin','prefs is not valid for FS plugin \''.$plugins_FS.'\'');
					$plugins_ok = false;
					return _('prefs is not valid for FS plugin').' ('.$plugins_FS.')'; // TODO
				}
			//}
		}
		if ( $plugins_ok === false) {
			Logger::error('admin','PREFERENCESADMIN::isValid plugins false');
			return _('Plugins configuration not valid');
		}

		$modules_ok = true;
		$modules_enable = $this->get('general', 'module_enable');
		foreach ($modules_enable as $module_name) {
			$mod_name = $module_name.'_'.$this->get($module_name,'enable');
			$ret_eval = eval('return '.$mod_name.'::prefsIsValid($this);');
			if ($ret_eval !== true) {
				Logger::error('admin','prefs is not valid for module \''.$mod_name.'\'');
				$modules_ok = false;
				return _('prefs is not valid for module').' ('.$mod_name.')'; // TODO
			}
		}

		if ( $modules_ok === false) {
			Logger::error('admin','PREFERENCESADMIN::isValid modules false');
			return _('Modules configuration not valid');
		}
		return true;
	}

	public function addPrettyName($key_,$prettyName_) {
		$this->prettyName[$key_] = $prettyName_;
	}

	protected function constructFromArray(){
		Logger::debug('admin','ADMIN_PREFERENCES::constructFromArray');
		$prefs =& $this->prefs;
		$init = ($prefs == array());
		foreach ($this->elements as $key1 => $value1) {
			if (is_object($value1)) {
			}
			else if (is_array($value1)){
				foreach ($value1 as $key2 => $value2) {
					if (is_object($value2)) {
						if (isset($prefs[$key1][$key2])) {
							if (isset($prefs[$key1][$key2][$value2->id])) {
								$buf =& $this->elements[$key1][$key2];
								$buf->content = $prefs[$key1][$key2][$value2->id];
							}
						}
						else {
							$buf =& $this->elements[$key1][$key2];
							if (!$init)
								$buf->reset();
							$prefs[$key1][$key2] = $buf->content;
						}
					}
					else if (is_array($value2)){
						foreach ($value2 as $key3 => $value3) {
							if (is_object($value3)) {
								if (isset($prefs[$key1][$key2][$key3])) {
									$buf =& $this->elements[$key1][$key2][$key3];
									$buf->content = $prefs[$key1][$key2][$key3];
								}
								else {
									$buf =& $this->elements[$key1][$key2][$key3];
									if (!$init)
										$buf->reset();
									$prefs[$key1][$key2][$key3] = $buf->content;
								}
							}
						}
					}
				}
			}
		}
	}
}
