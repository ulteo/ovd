<?php
/**
 * Copyright (C) 2008,2009 Ulteo SAS
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
	public function __construct($element_form_=array(), $partial=false){
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
		if ($partial == true) {
			$this->prefs = array();
			$this->constructFromArray();
			$this->prefs = array_merge2($this->prefs ,$element_form_);
		}
		else {
			$this->constructFromArray();
		}
	}

	public function initialize(){
		Logger::debug('admin','ADMIN_PREFERENCES::initialize');
		$this->elements = array();

		$this->addPrettyName('general',_('General configuration'));
		$c = new ConfigElement('main_title', _('Heading title'), _('You can customize the heading title here.'), _('You can customize the heading title here.'), DEFAULT_PAGE_TITLE, NULL, ConfigElement::$INPUT);
		$this->add($c,'general');

		$c = new ConfigElement('logo_url',_('Logo URL'),_('You can customize the logo by entering a new path or replacing the corresponding image. Use a 90 pixels high image in png or jpeg format. Example: media/image/header.png'),_('You can customize the logo by entering a new path or replacing the corresponding image. Use a 90 pixels high image in png or jpeg format. Example: media/image/header.png'),DEFAULT_LOGO_URL ,NULL,ConfigElement::$INPUT);
		$this->add($c,'general');

		$c = new ConfigElement('log_flags', _('Debug options list'), _('Select debug options you want to enable.'), _('Select debug options you want to enable.'), array('info','warning','error','critical'),array('debug' => _('debug'),'info' => _('info'), 'warning' => _('warning'),'error' => _('error'),'critical' => _('critical')), ConfigElement::$MULTISELECT);
		$this->add($c,'general');

// 		$c = new ConfigElement('locale','locale','locale_des','fr_FR.UTF8@euro',NULL,ConfigElement::$INPUT);
// 		$this->add('general',$c);
//
// 		$c = new ConfigElement('start_app','start_app','start_app_des','',NULL,ConfigElement::$INPUT);
// 		$this->add('general',$c);

		$c = new ConfigElement('user_authenticate_sso', _('Use SSO for user authentication'), _('Use SSO for user authentication'), _('Use SSO for user authentication'), 0, array(0=>_('no'),1=>_('yes')), ConfigElement::$SELECT);

		$this->add($c,'general');
		$c = new ConfigElement('user_authenticate_trust', _('SERVER variable for SSO'), _('SERVER variable for SSO'), _('SERVER variable for SSO'), 'REMOTE_USER', NULL, ConfigElement::$INPUT);
		$this->add($c,'general');

		$user_groups = array();
		$ugs = get_all_usergroups();
        if ($ugs){
    		foreach ($ugs as $ug) {
    			$user_groups[$ug->id] = $ug->name;
	    	}
		    $user_groups[-1] = 'None';
			ksort($user_groups);
		    $c = new ConfigElement('user_default_group', _('Default user group'), _('Default user group'), _('Default user group'), -1, $user_groups , ConfigElement::$SELECT);
		    $this->add($c,'general');
        }

		$this->addPrettyName('mysql',_('MySQL configuration'));
		$c = new ConfigElement('host', _('Database host address'), _('The address of your database host. This database contains adminstration console data. Example: localhost or db.mycorporate.com.'), _('The address of your database host. This database contains adminstrations console data. Example: localhost or db.mycorporate.com.'),'' ,NULL,ConfigElement::$INPUT);
		$this->add($c,'general','mysql');
		$c = new ConfigElement('user', _('Database username'), _('The username that must be used to access the database.'), _('The user name that must be used to access the database.'),'',NULL,ConfigElement::$INPUT);
		$this->add($c,'general','mysql');
		$c = new ConfigElement('password',_('Database password'), _('The user password that must be used to access the database.'), _('The user password that must be used to access the database.'),'',NULL,ConfigElement::$PASSWORD);
		$this->add($c,'general','mysql');
		$c = new ConfigElement('database', _('Database name'), _('The name of the database.'), _('The name of the database.'), '',NULL,ConfigElement::$INPUT);
		$this->add($c,'general','mysql');
		$c = new ConfigElement('prefix', _('Table prefix'), _('The table prefix for the database.'), _('The table prefix for the database.'), 'ulteo_','ulteo_',ConfigElement::$INPUT);
		$this->add($c,'general','mysql');

		$this->addPrettyName('mails_settings',_('Mails settings'));
		$c = new ConfigElement('send_type', _('Mail server type'), _('Mail server type'), _('Mail server type'),'mail',array('mail'=>_('Local'),'smtp'=>_('SMTP server')),ConfigElement::$SELECT);
		$this->add($c,'general','mails_settings');
		$c = new ConfigElement('send_from', _('From'), _('From'), _('From'),'no-reply@'.$_SERVER['SERVER_NAME'],NULL,ConfigElement::$INPUT);
		$this->add($c,'general','mails_settings');
		$c = new ConfigElement('send_host', _('Host'), _('Host'), _('Host'),'',NULL,ConfigElement::$INPUT);
		$this->add($c,'general','mails_settings');
		$c = new ConfigElement('send_auth', _('Authentication'), _('Authentication'), _('Authentication'),1,array(0=>_('no'),1=>_('yes')),ConfigElement::$SELECT);
		$this->add($c,'general','mails_settings');
		$c = new ConfigElement('send_username', _('SMTP username'), _('SMTP username'), _('SMTP username'),'',NULL,ConfigElement::$INPUT);
		$this->add($c,'general','mails_settings');
		$c = new ConfigElement('send_password', _('SMTP password'), _('SMTP password'), _('SMTP password'),'',NULL,ConfigElement::$PASSWORD);
		$this->add($c,'general','mails_settings');

		$this->addPrettyName('application_server_settings',_('Application Server settings'));
		$c = new ConfigElement('authorized_fqdn', _('Authorized network domain'), _('Enter the list of authorized network domains that can self-declare an Application Server to the administration console. Example: *.office.mycorporation.com'), _('Enter the list of authorized network domains that can self-declare an Application Server to the administration console. Example: *.office.mycorporation.com'), array('*.ulteo.com'), NULL, ConfigElement::$LIST);
		$this->add($c,'general', 'application_server_settings');
		//fqdn_private_address : array('dns' => ip);
		$c = new ConfigElement('fqdn_private_address', _('Name/IP Address association (name <-> ip)'), _('Enter a private addresses you wish to associate to a specific IP in case of issue with the DNS configuration or to override a reverse address result. Example: pong.office.ulteo.com (field 1) 192.168.0.113 (field 2)'), _('Enter a private addresses you wish to associate to a specific IP in case of issue with the DNS configuration or to override a reverse address result. Example: pong.office.ulteo.com (field 1) 192.168.0.113 (field 2)'), array(), NULL, ConfigElement::$DICTIONARY);
		$this->add($c,'general', 'application_server_settings');
		$c = new ConfigElement('disable_fqdn_check', _('Disable reverse FQDN checking'), _('Enable this option if you don\'t want to check that the result of the reverse FQDN address fits the one that was registered.'), _('Enable this option if you don\'t want to check that the result of the reverse FQDN address fits the one that was registered.'), 0, array(0=>_('no'),1=>_('yes')), ConfigElement::$SELECT);
		$this->add($c,'general', 'application_server_settings');
		$c = new ConfigElement('action_when_as_not_ready', _('Action when an AS status is not ready anymore'), _('Action when an AS status is not ready anymore'), _('Action when an AS status is not ready anymore'), 1, array(0=>_('Do nothing'),1=>_('Switch to maintenance')), ConfigElement::$SELECT);
		$this->add($c,'general', 'application_server_settings');

		$decisionCriterion = get_classes_startwith('DecisionCriterion_');
		$content_load_balancing = array();
		foreach ($decisionCriterion as $criterion_class_name) {
				$c = new $criterion_class_name(NULL); // ugly
				$content_load_balancing[substr($criterion_class_name, strlen('DecisionCriterion_'))] = $c->default_value();
		}
		$c = new ConfigElement('load_balancing', _('load_balancing'), _('load_balancing'), _('load_balancing'), $content_load_balancing, NULL, ConfigElement::$SLIDERS);
		$this->add($c,'general', 'application_server_settings');

		$this->addPrettyName('session_settings_defaults',_('Sessions settings'));
		$c = new ConfigElement('language', _('Default language for session'), _('Default language for session'), _('Default language for session'), 'en_GB.UTF-8', array('en_GB.UTF-8'=>'English','fr_FR.UTF-8'=>'Français'), ConfigElement::$SELECT);
		$this->add($c,'general','session_settings_defaults');
		$c = new ConfigElement('quality', _('Default quality for session'), _('Default quality for session'), _('Default quality for session'), 9, array(2=>_('Lowest'),5=>_('Medium'),8=>_('High'),9=>_('Highest')), ConfigElement::$SELECT);
		$this->add($c,'general','session_settings_defaults');
		$c = new ConfigElement('timeout', _('Default timeout for session'), _('Default timeout for session'), _('Default timeout for session'), 86400, array(60 => _('1 minute'),120 => _('2 minutes'),300 => _('5 minutes'),600 => _('10 minutes'),900 => _('15 minutes'),1800 => _('30 minutes'),3600 => _('1 hour'),7200 => _('2 hours'),18000 => _('5 hours'),43200 => _('12 hours'),86400 => _('1 day'),172800 => _('2 days'),604800 => _('1 week'),2764800 => _('1 month'),-1 => _('Never')), ConfigElement::$SELECT);
		$this->add($c,'general','session_settings_defaults');
		$c = new ConfigElement('session_timeout_msg', _('Session timeout message'), _('Session timeout message'), _('Session timeout message'), "Dear user,\n\nYour session is going to end in 3 minutes.\n\nPLEASE SAVE ALL YOUR DATA NOW !", NULL, ConfigElement::$TEXTAREA);
		$this->add($c,'general','session_settings_defaults');
		$c = new ConfigElement('persistent', _('Sessions are persistent'), _('Sessions are persistent'), _('Sessions are persistent'), 0, array(0=>_('no'),1=>_('yes')), ConfigElement::$SELECT);
		$this->add($c,'general','session_settings_defaults');
		$c = new ConfigElement('desktop_icons', _('Show icons on desktop'), _('Show icons on desktop'), _('Show icons on desktop'), 1, array(0=>_('no'),1=>_('yes')), ConfigElement::$SELECT);
		$this->add($c,'general','session_settings_defaults');

		$c = new ConfigElement('launch_without_apps', _('User can launch a session with no application'), _('User can launch a session with no application'), _('User can launch a session with no application'), 0, array(0=>_('no'),1=>_('yes')), ConfigElement::$SELECT);
		$this->add($c,'general','session_settings_defaults');

		# Sessions can be shared ? yes/no
		$c = new ConfigElement('shareable', _('Session owner can share his session'), _('Session owner can share his session'), _('Session owner can share his session'), 1, array(0=>_('no'),1=>_('yes')), ConfigElement::$SELECT);
		$this->add($c,'general','session_settings_defaults');

		$c = new ConfigElement('advanced_settings_startsession', _('Forceable paramaters by users'), _('Choose Advanced Settings options you want to make available to users before they launch a session.'), _('Choose Advanced Settings options you want to make available to users before they launch a session.'), array('testapplet'),array('language' => _('language'), 'server' => _('server'), 'size' => _('size'), 'quality' => _('quality'), 'timeout' => _('timeout'), 'application' => _('application'), 'persistent' => _('persistent'),
			'shareable' => _('shareable'),
			'desktop_icons' => _('desktop icons'), 'debug' => _('debug')),ConfigElement::$MULTISELECT);
		$this->add($c,'general','session_settings_defaults');

		$this->addPrettyName('web_interface_settings',_('Web interface settings'));
		$c = new ConfigElement('show_list_users', _('Display users list'), _('Display the list of users from the corporate directory in the login box. If the list is not displayed, the user must enter his login name.'), _('Display the list of users from the corporate directory in the login box. If the list is not displayed, the user must enter his login name.'),0,array(0=>_('no'),1=>_('yes')),ConfigElement::$SELECT);
		$this->add($c,'general','web_interface_settings');
		$c = new ConfigElement('testapplet', _('SSH/ping applet test'), _('SSH/ping applet test'), _('SSH/ping applet test'), 1,array(0=>_('no'),1=>_('yes')), ConfigElement::$SELECT);
		$this->add($c,'general','web_interface_settings');

		$c = new ConfigElement('user_authenticate_sso', _('Use SSO for user authentication'), _('Use SSO for user authentication'), _('Use SSO for user authentication'), 0, array(0=>_('no'),1=>_('yes')), ConfigElement::$SELECT);

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
		$p2 = $plugs->getAvailablePlugins();
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

		$c = new ConfigElement('plugin_enable', _('Modules activation'), _('Choose the modules you want to enable.'), _('Choose the modules you want to enable.'), array(), $plugins_prettyname, ConfigElement::$MULTISELECT);
		$this->addPrettyName('plugins',_('Plugins configuration'));
		$this->add($c,'plugins');
		unset($p2['plugins']);

		foreach ($p2 as $key1 => $value1){
			$plugins_prettyname = array();
			$c = new ConfigElement($key1, $key1, 'plugins '.$key1, 'plugins '.$key1, array(), $plugins_prettyname, ConfigElement::$SELECT);
			foreach ($value1 as $plugin_name => $plu6) {
				$plugin_prettyname = eval('return '.$key1.'_'.$plugin_name.'::prettyName();');
				if (is_null($plugin_prettyname))
					$plugin_prettyname = $plugin_name;
				$plugins_prettyname[$plugin_name] = $plugin_prettyname;

				$isdefault1 = eval('return '.$key1.'_'.$plugin_name.'::isDefault();');
				if ($isdefault1 === true)
					$c = new ConfigElement($key1, $key1, 'plugins '.$key1,'plugins '.$key1, $plugin_name, $plugins_prettyname, ConfigElement::$SELECT);
			}
			$this->add($c,'plugins');
		}
	}

	public function getPrefsModule(){
		$available_module = $this->getAvailableModule();
		// we remove all diseable modules
		foreach ($available_module as $mod2 => $sub_mod2){
			foreach ($sub_mod2 as $sub_mod_name2 => $sub_mod_pretty2){
				$enable1 = 'return '.'admin_'.$mod2.'_'.$sub_mod_name2.'::enable();';
				$enable =  eval($enable1);
				if ($enable !== true)
					unset ($available_module[$mod2][$sub_mod_name2]);
			}

		}
		$modules_prettyname = array();
		foreach ($available_module as $module_name => $sub_module)
			$modules_prettyname[$module_name] = $module_name;

		$c = new ConfigElement('module_enable',_('Modules options'), _('Choose the modules you want to enable.'), _('Choose the modules you want to enable.'), array('UserDB', 'ApplicationDB', 'UserGroupDB'), $modules_prettyname, ConfigElement::$MULTISELECT);
		$this->add($c,'general');

		foreach ($available_module as $mod => $sub_mod){
			$c = new ConfigElement('enable', $mod, $mod, $mod, NULL, $sub_mod, ConfigElement::$SELECT);
			foreach ($sub_mod as $k4 => $v4) {
				$default2 = 'return '.$mod.'_'.$k4.'::isDefault();';
				$default1 =  eval($default2);
				if ($default1 === true)
					$c = new ConfigElement('enable', $mod, $mod, $mod, $k4, $sub_mod, ConfigElement::$SELECT);
			}

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

	protected function getAvailableModule(){
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
				if ($pathinfo['extension'] == 'php') {
					if (!isset($ret['module'])){
						$ret['module'] = array();
					}
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
	
	public function set($value_, $key_, $contener_) {
		if (!isset($this->prefs[$value_])) {
			$this->prefs[$value_] = array();
		}
		if (is_string($key_))
			$this->prefs[$value_][$key_] = $contener_;
		else
			Logger::error('admin','PREFERENCESADMIN::set $key_ is not a string');
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
								if (is_array($prefs[$key1][$key2][$value2->id]) ) {
									$buf =& $this->elements[$key1][$key2];
									$buf->content = $prefs[$key1][$key2][$value2->id];
								}
								else if (is_string($prefs[$key1][$key2])) {
									$buf =& $this->elements[$key1][$key2];
									$buf->content = $prefs[$key1][$key2];
								}
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
