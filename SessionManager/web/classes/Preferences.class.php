<?php
/**
 * Copyright (C) 2008-2012 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2008-2011
 * Author Julien LANGLOIS <julien@ulteo.com> 2011, 2012
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
		
		$ret = @unserialize(@file_get_contents($this->conf_file, LOCK_EX));
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

		$this->addPrettyName('general',_('General configuration'));

		$c = new ConfigElement_select('system_in_maintenance', _('System on maintenance mode'), _('System on maintenance mode'), _('System on maintenance mode'), 0);
		$c->setContentAvailable(array(0=>_('no'), 1=>_('yes')));
		$this->add($c,'general');

		$c = new ConfigElement_select('admin_language', _('Administration console language'), _('Administration console language'), _('Administration console language'), 'auto');
		$c->setContentAvailable(array(
			'auto'=>_('Autodetect'),
			'ar_AE'=>'العربي',
			'ca-es'=>'Català',
			'cs_CZ'=>'Česky',
			'de_DE'=>'Deutsch',
			'en_GB'=>'English',
			'es_ES'=>'Español',
			'fr_FR'=>'Français',
			'hu_HU'=>'Magyar',
//			'id_ID'=>'Bahasa Indonesia',
			'it_IT'=>'Italiano',
			'ja_JP'=>'日本語',
			'nl_NL'=>'Nederlands',
			'pt_BR'=>'Português (Brasil)',
			'sk_SK'=>'Slovenčina',
			'ro_RO'=>'Română',
			'ru_RU'=>'Русский',
			'zh_CN'=>'中文简体',
		));
		$this->add($c,'general');

		$c = new ConfigElement_multiselect('log_flags', _('Debug options list'), _('Select debug options you want to enable.'), _('Select debug options you want to enable.'), array('info','warning','error','critical'));
		$c->setContentAvailable(array('debug' => _('debug'),'info' => _('info'), 'warning' => _('warning'),'error' => _('error'),'critical' => _('critical')));
		$this->add($c,'general');
		$c = new ConfigElement_select('cache_update_interval', _('Cache logs update interval'), _('Cache logs update interval'), _('Cache logs update interval'), 30);
		$c->setContentAvailable(array(30=>_('30 seconds'), 60=>_('1 minute'), 300=>_('5 minutes'), 900=>_('15 minutes'), 1800=>_('30 minutes'), 3600=>_('1 hour'), 7200=>_('2 hours')));
		$this->add($c,'general');
		$c = new ConfigElement_select('cache_expire_time', _('Cache logs expiry time'), _('Cache logs expiry time'), _('Cache logs expiry time'), (86400*366));
		$c->setContentAvailable(array(86400=>_('A day'), (86400*7)=>_('A week'), (86400*31)=>_('A month'), (86400*366)=>_('A year')));
		$this->add($c,'general');

// 		$c = new ConfigElement_input('start_app','start_app','start_app_des','');
// 		$this->add('general',$c);

		$c = new ConfigElement_text('user_default_group', _('Default user group'), _('Default user group'), _('Default user group'), '');
		$this->add($c,'general');

		$c = new ConfigElement_select('domain_integration', _('Domain integration'), _('Domain integration'), _('Domain integration'), 'internal');
		$domain_integration_select = array();
		if (function_exists('get_classes_startwith_admin')) { // are we on /admin ?
			$domain_integration_classes = get_classes_startwith_admin('Configuration_mode_');
			foreach($domain_integration_classes as $a_class) {
				if (class_exists($a_class)) { // can not call class->getPrettyName(); because we might be on the admin
					$b = new $a_class();
					$name = substr($a_class, strlen('Configuration_mode_'));
					$domain_integration_select[$name] = $b->getPrettyName();
				}
				else {
					$name = substr($a_class, strlen('Configuration_mode_'));
					$domain_integration_select[$name] = $name; 
				}
			}
		}
		else {
			$domain_integration_select['internal'] = _('Internal');
		}
		$c->setContentAvailable($domain_integration_select);
		$this->add($c, 'general');

		$c = new ConfigElement_input('max_items_per_page', _('Maximum items per page'), _('The maximum number of items that can be displayed.'), _('The maximum number of items that can be displayed.'), 100);
		$this->add($c,'general');
		
		$c = new ConfigElement_multiselect('default_policy', _('Default policy'), _('Default policy'), _('Default policy'), array());
		$c->setContentAvailable(array(
			'canUseAdminPanel' => _('use Admin panel'),
			'viewServers' => _('view Servers'),
			'manageServers' => _('manage Servers'),
			'viewSharedFolders' => _('view Shared folders'),
			'manageSharedFolders' => _('manage Shared folders'),
			'viewUsers' => _('view Users'),
			'manageUsers' => _('manage Users'),
			'viewUsersGroups' => _('view Usergroups'),
			'manageUsersGroups' => _('manage Usergroups'),
			'viewApplications' => _('view Applications'),
			'manageApplications' => _('manage Applications'),
			'viewApplicationsGroups' => _('view Application groups'),
			'manageApplicationsGroups' => _('manage Application groups'),
			'viewPublications' => _('view Publications'),
			'managePublications' => _('manage Publications'),
			'viewConfiguration' => _('view Configuration'),
			'manageConfiguration' => _('manage Configuration'),
			'viewStatus' => _('view Status'),
			'viewSummary' => _('view Summary'),
			'viewNews' => _('view News'),
			'manageNews' => _('manage News')
		));

		$this->add($c,'general', 'policy');
		$this->addPrettyName('policy', _('Policy for administration delegation'));

		$this->addPrettyName('sql',_('SQL configuration'));
		$c = new ConfigElement_select('type', _('Database type'), _('The type of your database.'), _('The type of your database.'), 'mysql');
		$c->setContentAvailable(array('mysql'=>_('MySQL')));
		$this->add($c,'general','sql');
		$c = new ConfigElement_input('host', _('Database host address'), _('The address of your database host. This database contains adminstration console data. Example: localhost or db.mycorporate.com.'), _('The address of your database host. This database contains adminstration console data. Example: localhost or db.mycorporate.com.'),'localhost');
		$this->add($c,'general','sql');
		$c = new ConfigElement_input('user', _('Database username'), _('The username that must be used to access the database.'), _('The user name that must be used to access the database.'),'');
		$this->add($c,'general','sql');
		$c = new ConfigElement_password('password',_('Database password'), _('The user password that must be used to access the database.'), _('The user password that must be used to access the database.'),'');
		$this->add($c,'general','sql');
		$c = new ConfigElement_input('database', _('Database name'), _('The name of the database.'), _('The name of the database.'), 'ovd');
		$this->add($c,'general','sql');
		$c = new ConfigElement_input('prefix', _('Table prefix'), _('The table prefix for the database.'), _('The table prefix for the database.'), 'ulteo_');
		$this->add($c,'general','sql');

		$this->addPrettyName('mails_settings',_('Email settings'));
		$c_mail_type = new ConfigElement_select('send_type', _('Mail server type'), _('Mail server type'), _('Mail server type'),'mail');
		$c_mail_type->setContentAvailable(array('mail'=>_('Local'),'smtp'=>_('SMTP server')));
		$this->add($c_mail_type,'general','mails_settings');
		
		$c = new ConfigElement_input('send_from', _('From'), _('From'), _('From'), 'no-reply@'.@$_SERVER['SERVER_NAME']);
		$this->add($c,'general','mails_settings');
		
		// SMTP conf
		$c = new ConfigElement_input('send_host', _('Host'), _('Host'), _('Host'), '');
		$this->add($c,'general','mails_settings');
		$c_mail_type->addReference('smtp', $c);
		$c = new ConfigElement_input('send_port', _('Port'), _('Port'), _('Port'), 25);
		$this->add($c,'general','mails_settings');
		$c_mail_type->addReference('smtp', $c);
		$c = new ConfigElement_select('send_ssl', _('Use SSL with SMTP'), _('Use SSL with SMTP'), _('Use SSL with SMTP'), 0);
		$c->setContentAvailable(array(0=>_('no'),1=>_('yes')));
		$this->add($c,'general','mails_settings');
		$c_mail_type->addReference('smtp', $c);
		$c = new ConfigElement_select('send_auth', _('Authentication'), _('Authentication'), _('Authentication'),0);
		$c->setContentAvailable(array(0=>_('no'),1=>_('yes')));
		$this->add($c,'general','mails_settings');
		$c_mail_type->addReference('smtp', $c);
		$c = new ConfigElement_input('send_username', _('SMTP username'), _('SMTP username'), _('SMTP username'), '');
		$this->add($c,'general','mails_settings');
		$c_mail_type->addReference('smtp', $c);
		$c = new ConfigElement_password('send_password', _('SMTP password'), _('SMTP password'), _('SMTP password'), '');
		$this->add($c,'general','mails_settings');
		$c_mail_type->addReference('smtp', $c);

		$this->addPrettyName('slave_server_settings',_('Slave Server settings'));
		$c = new ConfigElement_list('authorized_fqdn', _('Authorized machines (FQDN or IP - the use of wildcards (*.) is allowed)'), _('Authorized machines (FQDN or IP - the use of wildcards (*.) is allowed)'), _('Authorized machines (FQDN or IP - the use of wildcards (*.) is allowed)'), array('*'));
		$this->add($c,'general', 'slave_server_settings');
		$c = new ConfigElement_select('disable_fqdn_check', _('Disable reverse FQDN checking'), _('Enable this option if you don\'t want to check that the result of the reverse FQDN address fits the one that was registered.'), _('Enable this option if you don\'t want to check that the result of the reverse FQDN address fits the one that was registered.'), 0);
		$c->setContentAvailable(array(0=>_('no'),1=>_('yes')));
		$this->add($c,'general', 'slave_server_settings');
		$c = new ConfigElement_select('action_when_as_not_ready', _('Action when a server status is not ready anymore'), _('Action when a server status is not ready anymore'), _('Action when an server status is not ready anymore'), 0);
		$c->setContentAvailable(array(0=>_('Do nothing'),1=>_('Switch to maintenance')));
		$this->add($c,'general', 'slave_server_settings');
		$c = new ConfigElement_select('auto_recover', _('Auto-recover server'), _('When a server status is down or broken, and it is sending monitoring, try to switch it back to ready ?'), _('When a server status is down or broken, and it is sending monitoring, try to switch it back to ready ?'), 1);
		$c->setContentAvailable(array(0=>_('no'),1=>_('yes')));
		$this->add($c,'general', 'slave_server_settings');
		$c = new ConfigElement_select('remove_orphan', _('Remove orphan applications when the application server is deleted'), _('Remove orphan applications when the application server is deleted'), _('Remove orphan applications when the application server is deleted'), 0);
		$c->setContentAvailable(array(0=>_('no'),1=>_('yes')));
		$this->add($c,'general','slave_server_settings');
		$c = new ConfigElement_select('auto_register_new_servers', _('Auto register new servers'), _('Auto register new servers'), _('Auto register new servers'), 0);
		$c->setContentAvailable(array(0=>_('no'),1=>_('yes')));
		$this->add($c,'general','slave_server_settings');
		$c = new ConfigElement_select('auto_switch_new_servers_to_production', _('Auto switch new servers to production mode'), _('Auto switch new servers to production mode'), _('Auto switch new servers to production mode'), 0);
		$c->setContentAvailable(array(0=>_('no'),1=>_('yes')));
		$this->add($c,'general','slave_server_settings');
		$c = new ConfigElement_select('use_max_sessions_limit', _('When an Application Server have reached its "max sessions" limit, disable session launch on it ?'), _('When an Application Server have reached its "max sessions" limit, disable session launch on it ?'), _('When an Application Server have reached its "max sessions" limit, disable session launch on it ?'), 1);
		$c->setContentAvailable(array(0=>_('no'),1=>_('yes')));
		$this->add($c,'general','slave_server_settings');

		$roles = array(Server::SERVER_ROLE_APS => _('Load Balancing policy for Application Servers'), Server::SERVER_ROLE_FS => _('Load Balancing policy for File Servers'));
		foreach ($roles as $role => $text) {
			$decisionCriterion = get_classes_startwith('DecisionCriterion_');
			$content_load_balancing = array();
			foreach ($decisionCriterion as $criterion_class_name) {
				$c = new $criterion_class_name(NULL); // ugly
				if ($c->applyOnRole($role)) {
					$content_load_balancing[substr($criterion_class_name, strlen('DecisionCriterion_'))] = $c->default_value();
				}
			}
			$c = new ConfigElement_sliders_loadbalancing('load_balancing_'.$role, $text, $text, $text, $content_load_balancing);
			$this->add($c,'general', 'slave_server_settings');
		}

		$this->addPrettyName('remote_desktop_settings', _('Remote Desktop settings'));

		$c_desktop_mode = new ConfigElement_select('enabled', _('Enable Remote Desktop'), _('Enable Remote Desktop'), _('Enable Remote Desktop'), 1);
		$c_desktop_mode->setContentAvailable(array(0=>_('no'),1=>_('yes')));
		$this->add($c_desktop_mode,'general','remote_desktop_settings');
		$c = new ConfigElement_select('persistent', _('Sessions are persistent'), _('Sessions are persistent'), _('Sessions are persistent'), 0);
		$c->setContentAvailable(array(0=>_('no'),1=>_('yes')));
		$c_desktop_mode->addReference('1', $c);
		$this->add($c,'general','remote_desktop_settings');
		$c = new ConfigElement_select('desktop_icons', _('Show icons on user desktop'), _('Show icons on user desktop'), _('Show icons on user desktop'), 1);
		$c->setContentAvailable(array(0=>_('no'),1=>_('yes')));
		$c_desktop_mode->addReference('1', $c);
		$this->add($c,'general','remote_desktop_settings');
		$c = new ConfigElement_select('allow_external_applications', _('Allow external applications in Desktop'), _('Allow external applications in Desktop'), _('Allow external applications in Desktop'), 1);
		$c->setContentAvailable(array(0=>_('no'),1=>_('yes')));
		$c_desktop_mode->addReference('1', $c);
		$this->add($c,'general','remote_desktop_settings');
		$c = new ConfigElement_select('desktop_type', _('Desktop type'), _('Desktop type'), _('Desktop type'), 'any');
		$c->setContentAvailable(array('any'=>_('Any'),'linux'=>_('Linux'),'windows'=>_('Windows')));
		$c_desktop_mode->addReference('1', $c);
		$this->add($c,'general','remote_desktop_settings');
		
		$c = new ConfigElement_list('allowed_desktop_servers', _('Servers which are allowed to start desktop'), _('An empty list means all servers can host a desktop (no restriction on desktop server choice)'), _('An empty list means all servers can host a desktop (no restriction on desktop server choice)'), array());
		$c_desktop_mode->addReference('1', $c);
		$this->add($c,'general','remote_desktop_settings');

		$this->addPrettyName('remote_applications_settings', _('Remote Applications settings'));

		$c = new ConfigElement_select('enabled', _('Enable Remote Applications'), _('Enable Remote Applications'), _('Enable Remote Applications'), 1);
		$c->setContentAvailable(array(0=>_('no'),1=>_('yes')));
		$this->add($c,'general','remote_applications_settings');

		$this->addPrettyName('session_settings_defaults',_('Sessions settings'));

		$c = new ConfigElement_select('session_mode', _('Default mode for session'), _('Default mode for session'), _('Default mode for session'), Session::MODE_APPLICATIONS);
		$c->setContentAvailable(array(Session::MODE_DESKTOP=>_('Desktop'), Session::MODE_APPLICATIONS=>_('Applications')));
		$this->add($c,'general','session_settings_defaults');

		$c = new ConfigElement_select('language', _('Default language for session'), _('Default language for session'), _('Default language for session'), 'en_GB');
		$c->setContentAvailable(array(
			'ar_AE'=>'العربي',
			'bg_BG'=>'Български',
			'ca-es'=>'Català',
			'cs_CZ'=>'Česky',
			'da-dk'=>'Dansk',
			'de_DE'=>'Deutsch',
			'en_GB'=>'English',
			'el_GR'=>'Ελληνικά',
			'es_ES'=>'Español',
			'fa_IR'=>'فارسی',
			'fi_FI'=>'Suomi',
			'fr_FR'=>'Français',
			'he_IL'=>'עברית',
			'hu_HU'=>'Magyar',
			'id_ID'=>'Bahasa Indonesia',
			'is_IS'=>'Icelandic',
			'it_IT'=>'Italiano',
			'ja_JP'=>'日本語',
			'ko_KR'=>'한국어',
			'nb_NO'=>'Norsk (bokmål)',
			'nl_NL'=>'Nederlands',
			'pl_PL'=>'Polski',
			'pt_PT'=>'Português',
			'pt_BR'=>'Português (Brasil)',
			'ro_RO'=>'Română',
			'ru_RU'=>'Русский',
			'sk_SK'=>'Slovenčina',
			'zh_CN'=>'中文简体',
		));
		$this->add($c,'general','session_settings_defaults');
		$c = new ConfigElement_select('timeout', _('Default timeout for session'), _('Default timeout for session'), _('Default timeout for session'), -1);
		$c->setContentAvailable(array(60 => _('1 minute'),120 => _('2 minutes'),300 => _('5 minutes'),600 => _('10 minutes'),900 => _('15 minutes'),1800 => _('30 minutes'),3600 => _('1 hour'),7200 => _('2 hours'),18000 => _('5 hours'),43200 => _('12 hours'),86400 => _('1 day'),172800 => _('2 days'),604800 => _('1 week'),2764800 => _('1 month'),-1 => _('None')));
		$this->add($c,'general','session_settings_defaults');
		$c = new ConfigElement_input('max_sessions_number', _('Maximum number of running sessions'), _('The maximum number of session that can be started on the farm (0 is unlimited).'), _('The maximum number of session that can be started on the farm (0 is unlimited).'), 0);
		$this->add($c, 'general');
		$c = new ConfigElement_select('launch_without_apps', _('User can launch a session even if some of his published applications are not available'), _('User can launch a session even if some of his published applications are not available'), _('User can launch a session even if some of his published applications are not available'), 0);
		$c->setContentAvailable(array(0=>_('no'),1=>_('yes')));
		$this->add($c,'general','session_settings_defaults');
		$c = new ConfigElement_select('allow_shell', _('User can use a console in the session'), _('User can use a console in the session'), _('User can use a console in the session'), 0);
		$c->setContentAvailable(array(0=>_('no'),1=>_('yes')));
		$this->add($c,'general','session_settings_defaults');

		$c = new ConfigElement_select('multimedia', _('Multimedia'), _('Multimedia'), _('Multimedia'), 1);
		$c->setContentAvailable(array(0=>_('no'),1=>_('yes')));
		$this->add($c,'general','session_settings_defaults');
		$c = new ConfigElement_select('redirect_client_drives', _('Redirect client drives'), _('Redirect client drives'), _("- None: none of the client drives will be used in the OVD session<br />- Partial: Desktop and My Documents user directories will be available in the OVD session<br />- Full: all client drives (including Desktop and My Documents) will be available in the OVD session"), 'full');
		$c->setContentAvailable(array('no'=>_('no'),'partial'=>_('partial'),'full'=>_('full')));
		$this->add($c,'general','session_settings_defaults');
		$c = new ConfigElement_select('redirect_client_printers', _('Redirect client printers'), _('Redirect client printers'), _('Redirect client printers'), 1);
		$c->setContentAvailable(array(0=>_('no'),1=>_('yes')));
		$this->add($c,'general','session_settings_defaults');
		$c = new ConfigElement_select('rdp_bpp', _('RDP bpp'), _('RDP color depth'), _('RDP color depth'), 16);
		$c->setContentAvailable(array(16=>'16', 24=>'24', 32=>'32'));
		$this->add($c,'general','session_settings_defaults');
		$c = new ConfigElement_select('enhance_user_experience', _('Enhance user experience'), _('Enhance user experience: graphic effects and optimizations (It decreases performances if used in a Wide Area Network)'), _('Enhance user experience: graphic effects and optimizations (It decreases performances if used in a Wide Area Network)'), 1);
		$c->setContentAvailable(array(0=>_('no'),1=>_('yes')));
		$this->add($c,'general','session_settings_defaults');

		$c_user_profile = new ConfigElement_select('enable_profiles', _('Enable user profiles'), _('Enable user profiles'), _('Enable user profiles'), 1);
		$c_user_profile->setContentAvailable(array(0=>_('no'),1=>_('yes')));
		$this->add($c_user_profile,'general','session_settings_defaults');
		$c = new ConfigElement_select('auto_create_profile', _('Auto-create user profiles when non-existant'), _('Auto-create user profile when non-existant'), _('Auto-create user profile when nonexistant'), 1);
		$c->setContentAvailable(array(0=>_('no'),1=>_('yes')));
		$c_user_profile->addReference('1', $c);
		$this->add($c,'general','session_settings_defaults');
		$c = new ConfigElement_select('start_without_profile', _('Launch a session without a valid profile'), _('Launch a session without a valid profile'), _('Launch a session without a valid profile'), 1);
		$c->setContentAvailable(array(0=>_('no'),1=>_('yes')));
		$c_user_profile->addReference('1', $c);
		$this->add($c,'general','session_settings_defaults');
		
		$c_shared_folder = new ConfigElement_select('enable_sharedfolders', _('Enable shared folders'), _('Enable shared folders'), _('Enable shared folders'), 1);
		$c_shared_folder->setContentAvailable(array(0=>_('no'),1=>_('yes')));
		$this->add($c_shared_folder,'general','session_settings_defaults');
		$c = new ConfigElement_select('start_without_all_sharedfolders', _('Launch a session even when a shared folder\'s fileserver is missing'), _('Launch a session even when a shared folder\'s fileserver is missing'), _('Launch a session even when a shared folder\'s fileserver is missing'), 1);
		$c->setContentAvailable(array(0=>_('no'),1=>_('yes')));
		$c_shared_folder->addReference('1', $c);
		$this->add($c,'general','session_settings_defaults');

		$c = new ConfigElement_multiselect('advanced_settings_startsession', _('Forceable paramaters by users'), _('Choose Advanced Settings options you want to make available to users before they launch a session.'), _('Choose Advanced Settings options you want to make available to users before they launch a session.'), array('session_mode', 'language'));
		$c->setContentAvailable(array('session_mode' => _('session mode'), 'language' => _('language'), 'server' => _('server'), 'timeout' => _('timeout'), /*'persistent' => _('persistent'), 'shareable' => _('shareable')*/));
		$this->add($c,'general','session_settings_defaults');

		$this->addPrettyName('web_interface_settings',_('Web interface settings'));

		$c = new ConfigElement_select('show_list_users', _('Display users list'), _('Display the list of users from the corporate directory in the login box. If the list is not displayed, the user must provide his login name.'), _('Display the list of users from the corporate directory in the login box. If the list is not displayed, the user must provide his login name.'), 1);
		$c->setContentAvailable(array(0=>_('no'),1=>_('yes')));
		$this->add($c,'general','web_interface_settings');
		
		$c = new ConfigElement_select('public_webservices_access', _('Public Webservices access'), _('Authorize non authenticated requests to get information about users authorized applications or get applications icons.'), _('Authorize non authenticated requests to get information about users authorized applications or get applications icons.'), 0);
		$c->setContentAvailable(array(0=>_('no'), 1=>_('yes')));
		$this->add($c,'general','web_interface_settings');

		$this->getPrefsModules();
		$this->getPrefsEvents();
	}

	public function getPrefsModules(){
		$available_module = $this->getAvailableModule();
		// we remove all diseable modules
		foreach ($available_module as $mod2 => $sub_mod2){
			foreach ($sub_mod2 as $sub_mod_name2 => $sub_mod_pretty2){
				$enable =  call_user_func(array($mod2.'_'.$sub_mod_name2, 'enable'));
				if ($enable !== true)
					unset ($available_module[$mod2][$sub_mod_name2]);
			}

		}
		$modules_prettyname = array();
		$enabledByDefault = array();
		foreach ($available_module as $module_name => $sub_module) {
			$modules_prettyname[$module_name] = $module_name;
			if (call_user_func(array($module_name, 'enabledByDefault')))
				$enabledByDefault[] =  $module_name;
		}
		$c2 = new ConfigElement_multiselect('module_enable',_('Modules activation'), _('Choose the modules you want to enable.'), _('Choose the modules you want to enable.'), $enabledByDefault);
		$c2->setContentAvailable($modules_prettyname);
		$this->add($c2, 'general');

		foreach ($available_module as $mod => $sub_mod){
			$module_is_multiselect = call_user_func(array($mod, 'multiSelectModule'));
			if ( $module_is_multiselect) {
				$c = new ConfigElement_multiselect('enable', $mod, $mod, $mod, array());
				$c->setContentAvailable($sub_mod);
			}
			else {
				$c = new ConfigElement_select('enable', $mod, $mod, $mod, NULL);
				$c->setContentAvailable($sub_mod);
			}

			foreach ($sub_mod as $k4 => $v4) {
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
			$this->addPrettyName($mod,'Module '.$mod);

			foreach ($sub_mod as $sub_mod_name => $sub_mod_pretty){
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
		$this->addPrettyName('events', _("Events settings"));

		$c = new ConfigElement_list('mail_to', _('Email addresses to send alerts to'), _('On system alerts, emails will be sent to these addresses'), NULL, array());
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
			$c = new ConfigElement_multiselect(get_class($event), $event_name,
			                       "When $event_name is emitted",
			                       "When $event_name is emitted",
			                       array());
			$c->setContentAvailable($pretty_list);
			$this->add($c, 'events', 'active_callbacks');
		}
		$this->addPrettyName('active_callbacks', _('Activated callbacks'));
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
							$pretty_name = call_user_func(array(basename($pathinfo["dirname"]).'_'.$pathinfo["filename"],'prettyName'));
							if ( is_null($pretty_name))
								$pretty_name = $pathinfo["filename"];
							$ret[basename($pathinfo["dirname"])][$pathinfo["filename"]] = $pretty_name;
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

	public function addPrettyName($key_,$prettyName_) {
		$this->prettyName[$key_] = $prettyName_;
	}

	public static function moduleIsEnabled($name_) {
		$prefs = Preferences::getInstance();
		if (! $prefs)
			die_error('get Preferences failed', __FILE__, __LINE__);
			
		$mods_enable = $prefs->get('general', 'module_enable');
		return in_array($name_, $mods_enable);
	}
}
