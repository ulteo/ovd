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
require_once(dirname(__FILE__).'/../admin/includes/core.inc.php');

class Preferences {
	public $elements;
	protected $conf_file;
	private static $instance;
	public $prettyName;

	public function __construct(){
		$this->conf_file = SESSIONMANAGER_CONFFILE_SERIALIZED;
		$this->elements = array();
		$this->initialize();
		$filecontents = $this->getConfFileContents();
		if (!is_array($filecontents)) {
			Logger::error('main', 'Preferences::construct contents of conf file is not an array');
		}
		else {
			$this->mergeWithConfFile($filecontents);
		}
	}

	public static function hasInstance() {
		return isset(self::$instance);
	}

	public static function getInstance() {
		if (!isset(self::$instance)) {
			try {
				self::$instance = new Preferences();
			} catch (Exception $e) {
				return false;
			}
		}
		return self::$instance;
	}

	public function getKeys(){
		return array_keys($this->elements);
	}

	public function get($container_,$container_sub_,$sub_sub_=NULL){
		if (isset($this->elements[$container_])) {
			if (isset($this->elements[$container_][$container_sub_])) {
				if (is_null($sub_sub_)) {
					$buf = $this->elements[$container_][$container_sub_];
					if (is_array($buf)) {
						$buf2 = array();
						foreach ($buf as $k=> $v) {
							$buf2[$k] = $v->content;
						}
						return $buf2;
					}
					else
						return $buf->content;
				}
				else {
					if (isset($this->elements[$container_][$container_sub_][$sub_sub_])) {
						$buf = $this->elements[$container_][$container_sub_][$sub_sub_];
						return $buf->content;
					}
					else {
						return NULL;
					}
				}
			}
			else {
				return NULL;
			}

		}
		else {
			//Logger::error('main','Preferences::get \''.$container_.'\' not found');
			return NULL;
		}
	}

	protected function getConfFileContents(){
		if (!is_readable($this->conf_file)) {
			throw new Exception('Unable to read config file');
			return array();
		}
		return unserialize(file_get_contents($this->conf_file));
	}

	public function getPrettyName($key_) {
		if (isset($this->prettyName[$key_]))
			return $this->prettyName[$key_];
		else {
			return $key_;
		}
	}

	public function mergeWithConfFile($filecontents) {
		if (is_array($filecontents)) {
			foreach($filecontents as $key1 => $value1) {
				if ((isset($this->elements[$key1])) && is_object($this->elements[$key1])) {
					$buf = &$this->elements[$key1];
					$buf->content = $filecontents[$key1];
				}
				else if (is_array($filecontents[$key1])) {
					foreach($value1 as $key2 => $value2) {
						if ((isset($this->elements[$key1][$key2])) && is_object($this->elements[$key1][$key2])) {
							$buf = &$this->elements[$key1][$key2];
							$buf->content = $filecontents[$key1][$key2];
						}
						else if (is_array($value2)) {
							foreach($value2 as $key3 => $value3) {
								if ((isset($this->elements[$key1][$key2][$key3])) && is_object($this->elements[$key1][$key2][$key3])) {
									$buf = &$this->elements[$key1][$key2][$key3];
									$buf->content = $filecontents[$key1][$key2][$key3];
								}
								else if (is_array($value3)) {
									foreach($value3 as $key4 => $value4) {
										if ((isset($this->elements[$key1][$key2][$key3][$key4])) && is_object($this->elements[$key1][$key2][$key3][$key4])) {
											$buf = &$this->elements[$key1][$key2][$key3][$key4];
											$buf->content = $filecontents[$key1][$key2][$key3][$key4];
										}
										else {
											echo 'todo6<br>';
										}
									}
								}
								else {
									echo 'todo5<br>';
								}
							}
						}
					}
				}
				else {
					echo 'todo4<br>';
				}
			}
		}
	}

	public function initialize(){

		$this->addPrettyName('general',_('General configuration'));

		$c = new ConfigElement('admin_language', _('Administration console language'), _('Administration console language'), _('Administration console language'), 'en_GB', array('en_GB'=>'English','fr_FR'=>'Français'), ConfigElement::$SELECT);
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

		if (Preferences::hasInstance()) {
			$user_groups = array();
			$ugs = get_all_usergroups($this);
			if ($ugs){
				foreach ($ugs as $ug) {
					$user_groups[$ug->id] = $ug->name;
				}
				$user_groups[-1] = 'None';
				ksort($user_groups);
				$c = new ConfigElement('user_default_group', _('Default user group'), _('Default user group'), _('Default user group'), -1, $user_groups , ConfigElement::$SELECT);
				$this->add($c,'general');
			}
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

		$this->addPrettyName('mails_settings',_('Email settings'));
		$c = new ConfigElement('send_type', _('Mail server type'), _('Mail server type'), _('Mail server type'),'mail',array('mail'=>_('Local'),'smtp'=>_('SMTP server')),ConfigElement::$SELECT);
		$this->add($c,'general','mails_settings');
		$c = new ConfigElement('send_from', _('From'), _('From'), _('From'),'no-reply@'.$_SERVER['SERVER_NAME'],NULL,ConfigElement::$INPUT);
		$this->add($c,'general','mails_settings');
		$c = new ConfigElement('send_host', _('Host'), _('Host'), _('Host'),'',NULL,ConfigElement::$INPUT);
		$this->add($c,'general','mails_settings');
		$c = new ConfigElement('send_auth', _('Authentication'), _('Authentication'), _('Authentication'),0,array(0=>_('no'),1=>_('yes')),ConfigElement::$SELECT);
		$this->add($c,'general','mails_settings');
		$c = new ConfigElement('send_username', _('SMTP username'), _('SMTP username'), _('SMTP username'),'',NULL,ConfigElement::$INPUT);
		$this->add($c,'general','mails_settings');
		$c = new ConfigElement('send_password', _('SMTP password'), _('SMTP password'), _('SMTP password'),'',NULL,ConfigElement::$PASSWORD);
		$this->add($c,'general','mails_settings');

		$this->addPrettyName('application_server_settings',_('Application Server settings'));
		$c = new ConfigElement('authorized_fqdn', _('Authorized machines (FQDN or IP - the use of wildcards (*.) is allowed)'), _('Authorized machines (FQDN or IP - the use of wildcards (*.) is allowed)'), _('Authorized machines (FQDN or IP - the use of wildcards (*.) is allowed)'), array('*'), NULL, ConfigElement::$LIST);
		$this->add($c,'general', 'application_server_settings');
		//fqdn_private_address : array('dns' => ip);
		$c = new ConfigElement('fqdn_private_address', _('Name/IP Address association (name <-> ip)'), _('Enter a private addresses you wish to associate to a specific IP in case of issue with the DNS configuration or to override a reverse address result. Example: pong.office.ulteo.com (field 1) 192.168.0.113 (field 2)'), _('Enter a private addresses you wish to associate to a specific IP in case of issue with the DNS configuration or to override a reverse address result. Example: pong.office.ulteo.com (field 1) 192.168.0.113 (field 2)'), array(), NULL, ConfigElement::$DICTIONARY);
		$this->add($c,'general', 'application_server_settings');
		$c = new ConfigElement('disable_fqdn_check', _('Disable reverse FQDN checking'), _('Enable this option if you don\'t want to check that the result of the reverse FQDN address fits the one that was registered.'), _('Enable this option if you don\'t want to check that the result of the reverse FQDN address fits the one that was registered.'), 0, array(0=>_('no'),1=>_('yes')), ConfigElement::$SELECT);
		$this->add($c,'general', 'application_server_settings');
		$c = new ConfigElement('action_when_as_not_ready', _('Action when an ApS status is not ready anymore'), _('Action when an ApS status is not ready anymore'), _('Action when an ApS status is not ready anymore'), 1, array(0=>_('Do nothing'),1=>_('Switch to maintenance')), ConfigElement::$SELECT);
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
		$c = new ConfigElement('desktop_icons', _('Show icons on user desktop'), _('Show icons on user desktop'), _('Show icons on user desktop'), 1, array(0=>_('no'),1=>_('yes')), ConfigElement::$SELECT);
		$this->add($c,'general','session_settings_defaults');
		$c = new ConfigElement('launch_without_apps', _('User can launch a session with no application'), _('User can launch a session with no application'), _('User can launch a session with no application'), 0, array(0=>_('no'),1=>_('yes')), ConfigElement::$SELECT);
		$this->add($c,'general','session_settings_defaults');
		$c = new ConfigElement('shareable', _('Session owner can share his session'), _('Session owner can share his session'), _('Session owner can share his session'), 1, array(0=>_('no'),1=>_('yes')), ConfigElement::$SELECT);
		$this->add($c,'general','session_settings_defaults');
		$c = new ConfigElement('allow_shell', _('User can use a console in the session'), _('User can use a console in the session'), _('User can use a console in the session'), 0, array(0=>_('no'),1=>_('yes')), ConfigElement::$SELECT);
		$this->add($c,'general','session_settings_defaults');

		$c = new ConfigElement('action_when_active_session', _('Action to do when an user already have an active session'), _('Action to do when an user already have an active session'), _('Action to do when an user already have an active session'), 0, array(0=>_('Forbid access'),1=>_('Invite into the session')), ConfigElement::$SELECT);
		$this->add($c,'general','session_settings_defaults');

		$c = new ConfigElement('advanced_settings_startsession', _('Forceable paramaters by users'), _('Choose Advanced Settings options you want to make available to users before they launch a session.'), _('Choose Advanced Settings options you want to make available to users before they launch a session.'), array('testapplet'),array('language' => _('language'), 'server' => _('server'), 'size' => _('size'), 'quality' => _('quality'), 'timeout' => _('timeout'), 'application' => _('application'), 'persistent' => _('persistent'),
			'shareable' => _('shareable'),
			'desktop_icons' => _('desktop icons')),ConfigElement::$MULTISELECT);
		$this->add($c,'general','session_settings_defaults');

		$this->addPrettyName('web_interface_settings',_('Web interface settings'));

		$c = new ConfigElement('main_title', _('Heading title'), _('You can customize the heading title here.'), _('You can customize the heading title here.'), DEFAULT_PAGE_TITLE, NULL, ConfigElement::$INPUT);
		$this->add($c,'general','web_interface_settings');

		$c = new ConfigElement('logo_url',_('Logo URL'),_('You can customize the logo by entering a new path or replacing the corresponding image. Use a 90 pixels high image in png or jpeg format. Example: sessionmanager/media/image/header.png'),_('You can customize the logo by entering a new path or replacing the corresponding image. Use a 90 pixels high image in png or jpeg format. Example: sessionmanager/media/image/header.png'),DEFAULT_LOGO_URL ,NULL,ConfigElement::$INPUT);
		$this->add($c,'general','web_interface_settings');

		$c = new ConfigElement('show_list_users', _('Display users list'), _('Display the list of users from the corporate directory in the login box. If the list is not displayed, the user must enter his login name.'), _('Display the list of users from the corporate directory in the login box. If the list is not displayed, the user must enter his login name.'),0,array(0=>_('no'),1=>_('yes')),ConfigElement::$SELECT);
		$this->add($c,'general','web_interface_settings');
		$c = new ConfigElement('testapplet', _('SSH/ping applet test'), _('SSH/ping applet test'), _('SSH/ping applet test'), 1,array(0=>_('no'),1=>_('yes')), ConfigElement::$SELECT);
		$this->add($c,'general','web_interface_settings');
		$c = new ConfigElement('allow_proxy', _('Allow connections through proxy (SSH/ping applet test required)'), _('Allow connections through proxy (SSH/ping applet test required)'), _('Allow connections through proxy (SSH/ping applet test required)'), 1, array(0=>_('no'),1=>_('yes')), ConfigElement::$SELECT);
		$this->add($c,'general','web_interface_settings');
		$c = new ConfigElement('use_popup', _('Launch session in a popup'), _('When set to yes, the session will start in a new browser window, and when set to no, the session will start in the current browser window'), _('When set to yes, the session will start in a new browser window, and when set to no, the session will start in the current browser window'), 1, array(0=>_('no'),1=>_('yes')), ConfigElement::$SELECT);
		$this->add($c,'general','web_interface_settings');
		$c = new ConfigElement('advanced_settings_startsession', _('Forceable paramaters by users'), _('Choose Advanced Settings options you want to make available to users before they launch a session.'), _('Choose Advanced Settings options you want to make available to users before they launch a session.'), array(),array('popup' => _('popup'), 'debug' => _('debug')),ConfigElement::$MULTISELECT);
		$this->add($c,'general','web_interface_settings');

		$c = new ConfigElement('user_authenticate_sso', _('Use SSO for user authentication'), _('Use SSO for user authentication'), _('Use SSO for user authentication'), 0, array(0=>_('no'),1=>_('yes')), ConfigElement::$SELECT);

		$this->getPrefsModules();
		$this->getPrefsPlugins();
		$this->getPrefsEvents();
	}


	public function getPrefsPlugins(){
		$plugs = new Plugins();
		$p2 = $plugs->getAvailablePlugins();
		// we remove all disabled Plugins
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
		if (array_key_exists('plugins', $p2)) {
			foreach ($p2['plugins'] as $plugin_name => $plu6) {
				$plugin_prettyname = eval('return '.'Plugin_'.$plugin_name.'::prettyName();');
				if (is_null($plugin_prettyname))
					$plugin_prettyname = $plugin_name;
				$plugins_prettyname[$plugin_name] = $plugin_prettyname;
			}

			$c = new ConfigElement('plugin_enable', _('Plugins activation'), _('Choose the plugins you want to enable.'), _('Choose the plugins you want to enable.'), array(), $plugins_prettyname, ConfigElement::$MULTISELECT);
			$this->addPrettyName('plugins',_('Plugins configuration'));
			$this->add($c,'plugins');
			unset($p2['plugins']);
		}

		foreach ($p2 as $key1 => $value1){
			$plugins_prettyname = array();
			$c = new ConfigElement($key1, $key1, 'plugins '.$key1, 'plugins '.$key1, array(), $plugins_prettyname, ConfigElement::$SELECT);
			foreach ($value1 as $plugin_name => $plu6) {
				$plugin_prettyname = eval('return '.$key1.'_'.$plugin_name.'::prettyName();');
				if (is_null($plugin_prettyname))
					$plugin_prettyname = $plugin_name;
				$plugins_prettyname[$plugin_name] = $plugin_prettyname;

				$isdefault1 = eval('return '.$key1.'_'.$plugin_name.'::isDefault();');
				if ($isdefault1 === true) // replace the default value
					$c = new ConfigElement($key1, $key1, 'plugins '.$key1,'plugins '.$key1, $plugin_name, $plugins_prettyname, ConfigElement::$SELECT);

				$plugin_conf = 'return '.$key1.'_'.$plugin_name.'::configuration();';
				$list_conf = eval($plugin_conf);
				if (is_array($list_conf)) {
					foreach ($list_conf as $l_conf){
						$this->add($l_conf,'plugins', $key1.'_'.$plugin_name);
					}
				}
			}
			$this->add($c,'plugins');
		}
	}

	public function getPrefsModules(){
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

		$c2 = new ConfigElement('module_enable',_('Modules activation'), _('Choose the modules you want to enable.'), _('Choose the modules you want to enable.'), array('UserDB', 'ApplicationDB', 'UserGroupDB'), $modules_prettyname, ConfigElement::$MULTISELECT);
		$this->add($c2,'general');

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

	public function getPrefsEvents() {
		/* Events settings */
		$this->addPrettyName('events', _("Events settings"));

		$c = new ConfigElement('mail_to', _('Mail addresses to send alerts to'),
			_('On system alerts, mails will be sent to these addresses'), NULL,
			array(), NULL, ConfigElement::$LIST);
		$this->add($c,'events');

		$events = Events::loadAll();
		foreach ($events as $event) {
			$list = array();
			$pretty_list = array();
			foreach ($event->getCallbacks() as $cb) {
				if (! $cb['is_internal']) {
					$list[] = $cb['name'];
					$pretty_list[$cb['name']] = $cb['description'];
				}
			}
			if (count($list) == 0)
				continue;

			$event_name = $event->getPrettyName();
			/* FIXME: descriptions */
			$c = new ConfigElement(get_class($event), $event_name,
			                       "When $event_name is emitted",
			                       "When $event_name is emitted",
			                       array(), $pretty_list,
			                       ConfigElement::$MULTISELECT);
			$this->add($c, 'events', 'active_callbacks');
		}
		$this->addPrettyName('active_callbacks', _('Activated callbacks'));
		unset($events);
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

	public function add($value_,$key_,$container_=NULL){
		if (!is_null($container_)) {
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
			if (!isset($this->elements[$key_][$container_])) {
				$this->elements[$key_][$container_] = array();
			}
			// already something on [$key_][$container_]
			if (is_array($this->elements[$key_][$container_]))
				$this->elements[$key_][$container_][$value_->id]= $value_;
			else {
				$val = $this->elements[$key_][$container_];
				$this->elements[$key_][$container_] = array();
				$this->elements[$key_][$container_][$val->id]= $val;
				$this->elements[$key_][$container_][$value_->id]= $value_;
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

	public function addPrettyName($key_,$prettyName_) {
		$this->prettyName[$key_] = $prettyName_;
	}


}

class ConfigElement{
	public $id;
	public $label;
	public $description;
	public $description_detailed;
	public $content;
	public $content_available;
	public $type;

	static $TEXT = 0;
	static $INPUT = 1;
	static $SELECT = 2;
	static $MULTISELECT = 3;
	static $DICTIONARY = 4;
	static $LIST = 5;
	static $TEXTAREA = 6;
	static $PASSWORD = 7;
	static $INPUT_LIST = 8;
	static $SLIDERS = 9;

	public function __construct($id_, $label_, $description_, $description_detailed_, $content_, $content_available_, $type_){
		$this->id = $id_;
		$this->label = $label_;
		$this->description = $description_;
		$this->description_detailed = $description_detailed_;
		$this->content = $content_;
// 		$this->content_default = $content_default_;
		$this->content_available = $content_available_;
		$this->type = $type_;
	}

	public function __toString(){
		$str =  "<strong>ConfigElement</strong>( '".$this->id."','".$this->label."','";
		$str .=  '<strong>';
		if (is_array($this->content)) {
			$str .= 'array(';
			foreach($this->content as $k => $v)
				$str .= '\''.$k.'\' => \''.$v.'\' , ';
			$str .= ') ';
		}
		else
			$str .= $this->content;
		$str .=  '</strong>';
		$str .=  "','";
		if (is_array($this->content_available)) {
			$str .= 'array(';
			foreach($this->content_available as $k => $v)
				$str .= '\''.$k.'\' => \''.$v.'\' , ';
			$str .= ') ';
		}
		else
			$str .= $this->content_available;
		$str .=  "','".$this->description."','".$this->description_detailed."','".$this->type."'";
		$str .= ')';
		return $str;
	}

	public function reset() {
		if (is_string($this->content)) {
			$this->content = '';
		}
		else if (is_array($this->content)){
			$this->content = array();
		}
		else{
			// TODO
			$this->content = '';
		}
	}
}



