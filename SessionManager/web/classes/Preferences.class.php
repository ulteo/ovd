<?php
/**
 * Copyright (C) 2008-2013 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2008-2011
 * Author Julien LANGLOIS <julien@ulteo.com> 2011, 2012, 2013
 * Author David PHAM-VAN <d.pham-van@ulteo.com> 2012, 2013
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

	public static function removeInstance() {
		self::$instance = null;
	}

	public static function hasInstance() {
		return isset(self::$instance) and self::$instance != null;
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
	
	public static function fileExists() {
		return @file_exists(SESSIONMANAGER_CONFFILE_SERIALIZED); // ugly
	}

	public function getKeys(){
		return array_keys($this->elements);
	}
	
	public function getElements($container_,$container_sub_) {
		if (isset($this->elements[$container_])) {
			if (isset($this->elements[$container_][$container_sub_])) {
				return $this->elements[$container_][$container_sub_];
			}
			else {
				Logger::error('main',"Preferences::getElements($container_,$container_sub_), '$container_' found but '$container_sub_' not found");
				return NULL;
			}
		}
		else {
			Logger::error('main',"Preferences::getElements($container_,$container_sub_), '$container_'  not found");
			return NULL;
		}
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
			return array();
		}
		
		$ret = json_unserialize(@file_get_contents($this->conf_file, LOCK_EX));
		if ($ret === false) {
			return array();
		}
		
		return $ret;
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
									}
								}
							}
						}
					}
				}
			}
		}
	}

	public function initialize(){

		$c = new ConfigElement_select('system_in_maintenance', 0);
		$c->setContentAvailable(array(0, 1));
		$this->add($c,'general');

		$c = new ConfigElement_select('admin_language', 'auto');
		$c->setContentAvailable(array(
			'auto',
			'ar_AE',
			'de_DE',
			'en_GB',
			'es_ES',
			'fr_FR',
			'hu_HU',
//			'id_ID',
			'it_IT',
			'ja_JP',
			'nl_NL',
			'pt_BR',
			'sk_SK',
			'ro_RO',
			'ru_RU',
			'zh_CN',
		));
		$this->add($c,'general');

		$c = new ConfigElement_multiselect('log_flags', array('info','warning','error','critical'));
		$c->setContentAvailable(array('debug','info', 'warning','error','critical'));
		$this->add($c,'general');
		$c = new ConfigElement_select('cache_update_interval', 30);
		$c->setContentAvailable(array(30, 60, 300, 900, 1800, 3600, 7200));
		$this->add($c,'general');
		$c = new ConfigElement_select('cache_expire_time', (86400*366));
		$c->setContentAvailable(array(86400, (86400*7), (86400*31), (86400*366)));
		$this->add($c,'general');

// 		$c = new ConfigElement_input('start_app','');
// 		$this->add('general',$c);

		$c = new ConfigElement_text('user_default_group', '');
		$this->add($c,'general');

		$c = new ConfigElement_select('domain_integration', 'internal');
		$c->setContentAvailable(array(
			'internal',
			'microsoft',
			'ldap',
			'novell',
		));
		
		$this->add($c, 'general');

		$c = new ConfigElement_input('max_items_per_page', 15);
		$this->add($c,'general');
		
		$c = new ConfigElement_inputlist('default_browser', array('linux' => NULL, 'windows' => NULL));
		$this->add($c,'general');
		
		$c = new ConfigElement_dictionary('liaison', array());
		$this->add($c,'general');
		
		$c = new ConfigElement_multiselect('default_policy', array());
		$c->setContentAvailable(array(
			'canUseAdminPanel',
			'viewServers',
			'manageServers',
			'viewSharedFolders',
			'manageSharedFolders',
			'viewUsers',
			'manageUsers',
			'viewUsersGroups',
			'manageUsersGroups',
			'viewApplications',
			'manageApplications',
			'viewApplicationsGroups',
			'manageApplicationsGroups',
			'viewPublications',
			'managePublications',
			'viewConfiguration',
			'manageConfiguration',
			'viewStatus',
			'manageSession',
			'manageReporting',
			'viewSummary',
			'viewNews',
			'manageNews'
		));

		$this->add($c,'general', 'policy');
		$c = new ConfigElement_select('type', 'mysql');
		$c->setContentAvailable(array('mysql'));
		$this->add($c,'general','sql');
		$c = new ConfigElement_input('host', 'localhost');
		$this->add($c,'general','sql');
		$c = new ConfigElement_input('user', '');
		$this->add($c,'general','sql');
		$c = new ConfigElement_password('password', '');
		$this->add($c,'general','sql');
		$c = new ConfigElement_input('database', 'ovd');
		$this->add($c,'general','sql');
		$c = new ConfigElement_input('prefix', 'ulteo_');
		$this->add($c,'general','sql');

		$c_mail_type = new ConfigElement_select('send_type', 'mail');
		$c_mail_type->setContentAvailable(array('mail', 'smtp'));
		$this->add($c_mail_type,'general','mails_settings');
		
		$c = new ConfigElement_input('send_from', 'no-reply@'.@$_SERVER['SERVER_NAME']);
		$this->add($c,'general','mails_settings');
		
		// SMTP conf
		$c = new ConfigElement_input('send_host', '');
		$this->add($c,'general','mails_settings');
		$c_mail_type->addReference('smtp', $c);
		$c = new ConfigElement_input('send_port', 25);
		$this->add($c,'general','mails_settings');
		$c_mail_type->addReference('smtp', $c);
		$c = new ConfigElement_select('send_ssl', 0);
		$c->setContentAvailable(array(0, 1));
		$this->add($c,'general','mails_settings');
		$c_mail_type->addReference('smtp', $c);
		$c = new ConfigElement_select('send_auth',0);
		$c->setContentAvailable(array(0, 1));
		$this->add($c,'general','mails_settings');
		$c_mail_type->addReference('smtp', $c);
		$c = new ConfigElement_input('send_username', '');
		$this->add($c,'general','mails_settings');
		$c_mail_type->addReference('smtp', $c);
		$c = new ConfigElement_password('send_password', '');
		$this->add($c,'general','mails_settings');
		$c_mail_type->addReference('smtp', $c);

		$c = new ConfigElement_list('authorized_fqdn', array('*'));
		$this->add($c,'general', 'slave_server_settings');
		$c = new ConfigElement_select('disable_fqdn_check', 0);
		$c->setContentAvailable(array(0, 1));
		$this->add($c,'general', 'slave_server_settings');
		$c = new ConfigElement_select('use_reverse_dns', true);
		$c->setContentAvailable(array(false, true));
		$this->add($c,'general', 'slave_server_settings');
		$c = new ConfigElement_select('action_when_as_not_ready', 0);
		$c->setContentAvailable(array(0, 1));
		$this->add($c,'general', 'slave_server_settings');
		$c = new ConfigElement_select('auto_recover', 1);
		$c->setContentAvailable(array(0, 1));
		$this->add($c,'general', 'slave_server_settings');
		$c = new ConfigElement_select('remove_orphan', 0);
		$c->setContentAvailable(array(0, 1));
		$this->add($c,'general','slave_server_settings');
		$c = new ConfigElement_select('auto_register_new_servers', 0);
		$c->setContentAvailable(array(0, 1));
		$this->add($c,'general','slave_server_settings');
		$c = new ConfigElement_select('auto_switch_new_servers_to_production', 0);
		$c->setContentAvailable(array(0, 1));
		$this->add($c,'general','slave_server_settings');
		$c = new ConfigElement_select('use_max_sessions_limit', 1);
		$c->setContentAvailable(array(0, 1));
		$this->add($c,'general','slave_server_settings');

		$roles = array(Server::SERVER_ROLE_APS, Server::SERVER_ROLE_FS);
		foreach ($roles as $role) {
			$decisionCriterion = get_classes_startwith('DecisionCriterion_');
			$content_load_balancing = array();
			foreach ($decisionCriterion as $criterion_class_name) {
				$c = new $criterion_class_name(NULL); // ugly
				if ($c->applyOnRole($role)) {
					$content_load_balancing[substr($criterion_class_name, strlen('DecisionCriterion_'))] = $c->default_value();
				}
			}
			$c = new ConfigElement_sliders_loadbalancing('load_balancing_'.$role, $content_load_balancing);
			$this->add($c,'general', 'slave_server_settings');
		}

		$c_desktop_mode = new ConfigElement_select('enabled', 1);
		$c_desktop_mode->setContentAvailable(array(0, 1));
		$this->add($c_desktop_mode,'general','remote_desktop_settings');
		$c = new ConfigElement_select('desktop_icons', 1);
		$c->setContentAvailable(array(0, 1));
		$c_desktop_mode->addReference('1', $c);
		$this->add($c,'general','remote_desktop_settings');
		$c = new ConfigElement_select('allow_external_applications', 1);
		$c->setContentAvailable(array(0, 1));
		$c_desktop_mode->addReference('1', $c);
		$this->add($c,'general','remote_desktop_settings');
		$c = new ConfigElement_select('desktop_type', 'any');
		$c->setContentAvailable(array('any', 'linux' ,'windows'));
		$c_desktop_mode->addReference('1', $c);
		$this->add($c,'general','remote_desktop_settings');
		
		$c = new ConfigElement_list('allowed_desktop_servers', array());
		$c_desktop_mode->addReference('1', $c);
		$this->add($c,'general','remote_desktop_settings');
		
		$c = new ConfigElement_select('authorize_no_desktop', 1);
		$c->setContentAvailable(array(0, 1));
		$this->add($c,'general','remote_desktop_settings');

		$c = new ConfigElement_select('enabled', 1);
		$c->setContentAvailable(array(0, 1));
		$this->add($c,'general','remote_applications_settings');

		$c = new ConfigElement_select('session_mode', Session::MODE_APPLICATIONS);
		$c->setContentAvailable(array(Session::MODE_DESKTOP, Session::MODE_APPLICATIONS));
		$this->add($c,'general','session_settings_defaults');

		$c = new ConfigElement_select('language', 'en_GB');
		$c->setContentAvailable(array(
			'ar_AE',
			'bg_BG',
			'da-dk',
			'de_DE',
			'en_GB',
			'el_GR',
			'es_ES',
			'fa_IR',
			'fi_FI',
			'fr_FR',
			'he_IL',
			'hu_HU',
			'id_ID',
			'is_IS',
			'it_IT',
			'ja_JP',
			'nb_NO',
			'nl_NL',
			'pl_PL',
			'pt_BR',
			'ro_RO',
			'ru_RU',
			'sk_SK',
			'zh_CN',
		));
		$this->add($c,'general','session_settings_defaults');
		$c = new ConfigElement_select('timeout', -1);
		$c->setContentAvailable(array(
			60,
			120,
			300,
			600,
			900,
			1800,
			3600,
			7200,
			18000,
			43200,
			86400,
			172800,
			604800,
			2764800,
			-1));
		$this->add($c,'general','session_settings_defaults');
		
		$c = new ConfigElement_week_time_select('time_restriction', str_repeat("FF", 3 * 7));
		$this->add($c,'general','session_settings_defaults');
		
		$c = new ConfigElement_input('max_sessions_number', 0);
		$this->add($c, 'general');
		$c = new ConfigElement_select('launch_without_apps', 0);
		$c->setContentAvailable(array(0, 1));
		$this->add($c,'general','session_settings_defaults');
		$c = new ConfigElement_select('allow_shell', 0);
		$c->setContentAvailable(array(0, 1));
		$this->add($c,'general','session_settings_defaults');
		
		$c = new ConfigElement_select('use_known_drives', 0);
		$c->setContentAvailable(array(0, 1));
		$this->add($c,'general','session_settings_defaults');

		$c = new ConfigElement_select('multimedia', 1);
		$c->setContentAvailable(array(0, 1));
		$this->add($c,'general','session_settings_defaults');
		$c = new ConfigElement_select('redirect_client_drives', 'full');
		$c->setContentAvailable(array('no', 'partial', 'full'));
		$this->add($c,'general','session_settings_defaults');
		$c = new ConfigElement_select('redirect_client_printers', 1);
		$c->setContentAvailable(array(0, 1));
		$this->add($c,'general','session_settings_defaults');
		$c = new ConfigElement_select('redirect_smartcards_readers', 0);
		$c->setContentAvailable(array(0, 1));
		$this->add($c, 'general', 'session_settings_defaults');
		$c = new ConfigElement_select('rdp_bpp', 16);
		$c->setContentAvailable(array(16, 24, 32));
		$this->add($c,'general','session_settings_defaults');
		$c = new ConfigElement_select('enhance_user_experience', 1);
		$c->setContentAvailable(array(0, 1));
		$this->add($c,'general','session_settings_defaults');
		
		$c = new ConfigElement_select('persistent', 1);
		$c->setContentAvailable(array(0, 1));
		$this->add($c,'general','session_settings_defaults');

		$c_user_profile = new ConfigElement_select('enable_profiles', 1);
		$c_user_profile->setContentAvailable(array(0, 1));
		$this->add($c_user_profile,'general','session_settings_defaults');
		
		$c = new ConfigElement_input('quota', 0);
		$c_user_profile->addReference('1', $c);
		$this->add($c,'general','session_settings_defaults');
		
		$c = new ConfigElement_select('auto_create_profile', 1);
		$c->setContentAvailable(array(0, 1));
		$c_user_profile->addReference('1', $c);
		$this->add($c,'general','session_settings_defaults');
		$c = new ConfigElement_select('start_without_profile', 1);
		$c->setContentAvailable(array(0, 1));
		$c_user_profile->addReference('1', $c);
		$this->add($c,'general','session_settings_defaults');
		
		$c_shared_folder = new ConfigElement_select('enable_sharedfolders', 1);
		$c_shared_folder->setContentAvailable(array(0, 1));
		$this->add($c_shared_folder,'general','session_settings_defaults');
		$c = new ConfigElement_select('start_without_all_sharedfolders', 1);
		$c->setContentAvailable(array(0, 1));
		$c_shared_folder->addReference('1', $c);
		$this->add($c,'general','session_settings_defaults');
		$c = new ConfigElement_select('can_force_sharedfolders', 1);
		$c->setContentAvailable(array(0, 1));
		$this->add($c,'general','session_settings_defaults');

		$c = new ConfigElement_multiselect('advanced_settings_startsession', array('session_mode', 'language'));
		$c->setContentAvailable(array('session_mode', 'language', 'server', 'timeout', 'persistent', /*'shareable'*/));
		$this->add($c,'general','session_settings_defaults');

		$c = new ConfigElement_select('show_list_users', 0);
		$c->setContentAvailable(array(0, 1));
		$this->add($c,'general','web_interface_settings');
		
		$c = new ConfigElement_select('public_webservices_access', 0);
		$c->setContentAvailable(array(0, 1));
		$this->add($c,'general','web_interface_settings');

		$this->getPrefsModules();
		$this->getPrefsEvents();
	}

	public function getPrefsModules(){
		$available_module = $this->getAvailableModule();
		// we remove all diseable modules
		foreach ($available_module as $mod2 => $sub_mod2){
			foreach ($sub_mod2 as $sub_mod_name2){
				$enable =  call_user_func(array($mod2.'_'.$sub_mod_name2, 'enable'));
				if ($enable !== true)
					unset ($available_module[$mod2][$sub_mod_name2]);
			}

		}
		$modules_prettyname = array();
		$enabledByDefault = array();
		foreach ($available_module as $module_name => $sub_module) {
			$modules_prettyname[] = $module_name;
			if (call_user_func(array($module_name, 'enabledByDefault')))
				$enabledByDefault[] =  $module_name;
		}
		$c2 = new ConfigElement_multiselect('module_enable', $enabledByDefault);
		$c2->setContentAvailable($modules_prettyname);
		$this->add($c2, 'general');

		foreach ($available_module as $mod => $sub_mod){
			$module_is_multiselect = call_user_func(array($mod, 'multiSelectModule'));
			if ( $module_is_multiselect) {
				$c = new ConfigElement_multiselect('enable', array());
				$c->setContentAvailable($sub_mod);
			}
			else {
				$c = new ConfigElement_select('enable', NULL);
				$c->setContentAvailable($sub_mod);
			}

			foreach ($sub_mod as $k4) {
				$default1 = call_user_func(array($mod.'_'.$k4, 'isDefault'));
				if ($default1 === true) {
					if ( $module_is_multiselect) {
						$c->content[] = $k4;
					}
					else {
						$c->content = $k4;
					}
				}
			}

			//dirty hack (if this->elements[mod] will be empty)
			if (!isset($this->elements[$mod]))
				$this->elements[$mod] = array();

			$this->add($c,$mod);
			foreach ($sub_mod as $sub_mod_name){
				$module_name= $mod.'_'.$sub_mod_name;
				$list_conf = call_user_func(array($module_name, 'configuration'));
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
		$c = new ConfigElement_list('mail_to', array());
		$this->add($c,'events');

		$events = Events::loadAll();
		foreach ($events as $event) {
			$list = array();
			$pretty_list = array();
			foreach ($event->getCallbacks() as $cb) {
				if (! $cb['is_internal']) {
					$list[] = $cb['name'];
					$pretty_list[] = $cb['name'];
				}
			}
			if (count($list) == 0)
				continue;

			$event_name = $event->getPrettyName();
			/* FIXME: descriptions */
			$c = new ConfigElement_multiselect(get_class($event),
			                       array());
			$c->setContentAvailable($pretty_list);
			$this->add($c, 'events', 'active_callbacks');
		}
		unset($events);
	}

	public function getAvailableModule(){
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
						if (array_key_exists('extension', $pathinfo) && ($pathinfo['extension'] == 'php')) {
							$ret[basename($pathinfo["dirname"])][] = $pathinfo["filename"];
						}
					}
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

	public static function moduleIsEnabled($name_) {
		$prefs = Preferences::getInstance();
		if (! $prefs)
			die_error('get Preferences failed', __FILE__, __LINE__);
			
		$mods_enable = $prefs->get('general', 'module_enable');
		return in_array($name_, $mods_enable);
	}
	
	public function getLiaisonsOwner() {
		$types = array();
		
		$modules_enable = $this->get('general', 'module_enable');
		foreach ($modules_enable as $module_name) {
			if (method_exists($module_name, 'getInstance')) {
				try {
					$module_instance = call_user_func(array($module_name, 'getInstance'));
				}
				catch (Exception $err) {
					continue;
				}
				if (is_object($module_instance)) {
					if (method_exists($module_instance, 'liaisonType')) {
						$liaisons = $module_instance->liaisonType();
						if (is_array($liaisons)) {
							foreach ($liaisons as $liaison) {
								if (is_array($liaison) && array_key_exists('type', $liaison) && array_key_exists('owner', $liaison)) {
									$types[$liaison['type']] = $liaison['owner'];
								}
							}
						}
					}
				}
			}
		}
		
		$overwrited_liaisons = $this->get('general', 'liaison');
		foreach ($overwrited_liaisons as $type => $owner) {
			$types[$type] = $owner;
		}
		
		return $types;
	}
	
	public static function liaisonsOwner() {
		$types = array();
		
		$prefs = Preferences::getInstance();
		if (! $prefs)
			die_error('get Preferences failed', __FILE__, __LINE__);
		
		return $prefs->getLiaisonsOwner();
	}
}
