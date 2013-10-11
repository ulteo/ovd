<?php
/**
 * Copyright (C) 2008-2014 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2008-2011
 * Author David PHAM-VAN <d.pham-van@ulteo.com> 2012, 2014
 * Author Julien LANGLOIS <julien@ulteo.com> 2012, 2013, 2014
 * Author David LECHEVALIER <david@ulteo.com> 2014
 * Author Vincent ROULLIER <vincent.roullier@ulteo.com> 2013
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

class Preferences_admin {
	protected $titles = array();
	protected $descriptions = array();
	protected $values = array();
	
	public $elements = array();
	
	public function __construct($element_form_=array(), $load=true){
		$this->initialize();
		
		if ($load === true) {
			$this->load();
			$this->mergeWithConfFile($element_form_);
		}
	}
	
	public function getPrettyName($item_, $path_ = '') {
		$key = '';
		if (strlen($path_)>0) {
			$key.= $path_.'.';
		}
		$key.= $item_;
		
		if (! array_key_exists($key, $this->titles)) {
			return $item_;
		}
		
		return $this->titles[$key];
	}
	
	protected function load() {
		$prefs = $_SESSION['service']->settings_get();
		if (is_null($prefs)) {
			return;
		}
		
		foreach($prefs as $key => $element) {
			$c = $this->load_element($element, $key);
			$this->insert_in_elements($c, $key, $this->elements);
		}
	}
	
	public function backup(){
		$settings = $this->export_elements($this->elements);
		
		$ret = $_SESSION['service']->settings_set($settings);
		return ($ret === true);
	}
	
	private static function insert_in_elements(&$c_, $key_, &$elements_) {
		$res = explode ('.', $key_, 2);
		if (count($res) == 1) {
			// insert here
			$elements_[$key_] = $c_;
		}
		else {
			$root = $res[0];
			
			if (! array_key_exists($root, $elements_)) {
				$elements_[$root] = array();
			}
			
			self::insert_in_element($c_, $res[1], $elements_[$root]);
		}
	}
	
	public function load_element($element_, $key_) {
		$title = $element_['id'];
		$gid = $key_;
		
		if (array_key_exists($gid, $this->titles)) {
			$title = $this->titles[$gid];
		}
		else if (array_key_exists($element_['id'], $this->titles)) {
			$title = $this->titles[$element_['id']];
		}
		
		$description = $title;
		if (array_key_exists($gid, $this->descriptions)) {
			$description = $this->descriptions[$gid];
		}
		
		switch($element_['type']) {
		// simplify that with a call function !!!
			case 'list':
				$c = new ConfigElement_list($element_['id'], $title, $description, $description, $element_['value'], $element_['default_value']);
				break;
			case 'text':
				$c = new ConfigElement_text($element_['id'], $title, $description, $description, $element_['value'], $element_['default_value']);
				break;
			case 'textarea':
				$c = new ConfigElement_textarea($element_['id'], $title, $description, $description, $element_['value'], $element_['default_value']);
				break;
			case 'sliders_loadbalancing':
				$c = new ConfigElement_sliders_loadbalancing($element_['id'], $title, $description, $description, $element_['value'], $element_['default_value']);
				break;
			case 'select':
				$c = new ConfigElement_select($element_['id'], $title, $description, $description, $element_['value'], $element_['default_value']);
				if (array_key_exists('references', $element_)) {
					$c->references = $element_['references'];
				}
				break;
			case 'multiselect':
				$c = new ConfigElement_multiselect($element_['id'], $title, $description, $description, $element_['value'], $element_['default_value']);
				break;
			case 'password':
				$c = new ConfigElement_password($element_['id'], $title, $description, $description, $element_['value'], $element_['default_value']);
				break;
			case 'inputlist':
				$c = new ConfigElement_inputlist($element_['id'], $title, $description, $description, $element_['value'], $element_['default_value']);
				break;
			case 'input':
				$c = new ConfigElement_input($element_['id'], $title, $description, $description, $element_['value'], $element_['default_value']);
				break;
			case 'hash':
				$c = new ConfigElement_dictionary($element_['id'], $title, $description, $description, $element_['value'], $element_['default_value']);
				break;
			case 'week_time_select':
				$c = new ConfigElement_week_time_select($element_['id'], $title, $description, $description, $element_['value'], $element_['default_value']);
				break;
			default:
				// todo log error unknown settings type
				return null;
		}
		
		if (array_key_exists('possible_values', $element_)) {
			$values = array();
			foreach($element_['possible_values'] as $value) {
				$value = strval($value);
				$name = $value;
				$k = $gid.'.value.'.$value;
				if (array_key_exists($k, $this->values)) {
					$name = $this->values[$k];
				}
				else if (array_key_exists(strval($value), $this->values)) {
					$name = $this->values[strval($value)];
				}
				else if (array_key_exists(strval($value), $this->titles)) {
					$name = $this->titles[strval($value)];
				}
				
				$values[$value] = $name;
			}
			
			$c->setContentAvailable($values);
		}
		
		return $c;
	}
	
	public function export_elements($elements_) {
		$ret = array();
		
		foreach ($elements_ as $container => $elements2) {
			if (is_object($elements2)) {
				$ret[$container] = $this->export_element($elements2);
			}
			else {
				$ret2 = $this->export_elements($elements2);
				foreach($ret2 as $k => $v) {
					$ret[$container.'.'.$k] = $v;
				}
			}
		}
		
		return $ret;
	}
	
	protected function export_element($element_) {
		return $element_->content;
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
			return NULL;
		}
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
	
	public function getKeys(){
		return array_keys($this->elements);
	}
	
	public function mergeWithConfFile($filecontents) {
		if (is_array($filecontents)) {
			self::merge_data($this->elements, $filecontents);
		}
	}
	
	private static function merge_data(&$elements_, &$contents_) {
		foreach($contents_ as $key => $value) {
			if (! array_key_exists($key, $elements_)) {
				continue;
			}
			
			if (is_object($elements_[$key])) {
				$element = &$elements_[$key];
				$v = $element->html2value($contents_[$key]);
				if (is_null($v)) {
					continue;
				}
				
				$element->content = $v;
			}
			else if (is_array($value)) {
				self::merge_data($elements_[$key], $value);
			}
		}
	}
	
	public function initialize(){
		$this->titles = array(
			'general' => _('General Configuration'),
			'general.system_in_maintenance' => _('System in maintenance mode'),
			'general.admin_language' => _('Administration Console language'),
			'general.log_flags' => _('Debug options list'),
			'general.cache_update_interval' => _('Cached logs update interval'),
			'general.cache_expire_time' => _('Cached logs expiry time'),
			'general.user_default_group' => _('Default user group'),
			'general.domain_integration' => _('Domain integration'),
			'general.max_items_per_page' => _('Maximum items per page'),
			'general.max_sessions_number' => _('Maximum number of running sessions'),
			'general.default_browser' => _('Default browser'),
			'general.liaison' => _('Liaisons'),
			
			'general.policy' => _('Policy for Administration Delegation'),
			'general.policy.default_policy' => _('Default policy'),
			
			'general.sql'=> _('SQL configuration'),
			'general.sql.type' => _('Database type'),
			'general.sql.host' => _('Database host address'),
			'general.sql.user' => _('Database username'),
			'general.sql.password' => _('Database password'),
			'general.sql.database' => _('Database name'),
			'general.sql.prefix' => _('Table prefix'),
			
			'general.mails_settings' => _('Email Settings'),
			'general.mails_settings.send_type' => _('Mail Server Type'),
			'general.mails_settings.send_from' => _('From'),
			'general.mails_settings.send_host' => _('Host'),
			'general.mails_settings.send_port' => _('Port'),
			'general.mails_settings.send_ssl' => _('Use SSL with SMTP'),
			'general.mails_settings.send_auth' => _('Authentication'),
			'general.mails_settings.send_username' => _('SMTP username'),
			'general.mails_settings.send_password' => _('SMTP password'),
			
			'general.slave_server_settings' => _('Slave Server Settings'),
			'general.slave_server_settings.authorized_fqdn' => _('Authorized machines (FQDN or IP - the use of wildcards (*.) is allowed)'),
			'general.slave_server_settings.disable_fqdn_check' => _('Disable reverse FQDN checking'),
			'general.slave_server_settings.use_reverse_dns' => _('Use reverse DNS for server\'s FQDN'),
			'general.slave_server_settings.action_when_as_not_ready' => _('Action when a server status becomes not ready'),
			'general.slave_server_settings.auto_recover' => _('Auto-recover server'),
			'general.slave_server_settings.remove_orphan' => _('Remove orphan applications when an Application Server is deleted'),
			'general.slave_server_settings.auto_register_new_servers' => _('Auto register new servers'),
			'general.slave_server_settings.auto_switch_new_servers_to_production' => _('Auto switch new servers to production mode'),
			'general.slave_server_settings.use_max_sessions_limit' => _('When an Application Server has reached its "max sessions" limit, disable session launch on it ?'),
			'general.slave_server_settings.load_balancing_aps' => _('Load Balancing policy for Application Servers'),
			'general.slave_server_settings.load_balancing_fs' => _('Load Balancing policy for File Servers'),
			
			'general.remote_desktop_settings' => _('Remote Desktop Settings'),
			'general.remote_desktop_settings.enabled' => _('Enable Remote Desktop'),
			'general.remote_desktop_settings.desktop_icons' => _('Show icons on user desktop'),
			'general.remote_desktop_settings.allow_external_applications' => _('Allow external applications in Desktop'),
			'general.remote_desktop_settings.desktop_type' => _('Desktop type'),
			'general.remote_desktop_settings.allowed_desktop_servers' => _('Servers which are allowed to start desktop'),
			'general.remote_desktop_settings.authorize_no_desktop' => _('Authorize to launch a desktop session without desktop process'),
			
			'general.remote_applications_settings' => _('Remote Application Settings'),
			'general.remote_applications_settings.enabled' => _('Enable Remote Applications'),
		
			'general.session_settings_defaults' => _('Session settings'),
			'general.session_settings_defaults.session_mode' => _('Default mode for session'),
			'general.session_settings_defaults.language' => _('Default language for session'),
			'general.session_settings_defaults.timeout' => _('Default timeout for session'),
			'general.session_settings_defaults.launch_without_apps' => _('User can launch a session even if some of his published applications are not available'),
			'general.session_settings_defaults.allow_shell' => _('User can use a console in the session'),
			'general.session_settings_defaults.bypass_servers_restrictions' => _('Bypass server restrictions'),
			'general.session_settings_defaults.use_known_drives' => _('Use known drives'),
			'general.session_settings_defaults.multimedia' => _('Multimedia'),
			'general.session_settings_defaults.redirect_client_drives' => _('Redirect client drives'),
			'general.session_settings_defaults.redirect_client_printers' => _('Redirect client printers'),
			'general.session_settings_defaults.redirect_smartcards_readers' => _('Redirect Smart card readers'),
			'general.session_settings_defaults.rdp_bpp' => _('RDP bpp'),
			'general.session_settings_defaults.use_local_ime' => _('use local ime integration'),
			'general.session_settings_defaults.enhance_user_experience' => _('Enhance user experience'),
			
			'general.session_settings_defaults.persistent' => _('Sessions are persistent'),
			'general.session_settings_defaults.followme' => _('Follow me'),
			'general.session_settings_defaults.enable_profiles' => _('Enable user profiles'),
			'general.session_settings_defaults.auto_create_profile' => _('Auto-create user profiles when non-existent'),
			'general.session_settings_defaults.quota' => _('Quota assigned to the profile'),
			'general.session_settings_defaults.profile_mode' => _('Profile integration used in the session'),
			'general.session_settings_defaults.start_without_profile' => _('Launch a session without a valid profile'),
			'general.session_settings_defaults.enable_sharedfolders' => _('Enable shared folders'),
			'general.session_settings_defaults.start_without_all_sharedfolders' => _('Launch a session even when a shared folder\'s fileserver is missing'),
			'general.session_settings_defaults.can_force_sharedfolders' => _('Allow user to force shared folders'),
			'general.session_settings_defaults.advanced_settings_startsession' => _('User selectable parameters'),
			'general.session_settings_defaults.time_restriction' => _('Time restriction'),
			
			'general.web_interface_settings' => _('Web Interface Settings'),
			'general.web_interface_settings.show_list_users' => _('Display user list'),
			'general.web_interface_settings.public_webservices_access' => _('Public Web Services access'),
			
			'activedirectory' => _('Active Directory'),
			'ldap' => _('Lightweight Directory Access Protocol (LDAP)'),
			'sql_external' => _('MySQL external'),
			'unix' => _('Unix'),
			
			'module_enable' => _('Module activation'),
			
			'ApplicationDB.enable' => 'ApplicationDB',
			'ApplicationsGroupDB.enable' => 'ApplicationsGroupDB',
			'AuthMethod.enable' => 'AuthMethod',
			'ProfileDB.enable' => 'ProfileDB',
			'SessionManagement.enable' => 'SessionManagement',
			'SharedFolderDB.enable' => 'SharedFolderDB',
			'UserDB.enable' => 'UserDB',
			'UserGroupDB.enable' => 'UserGroupDB',
			'UserGroupDBDynamic.enable' => 'UserGroupDBDynamic',
			'UserGroupDBDynamicCached.enable' => 'UserGroupDBDynamicCached',
			
			'UserGroupDB.activedirectory.match' => _('Matching'),
			'UserGroupDB.activedirectory.use_child_group' => _('Use child groups'),
			
			'UserGroupDB.ldap.match' => _('Matching'),
			'UserGroupDB.ldap.filter' => _('Filter'),
			
			'UserGroupDB.sql_external.host' => _('Server host address'),
			'UserGroupDB.sql_external.user' => _('User login'),
			'UserGroupDB.sql_external.password' => _('User password'),
			'UserGroupDB.sql_external.database' => _('Database name'),
			'UserGroupDB.sql_external.table' => _('Database User Group table name'),
			'UserGroupDB.sql_external.match' => _('Matching'),
			
			'UserDB.sql_external.host' => _('Server host address'),
			'UserDB.sql_external.user' => _('User login'),
			'UserDB.sql_external.password' => _('User password'),
			'UserDB.sql_external.database' => _('Database name'),
			'UserDB.sql_external.table' => _('Table of users'),
			'UserDB.sql_external.match' => _('Matching'),
			'UserDB.sql_external.hash_method' => _('Hash method'),
			
			'UserDB.sql_external.host' => _('The address of your MySQL server.'),
			'UserDB.sql_external.user' => _('The user login that must be used to access the database (to list users accounts).'),
			'UserDB.sql_external.password' => _('The user password that must be used to access the database (to list users accounts).'),
			'UserDB.sql_external.database' => _('The name of the database.'),
			
			'UserDB.ldap.hosts' => _('Server host address'),
			'UserDB.ldap.port' => _('Server port'),
			'UserDB.ldap.use_ssl' => _('Use SSL'),
			'UserDB.ldap.login' => _('User login'),
			'UserDB.ldap.password' => _('User password'),
			'UserDB.ldap.options' => _('Options given to ldap object'),
			'UserDB.ldap.match' => _('Matching'),
			'UserDB.ldap.filter' => _('Filter (optional)'),
			'UserDB.ldap.extra' => _('extra'),
			
			'UserDB.activedirectory.hosts' => _('Server host address'),
			'UserDB.activedirectory.domain' => _('Domain name'),
			'UserDB.activedirectory.login' => _('Administrator DN'),
			'UserDB.activedirectory.password' => _('Administrator password'),
			'UserDB.activedirectory.port' => _('Server port'),
			'UserDB.activedirectory.use_ssl' => _('Use SSL'),
			'UserDB.activedirectory.match' => _('match'),
			'UserDB.activedirectory.accept_expired_password' => _('Accept expired password'),
			
			'AuthMethod.CAS.user_authenticate_cas_server_url' => _('CAS server URL'),
			
			'AuthMethod.RemoteUser.user_authenticate_trust' => _('SERVER variable for SSO'),
			'AuthMethod.RemoteUser.remove_domain_if_exists' => _('Remove domain if exists'),
			
			'AuthMethod.SAML2.idp_url' => _('Identity provider url'),
			'AuthMethod.SAML2.idp_fingerprint' => _('Certificate fingerprint'),
			'AuthMethod.SAML2.idp_cert' => _('Certificate'),

			'AuthMethod.Token.url' => _('Token validation URL'),
			'AuthMethod.Token.user_node_name' => _('Token XML user node name'),
			'AuthMethod.Token.login_attribute_name' => _('Token XML login attribute name'),
			
			'SessionManagement.internal.generate_aps_login' => _("Which login should be used for the ApplicationServer's generated user?"), 
			'SessionManagement.internal.generate_aps_password' => _("Which password should be used for the ApplicationServer's generated user?"),
			
			'SessionManagement.novell.dlu' => _('Manage users by ZENworks DLU instead of native method'),
			
			'SessionManagement.localusers' => _('Override password'),
		);
		
		$this->descriptions = array(
			'general.log_flags' => _('Select debug options you want to enable.'),
			'general.max_items_per_page' => _('The maximum number of items that can be displayed.'),
			'general.max_sessions_number' => _('The maximum number of sessions that can be started on the farm (0 is unlimited).'),
			
			'general.sql.type' => _('The type of your database.'),
			'general.sql.host' => _('The address of your database host. This database contains adminstration console data. Example: localhost or db.mycorporate.com.'),
			'general.sql.user' => _('The username that must be used to access the database.'),
			'general.sql.password' => _('The user password that must be used to access the database.'),
			'general.sql.database' => _('The name of the database.'),
			'general.sql.prefix' => _('The table prefix for the database.'),
			
			'general.slave_server_settings.disable_fqdn_check' => _('Enable this option if you don\'t want to check that the result of the reverse FQDN address fits the one that was registered.'),
			'general.slave_server_settings.use_reverse_dns' => _('Try to identify the server using the reverse DNS record associated to the remote address.'),
			'general.slave_server_settings.auto_recover' => _('When a server status is down or broken, and it is sending monitoring, try to switch it back to ready ?'),
			'general.remote_desktop_settings.allowed_desktop_servers' => _('An empty list means all servers can host a desktop (no restriction on desktop server choice)'),
			'general.remote_desktop_settings.authorize_no_desktop' => _('Useful for web integration starting a maximised application as only windows into the remote screen'),
			
			'general.session_settings_defaults.bypass_servers_restrictions' => _('If there is no server available for a session according to server restrictions and this setting is enabled, the system will try to use servers without taking care of server restrictions (so it become as a priority system)'),
			'general.session_settings_defaults.use_known_drives' => _('Provide file access optimization when using common network drives between client & Application Servers (open the file on server side instead of sending it from client using RDP disk redirection)'),
			'general.session_settings_defaults.redirect_client_drives' => _("- None: none of the client drives will be used in the OVD session<br />- Partial: Desktop and My Documents user directories will be available in the OVD session<br />- Full: all client drives (including Desktop and My Documents) will be available in the OVD session"),
			'general.session_settings_defaults.rdp_bpp' => _('RDP color depth'),
			'general_session_settings_defaults_use_local_ime' => _('When you are using asian keyboard (mostly japanese, korean and chinese), \'use_local_ime\' offers a better integration with your local input method engine(IME) regarding candidates list position and input method status.'),
			'general.session_settings_defaults.enhance_user_experience' => _('Enhance user experience: graphic effects and optimizations (It decreases performances if used in a Wide Area Network)'),
			'general.session_settings_defaults.advanced_settings_startsession' =>  _('Choose Advanced Settings options you want to make available to users before they launch a session.'),
			
			'general.web_interface_settings.show_list_users' => _('Display the list of users from the corporate directory in the login box. If the list is not displayed, the user must provide his login name.'),
			'general.web_interface_settings.public_webservices_access' => _('Authorize non authenticated requests to get information about users authorized applications or get applications icons.'),
			
			'module.enable' => _('Choose the modules you want to enable.'),
			
			'UserGroupDB.activedirectory.use_child_group' => _('Use child groups (works with AD up to 2008r2)'),
			
			'UserGroupDB.ldap.filter' => sprintf(_('Filter (example: %s)'), '<em>(objectClass=posixGroup)</em>'),
			
			'UserGroupDB.sql_external.host' => _('The address of your MySQL server.'),
			'UserGroupDB.sql_external.user' => _('The user login that must be used to access the database (to list users groups).'),
			'UserGroupDB.sql_external.password' => _('The user password that must be used to access the database (to list users groups).'),
			'UserGroupDB.sql_external.database' => _('The name of the database.'),
			'UserGroupDB.sql_external.table' => _('The name of the database table which contains the User Groups'),
			
			'UserDB.ldap.hosts' => _('The address of your LDAP server.'),
			'UserDB.ldap.port' => _('The port number used by your LDAP server.'),
			'UserDB.ldap.use_ssl' => _('Use SSL (ldaps://)'),
			'UserDB.ldap.login' => _('The user login that must be used to access the database (to list users accounts).'),
			'UserDB.ldap.password' => _('The user password that must be used to access the database (to list users accounts).'),
			'UserDB.ldap.filter' => _('Filter, example (&(distinguishedname=mike*)(uid=42*))'),
			
			'UserDB.activedirectory.hosts' => _('The address of your Active Directory server.'),
			'UserDB.activedirectory.domain' => _('Domain name used by Active Directory'),
			'UserDB.activedirectory.login' => _('The user login that must be used to access the database (to list users accounts).'),
			'UserDB.activedirectory.password' => _('The user password that must be used to access the database (to list users accounts).'),
			'UserDB.activedirectory.port' => _('The port number used by your LDAP server.'),
			'UserDB.activedirectory.use_ssl' => _('Use SSL (ldaps://)'),
			'UserDB.activedirectory.accept_expired_password' => _('Authorize a user connection even if the password has expired, to have the Windows server perform the password renew process'),
			
			'AuthMethod.Token.url' => _('If a token argument is sent to <i>startsession</i>, the system performs a user login by requesting the token validation URL.<br /><br />Enter the URL to request if a token argument is sent to <i>startsession</i> instead of login/password.<br /><br />The special string <b>%TOKEN%</b> needs to be set because it will be replaced by the token argument when the URL is requested.'),
			'AuthMethod.Token.user_node_name' => _('The id of the XML node that contains the user login'),
			'AuthMethod.Token.login_attribute_name' => _('The name of the XML attribute that contains the user login (in the previously defined XML node)'),
			'AuthMethod.Token2.tokens' => _('Token list: the first column is supposed to be the token. the second is the user login associated with this token'),
		);
		
		$this->values = array(
			-1 => _('None'),
			0 => _('no'), // todo change to false instead of 0
			1 => _('yes'), // todo change to true instead of 1
			
			'auto' => _('Autodetect'),
			'ar_AE' => 'العربي - Arabic',
			'ca_ES' => 'Català - Catalan',
			'bg_BG' => 'Български - Bulgarian',
			'da-dk' => 'Dansk - Danish',
			'de_DE' => 'Deutsch - German',
			'el_GR' => 'Ελληνικά - Greek',
			'en_GB' => 'English',
			'es_ES' => 'Español - Spanish',
			'eu_ES' => 'Euskara - Basque',
			'fa_IR' => 'فارسی - Persian',
			'fi_FI' => 'Suomi - Finnish',
			'fr_FR' => 'Français - French',
			'he_IL' => 'Hebrew - עברית',
			'hu_HU' => 'Magyar - Hungarian',
			'id_ID' => 'Bahasa Indonesia - Indonesian',
			'is_IS' => 'Íslenska - Icelandic',
			'it_IT' => 'Italiano - Italian',
			'ja_JP' => '日本語 - Japanese',
			'nb_NO' => 'Norsk (bokmål) - Norwegian (Bokmal)',
			'nl_NL' => 'Nederlands - Dutch',
			'pl_PL' => 'Polski - Polish',
			'pt_BR' => 'Português (Brasil) - Portuguese (Brazil)',
			'sk_SK' => 'Slovenčina - Slovak',
			'sl' => 'Slovenščina - Slovenian',
			'ro_RO' => 'Română - Romanian',
			'ru_RU' => 'Русский - Russian',
			'sv_SE' => 'Svenska - Swedish',
			'zh_CN' => '中文简体 - Chinese',
			
			'debug' => _('debug'),
			'info' => _('info'),
			'warning' => _('warning'),
			'error' => _('error'),
			'critical' => _('critical'),
			
			30 => _('30 seconds'),
			60 => _('1 minute'),
			120 => _('2 minutes'),
			300 => _('5 minutes'),
			600 => _('10 minutes'),
			900 => _('15 minutes'),
			1800 => _('30 minutes'),
			3600 => _('1 hour'),
			7200 => _('2 hours'),
			18000 => _('5 hours'),
			43200 => _('12 hours'),
			86400=>_('1 day'),
			172800 => _('2 days'),
			(86400*7) => _('1 week'),
			(86400*31) => _('1 month'),
			(86400*366) => _('1 year'),
			(2764800) => _('1 year'), // to change !
			
			'internal' => _('Internal'),
			'microsoft' => _('Microsoft'),
			'novell' => _('Novell'),
			
			'canUseAdminPanel' => _('use Admin Console'),
			'viewServers' => _('view Servers'),
			'manageServers' => _('manage Servers'),
			'viewSharedFolders' => _('view Shared Folders'),
			'manageSharedFolders' => _('manage Shared Folders'),
			'viewUsers' => _('view Users'),
			'manageUsers' => _('manage Users'),
			'viewUsersGroups' => _('view User Groups'),
			'manageUsersGroups' => _('manage User Groups'),
			'viewApplications' => _('view Applications'),
			'manageApplications' => _('manage Applications'),
			'viewApplicationsGroups' => _('view Application Groups'),
			'manageApplicationsGroups' => _('manage Application Groups'),
			'viewPublications' => _('view Publications'),
			'managePublications' => _('manage Publications'),
			'viewConfiguration' => _('view Configuration'),
			'manageConfiguration' => _('manage Configuration'),
			'viewStatus' => _('view Status'),
			'manageSession' => _('manage Sessions'),
			'manageReporting' => _('manage Reporting'),
			'viewSummary' => _('view Summary'),
			'viewNews' => _('view News'),
			'manageNews' => _('manage News'),
			'manageScripts' => _('manage Scripts'),
			'viewScripts' => _('view Scripts'),
			'viewScriptsGroups' => _('view Script Groups'),
			'manageScriptsGroups' => _('manage Script Groups'),
			
			'mysql' => _('MySQL'),
			'sql' => _('MySQL'),
			
			'general.mails_settings.send_type.value.mail' => _('Local'),
			'general.mails_settings.send_type.value.smtp' => _('SMTP server'),
			
			'desktop' => _('Desktop'),
			'applications' => _('Applications'),
			
			'general.slave_server_settings.action_when_as_not_ready.value.0' => _('Do nothing'),
			'general.slave_server_settings.action_when_as_not_ready.value.1' => _('Switch to maintenance'),
			
			'general.remote_desktop_settings.desktop_type.value.any' => _('Any'),
			'general.remote_desktop_settings.desktop_type.value.linux' => _('Linux'),
			'general.remote_desktop_settings.desktop_type.value.windows' => _('Windows'),
			
			'no' => _('no'),
			'partial' => _('partial'),
			'full' => _('full'),
			
			'general.session_settings.advanced_settings.startsession.value.session_mode' =>  _('session mode'),
			'general.session_settings.advanced_settings.startsession.value.session_language' =>  _('language'),
			'general.session_settings.advanced_settings.startsession.value.server' =>  _('server'),
			'general.session_settings.advanced_settings.startsession.value.timeout' =>  _('timeout'),
			'general.session_settings.advanced_settings.startsession.value.persistent' =>  _('persistent'),
			
			'AuthMethod.enable.value_Auto' => _('Auto authentication'),
			'AuthMethod.enable.value_CAS' => _('CAS authentication'),
			'AuthMethod.enable.value_RemoteUser' => _('RemoteUser authentication'),
			'AuthMethod.enable.value_Token' => _('Token authentication'),
			'AuthMethod.enable.value_password' => _('Login/Password authentication'),
			
			'SessionManagement.internal.generate_aps_login.value_0' => _('Use given login'),
			'SessionManagement.internal.generate_aps_login.value_1' => _('Auto-generate'),
			
			'SessionManagement.internal.generate_aps_password.value.0' => _('Use given password'),
			'SessionManagement.internal.generate_aps_password.value.1' => _('Auto-generate'),
			
		);
	}
}
