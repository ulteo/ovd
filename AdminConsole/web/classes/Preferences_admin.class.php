<?php
/**
 * Copyright (C) 2008-2012 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2008-2011
 * Author David PHAM-VAN <d.pham-van@ulteo.com> 2012
 * Author Julien LANGLOIS <julien@ulteo.com> 2012
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
			$key.= $path_.'_';
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
		
		foreach($prefs as $key => $elements) {
			$this->elements[$key] = $this->load_elements($elements, $key);
		}
	}
	
	public function backup(){
		$settings = $this->export_elements($this->elements);
		
		$ret = $_SESSION['service']->settings_set($settings);
		return ($ret === true);
	}
	
	protected function load_elements($elements, $path) {
		$items = array();
		
		foreach($elements as $element_id => $element) {
			if ($element_id == 'is_node_tree')
				continue;
			
			if (array_key_exists('is_node_tree', $element)) {
				$item = $this->load_elements($element , $path.'_'.$element_id);
			}
			else {
				$item = $this->load_element($element, $path);
				if (is_null($item))
					continue;
			}
			
			$items[$element_id] = $item;
		}
		
		return $items;
	}
	
	public function load_element($element_, $path_) {
		$title = $element_['id'];
		$gid = $path_.'_'.$element_['id'];
		
		if (array_key_exists($gid, $this->titles)) {
			$title = $this->titles[$path_.'_'.$element_['id']];
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
			default:
				// todo log error unknown settings type
				return null;
		}
		
		if (array_key_exists('possible_values', $element_)) {
			$values = array();
			foreach($element_['possible_values'] as $value) {
				$name = $value;
				
				$k = $gid.'_value_'.$value;
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
				$c = $this->export_element($elements2);
			}
			else {
				$c = $this->export_elements($elements2);
			}
			
			$ret[$container] = $c;
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
		$this->titles = array(
			'general' => _('General configuration'),
			'general_system_in_maintenance' => _('System on maintenance mode'),
			'general_admin_language' => _('Administration console language'),
			'general_log_flags' => _('Debug options list'),
			'general_cache_update_interval' => _('Cache logs update interval'),
			'general_cache_expire_time' => _('Cache logs expiry time'),
			'general_user_default_group' => _('Default user group'),
			'general_domain_integration' => _('Domain integration'),
			'general_max_items_per_page' => _('Maximum items per page'),
			'general_default_browser' => _('Default browser'),
			'general_liaison' => _('Liaisons'),
			
			'general_policy' => _('Policy for administration delegation'),
			'general_policy_default_policy' => _('Default policy'),
			
			'general_sql'=> _('SQL configuration'),
			'general_sql_type' => _('Database type'),
			'general_sql_host' => _('Database host address'),
			'general_sql_user' => _('Database username'),
			'general_sql_password' => _('Database password'),
			'general_sql_database' => _('Database name'),
			'general_sql_prefix' => _('Table prefix'),
			
			'general_mails_settings' => _('Email settings'),
			'general_mails_settings_send_type' => _('Mail server type'),
			'general_mails_settings_send_from' => _('From'),
			'general_mails_settings_send_host' => _('Host'),
			'general_mails_settings_send_port' => _('Port'),
			'general_mails_settings_send_ssl' => _('Use SSL with SMTP'),
			'general_mails_settings_send_auth' => _('Authentication'),
			'general_mails_settings_send_username' => _('SMTP username'),
			'general_mails_settings_send_password' => _('SMTP password'),
			
			'general_slave_server_settings' => _('Slave Server settings'),
			'general_slave_server_settings_authorized_fqdn' => _('Authorized machines (FQDN or IP - the use of wildcards (*.) is allowed)'),
			'general_slave_server_settings_disable_fqdn_check' => _('Disable reverse FQDN checking'),
			'general_slave_server_settings_use_reverse_dns' => _('Use reverse DNS for server\'s FQDN'),
			'general_slave_server_settings_action_when_as_not_ready' => _('Action when a server status is not ready anymore'),
			'general_slave_server_settings_auto_recover' => _('Auto-recover server'),
			'general_slave_server_settings_remove_orphan' => _('Remove orphan applications when the application server is deleted'),
			'general_slave_server_settings_auto_register_new_servers' => _('Auto register new servers'),
			'general_slave_server_settings_auto_switch_new_servers_to_production' => _('Auto switch new servers to production mode'),
			'general_slave_server_settings_use_max_sessions_limit' => _('When an Application Server have reached its "max sessions" limit, disable session launch on it ?'),
			'general_slave_server_settings_load_balancing_aps' => _('Load Balancing policy for Application Servers'),
			'general_slave_server_settings_load_balancing_fs' => _('Load Balancing policy for File Servers'),
			
			'general_remote_desktop_settings' => _('Remote Desktop settings'),
			'general_remote_desktop_settings_enabled' => _('Enable Remote Desktop'),
			'general_remote_desktop_settings_desktop_icons' => _('Show icons on user desktop'),
			'general_remote_desktop_settings_allow_external_applications' => _('Allow external applications in Desktop'),
			'general_remote_desktop_settings_desktop_type' => _('Desktop type'),
			'general_remote_desktop_settings_allowed_desktop_servers' => _('Servers which are allowed to start desktop'),
			'general_remote_desktop_settings_authorize_no_desktop' => _('Authorize to launch a desktop session without desktop process'),
			
			'general_remote_applications_settings' => _('Remote Applications settings'),
			'general_remote_applications_settings_enabled' => _('Enable Remote Applications'),
		
			'general_session_settings_defaults' => _('Sessions settings'),
			'general_session_settings_defaults_session_mode' => _('Default mode for session'),
			'general_session_settings_defaults_language' => _('Default language for session'),
			'general_session_settings_defaults_timeout' => _('Default timeout for session'),
			'general_session_settings_defaults_max_sessions_number' => _('Maximum number of running sessions'),
			'general_session_settings_defaults_launch_without_apps' => _('User can launch a session even if some of his published applications are not available'),
			'general_session_settings_defaults_allow_shell' => _('User can use a console in the session'),
			'general_session_settings_defaults_use_known_drives' => _('Use known drives'),
			'general_session_settings_defaults_multimedia' => _('Multimedia'),
			'general_session_settings_defaults_redirect_client_drives' => _('Redirect client drives'),
			'general_session_settings_defaults_redirect_client_printers' => _('Redirect client printers'),
			'general_session_settings_defaults_redirect_smartcards_readers' => _('Redirect Smart card readers'),
			'general_session_settings_defaults_rdp_bpp' => _('RDP bpp'),
			'general_session_settings_defaults_enhance_user_experience' => _('Enhance user experience'),
			
			'general_session_settings_defaults_persistent' => _('Sessions are persistent'),
			'general_session_settings_defaults_enable_profiles' => _('Enable user profiles'),
			'general_session_settings_defaults_auto_create_profile' => _('Auto-create user profiles when non-existant'),
			'general_session_settings_defaults_start_without_profile' => _('Launch a session without a valid profile'),
			'general_session_settings_defaults_enable_sharedfolders' => _('Enable shared folders'),
			'general_session_settings_defaults_start_without_all_sharedfolders' => _('Launch a session even when a shared folder\'s fileserver is missing'),
			'general_session_settings_defaults_can_force_sharedfolders' => _('Allow user to force shared folders'),
			'general_session_settings_defaults_advanced_settings_startsession' => _('Forceable paramaters by users'),
			
			'general_web_interface_settings' => _('Web interface settings'),
			'general_web_interface_settings_show_list_users' => _('Display users list'),
			'general_web_interface_settings_public_webservices_access' => _('Public Webservices access'),
			
			'activedirectory' => _('Active Directory'),
			'ldap' => _('Lightweight Directory Access Protocol (LDAP)'),
			'sql_external' => _('MySQL external'),
			'unix' => _('Unix'),
			
			'module_enable' => _('Modules activation'),
			
			'ApplicationDB_enable' => 'ApplicationDB',
			'ApplicationsGroupDB_enable' => 'ApplicationsGroupDB',
			'AuthMethod_enable' => 'AuthMethod',
			'ProfileDB_enable' => 'ProfileDB',
			'SessionManagement_enable' => 'SessionManagement',
			'SharedFolderDB_enable' => 'SharedFolderDB',
			'UserDB_enable' => 'UserDB',
			'UserGroupDB_enable' => 'UserGroupDB',
			'UserGroupDBDynamic_enable' => 'UserGroupDBDynamic',
			'UserGroupDBDynamicCached_enable' => 'UserGroupDBDynamicCached',
			
			'UserGroupDB_ldap_memberof' => _('LDAP using memberOf'),
			'UserGroupDB_ldap_memberof_match' => _('Matching'),
			'UserGroupDB_ldap_memberof_use_child_group' => _('Use child groups'),
			
			'UserGroupDB_ldap_posix' => _('LDAP using Posix groups'),
			'UserGroupDB_ldap_posix_group_dn' => _('Group Branch DN'),
			'UserGroupDB_ldap_posix_match' => _('Matching'),
			'UserGroupDB_ldap_posix_filter' => _('Filter (optional)'),
			
			'UserGroupDB_sql_external_host' => _('Server host address'),
			'UserGroupDB_sql_external_user' => _('User login'),
			'UserGroupDB_sql_external_password' => _('User password'),
			'UserGroupDB_sql_external_database' => _('Database name'),
			'UserGroupDB_sql_external_table' => _('Database users groups table name'),
			'UserGroupDB_sql_external_match' => _('Matching'),
			
			'UserDB_sql_external_host' => _('Server host address'),
			'UserDB_sql_external_user' => _('User login'),
			'UserDB_sql_external_password' => _('User password'),
			'UserDB_sql_external_database' => _('Database name'),
			'UserDB_sql_external_table' => _('Table of users'),
			'UserDB_sql_external_match' => _('Matching'),
			'UserDB_sql_external_hash_method' => _('Hash method'),
			
			'UserDB_sql_external_host' => _('The address of your MySQL server.'),
			'UserDB_sql_external_user' => _('The user login that must be used to access the database (to list users accounts).'),
			'UserDB_sql_external_password' => _('The user password that must be used to access the database (to list users accounts).'),
			'UserDB_sql_external_database' => _('The name of the database.'),
			
			'UserDB_ldap_hosts' => _('Server host address'),
			'UserDB_ldap_port' => _('Server port'),
			'UserDB_ldap_login' => _('User login'),
			'UserDB_ldap_password' => _('User password'),
			'UserDB_ldap_options' => _('Options given to ldap object'),
			'UserDB_ldap_match' => _('Matching'),
			'UserDB_ldap_filter' => _('Filter (optional)'),
			'UserDB_ldap_extra' => _('extra'),
			
			'UserDB_activedirectory_hosts' => _('Server host address'),
			'UserDB_activedirectory_domain' => _('Domain name'),
			'UserDB_activedirectory_login' => _('Administrator DN'),
			'UserDB_activedirectory_password' => _('Administrator password'),
			'UserDB_activedirectory_match' => _('match'),
			'UserDB_activedirectory_accept_expired_password' => _('Accept expired password'),
			
			'AuthMethod_CAS_user_authenticate_cas_server_url' => _('CAS server URL'),
			
			'AuthMethod_RemoteUser_user_authenticate_trust' => _('SERVER variable for SSO'),
			'AuthMethod_RemoteUser_remove_domain_if_exists' => _('Remove domain if exists'),
			
			'AuthMethod_Token_url' => _('Token validation URL'),
			'AuthMethod_Token_user_node_name' => _('Token XML user node name'),
			'AuthMethod_Token_login_attribute_name' => _('Token XML login attribute name'),
			
			'SessionManagement_internal_generate_aps_login' => _("Which login should be used for the ApplicationServer's generated user?"), 
			
			'SessionManagement_novell_dlu' => _('Manage users by ZENworks DLU instead of native method'),
		);
		
		$this->descriptions = array(
			'log_flags' => _('Select debug options you want to enable.'),
			'max_items_per_page' => _('The maximum number of items that can be displayed.'),
			
			'general_sql_type' => _('The type of your database.'),
			'general_sql_host' => _('The address of your database host. This database contains adminstration console data. Example: localhost or db.mycorporate.com.'),
			'general_sql_user' => _('The username that must be used to access the database.'),
			'general_sql_password' => _('The user password that must be used to access the database.'),
			'general_sql_database' => _('The name of the database.'),
			'general_sql_prefix' => _('The table prefix for the database.'),
			
			'general_slave_server_settings_disable_fqdn_check' => _('Enable this option if you don\'t want to check that the result of the reverse FQDN address fits the one that was registered.'),
			'general_slave_server_settings_use_reverse_dns' => _('Try to identify the server using the reverse DNS record associated to the remote address.'),
			'general_slave_server_settings_auto_recover' => _('When a server status is down or broken, and it is sending monitoring, try to switch it back to ready ?'),
			'general_remote_desktop_settings_allowed_desktop_servers' => _('An empty list means all servers can host a desktop (no restriction on desktop server choice)'),
			'general_remote_desktop_settings_authorize_no_desktop' => _('Usefull for web integration starting a maximised application as only windows into the remote screen'),
			
			'general_session_settings_defaults_max_sessions_number' => _('The maximum number of session that can be started on the farm (0 is unlimited).'),
			'general_session_settings_defaults_use_known_drives' => _('Provide file access optimization when using common network drives between client & Application Servers (open the file on server side instead of sending it from client using RDP disk redirection)'),
			'general_session_settings_defaults_redirect_client_drives' => _("- None: none of the client drives will be used in the OVD session<br />- Partial: Desktop and My Documents user directories will be available in the OVD session<br />- Full: all client drives (including Desktop and My Documents) will be available in the OVD session"),
			'general_session_settings_defaults_rdp_bpp' => _('RDP color depth'),
			'general_session_settings_defaults_enhance_user_experience' => _('Enhance user experience: graphic effects and optimizations (It decreases performances if used in a Wide Area Network)'),
			'general_session_settings_defaults_advanced_settings_startsession' =>  _('Choose Advanced Settings options you want to make available to users before they launch a session.'),
			
			'general_web_interface_settings_show_list_users' => _('Display the list of users from the corporate directory in the login box. If the list is not displayed, the user must provide his login name.'),
			'general_web_interface_settings_public_webservices_access' => _('Authorize non authenticated requests to get information about users authorized applications or get applications icons.'),
			
			'module_enable' => _('Choose the modules you want to enable.'),
			
			'UserGroupDB_ldap_posix_group_dn' => _('Use LDAP users groups using Posix groups, Group Branch DN:'),
			'UserGroupDB_ldap_posix_filter' => sprintf(_('Filter (example: %s)'), '<em>(objectClass=posixGroup)</em>'),
			
			'UserGroupDB_sql_external_host' => _('The address of your MySQL server.'),
			'UserGroupDB_sql_external_user' => _('The user login that must be used to access the database (to list users groups).'),
			'UserGroupDB_sql_external_password' => _('The user password that must be used to access the database (to list users groups).'),
			'UserGroupDB_sql_external_database' => _('The name of the database.'),
			'UserGroupDB_sql_external_table' => _('The name of the database table which contains the users groups'),
			
			'UserDB_ldap_hosts' => _('The address of your LDAP server.'),
			'UserDB_ldap_port' => _('The port number used by your LDAP server.'),
			'UserDB_ldap_login' => _('The user login that must be used to access the database (to list users accounts).'),
			'UserDB_ldap_password' => _('The user password that must be used to access the database (to list users accounts).'),
			'UserDB_ldap_filter' => _('Filter, example (&(distinguishedname=mike*)(uid=42*))'),
			
			'UserDB_activedirectory_hosts' => _('The address of your Active Directory server.'),
			'UserDB_activedirectory_domain' => _('Domain name used by Active Directory'),
			'UserDB_activedirectory_login' => _('The user login that must be used to access the database (to list users accounts).'),
			'UserDB_activedirectory_password' => _('The user password that must be used to access the database (to list users accounts).'),
			'UserDB_activedirectory_accept_expired_password' => _('Authorize a user connection even if the password has expired, to have the Windows server perform the password renew process'),
			
			'AuthMethod_Token_url' => _('If a token argument is sent to startsession, the system tries to get an user login by requesting the token validation url.<br /><br />Put here the url to request if a token argument is sent to <i>startsession</i> instead of login/password.<br /><br />The special string <b>%TOKEN%</b> needs to be set because it\'s replaced by the token argument when the URL is requested.'),
			'AuthMethod_Token_user_node_name' => _('The id of the XML node that contains the user login'),
			'AuthMethod_Token_login_attribute_name' => _('The name of the XML attribute which contains the user login (in the previously defined XML node)'),
		);
		
		$this->values = array(
			-1 => _('None'),
			0 => _('no'), // todo change to false instead of 0
			1 => _('yes'), // todo change to true instead of 1
			
			'auto' => _('Autodetect'),
			'ar_AE' => 'العربي - Arabic',
			'bg_BG' => 'Български - Bulgarian',
			'da-dk' => 'Dansk - Danish',
			'de_DE' => 'Deutsch - German',
			'el_GR' => 'Ελληνικά - Greek',
			'en_GB' => 'English',
			'es_ES' => 'Español - Spanish',
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
			'ro_RO' => 'Română - Romanian',
			'ru_RU' => 'Русский - Russian',
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
			86400=>_('A day'),
			172800 => _('2 days'),
			(86400*7) => _('A week'),
			(86400*31) => _('A month'),
			(86400*366) => _('A year'),
			(2764800) => _('A year'), // to change !
			
			'internal' => _('Internal'),
			'microsoft' => _('Microsoft'),
			'novell' => _('Novell'),
			
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
			'manageSession' => _('manage session'),
			'manageReporting' => _('manage Reporting'),
			'viewSummary' => _('view Summary'),
			'viewNews' => _('view News'),
			'manageNews' => _('manage News'),
			
			'mysql' => _('MySQL'),
			'sql' => _('MySQL'),
			
			'general_mails_settings_send_type_value_mail' => _('Local'),
			'general_mails_settings_send_type_value_smtp' => _('SMTP server'),
			
			'desktop' => _('Desktop'),
			'applications' => _('Applications'),
			
			'general_slave_server_settings_action_when_as_not_ready_value_0' => _('Do nothing'),
			'general_slave_server_settings_action_when_as_not_ready_value_1' => _('Switch to maintenance'),
			
			'general_remote_desktop_settings_desktop_type_value_any' => _('Any'),
			'general_remote_desktop_settings_desktop_type_value_linux' => _('Linux'),
			'general_remote_desktop_settings_desktop_type_value_windows' => _('Windows'),
			
			'no' => _('no'),
			'partial' => _('partial'),
			'full' => _('full'),
			
			'general_session_settings_advanced_settings_startsession_value_session_mode' =>  _('session mode'),
			'general_session_settings_advanced_settings_startsession_value_session_language' =>  _('language'),
			'general_session_settings_advanced_settings_startsession_value_server' =>  _('server'),
			'general_session_settings_advanced_settings_startsession_value_timeout' =>  _('timeout'),
			'general_session_settings_advanced_settings_startsession_value_persistent' =>  _('persistent'),
			
			'AuthMethod_enable_value_Auto' => _('Auto authentication'),
			'AuthMethod_enable_value_CAS' => _('CAS authentication'),
			'AuthMethod_enable_value_RemoteUser' => _('RemoteUser authentication'),
			'AuthMethod_enable_value_Token' => _('Token authentication'),
			'AuthMethod_enable_value_password' => _('Login/Password authentication'),
			
			'SessionManagement_internal_generate_aps_login_value_0' => _('Use given login'),
			'SessionManagement_internal_generate_aps_login_value_1' => _('Auto-generate'),
		);
	}
}
