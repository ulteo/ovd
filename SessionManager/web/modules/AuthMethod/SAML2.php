<?php
/**
 * Copyright (C) 2014 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2014
 * Author David PHAM-VAN <d.pham-van@ulteo.com> 2014
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

include_once(dirname(dirname(dirname(__FILE__))).'/PEAR/php-saml/_toolkit_loader.php');


class AuthMethod_SAML2 extends AuthMethod {
	public function get_login() {
		Logger::debug('main', 'AuthMethod_SAML2::get_login()');
		
		$my_settings = $this->prefs->get('AuthMethod', 'SAML2');

		$saml_node = $this->user_node_request->getElementsByTagname('saml_ticket')->item(0);
		if (is_null($saml_node)) {
			Logger::error('main', 'Authentication SAML2: No incoming SAML ticket');
			return NULL;
		}
		
		$saml_response_ticket = NULL;
		for ($child = $saml_node->firstChild; $child != NULL; $child = $child->nextSibling) {
			if ($child->nodeType != XML_TEXT_NODE) {
				Logger::error('main', 'Authentication SAML2: node is not text');
				continue;
			}
			
			$saml_response_ticket = $child->wholeText;
		}
		
		if (is_null($saml_response_ticket)) {
			Logger::error('main', 'Authentication SAML2: No incoming SAML ticket (bad protocol)');
			return NULL;
		}
		
		$settings = $this->build_saml_settings($my_settings['idp_url'], $my_settings['idp_fingerprint'], $my_settings['idp_cert']);
 		try {
			$response = new OneLogin_Saml2_Response($settings, $saml_response_ticket);
			ob_start(); // Catch debug messages
			if (! $response->isValid()) {
				Logger::error('main', 'Authentication SAML2: the SAML response is not valid '.ob_get_contents());
				ob_end_clean();
				return NULL;
			}
			ob_end_clean();
			
			$sessionExpiration = $response->getSessionNotOnOrAfter();
			if ((!empty($sessionExpiration) &&  $sessionExpiration <= time()) || (!$response->validateTimestamps())) {
				Logger::error('main', 'Authentication SAML2: Session expired');
				return NULL;
			}
 		}
 		catch(Exception $e) {
 			Logger::error('main', 'Authentication SAML2: '.$e->getMessage());
 			return NULL;
 		}
		
		$attributes = $response->getAttributes();
		$user = $this->userDB->import($response->getNameId());
		if ($user == NULL) {
			Logger::error('main', 'Authentication SAML2: user not found');
			throw new Exception();
		}
		$login = $user->getAttribute('login');
		
		// we recognize following attributes:
		//  * ovd.group_member: for user group matching
		//  * ovd.setting.*: for settings
		
		if (array_key_exists("ovd.group_member", $attributes) && is_array($attributes["ovd.group_member"])) {
			$userGroupDB = UserGroupDB::getInstance();
			$to_delete = array();
			$current_groups = array_keys(Abstract_Liaison::loadGroups('UsersGroup', $login));
			foreach ($attributes["ovd.group_member"] as $group_name) {
				$found = false;
				list($groups, $sizelimit_exceeded) = $userGroupDB->getGroupsContains($group_name, array('name'));
				foreach($groups as $group) {
					if ($group->name == $group_name) {
						$found = True;
						if (!in_array($group->getUniqueID(), $current_groups)) {
							Logger::info('main', 'Authentication SAML2: Add user "'.$login.'" to group "'.$group->name.'"');
							$ret = Abstract_Liaison::save('UsersGroup', $login, $group->getUniqueID());
							if ($ret !== true) {
								Logger::error('main', 'Authentication SAML2: Unable to add user "'.$login.'" to group "'.$group->name.'"');
								throw new Exception();
							}
						} else {
							unset($current_groups[array_search($group->getUniqueID(), $current_groups)]);
						}
					}
				}
				if (!$found) {
					Logger::error('main', 'Authentication SAML2: group "'.$group_name.'" not found');
					throw new Exception();
				}
			}
			foreach ($current_groups as $group) {
				Logger::info('main', 'Authentication SAML2: remove group "'.$group.'" from '.$login);
				Abstract_Liaison::delete('UsersGroup', $login, $group);
			}
		}
		
		$prefs = Preferences::getInstance();
		foreach($attributes as $attribute => $value) {
			if (is_array($value) && count($value) == 1) {
				$value = $value[0];
			}
			
			if (substr($attribute, 0, 12) == 'ovd.setting.') {
				$attribute = explode('.', $attribute);
				if (count($attribute) != 4) {
					Logger::error('main', 'Authentication SAML2: incorrect setting : "'.implode('.', $attribute).'"');
					throw new Exception();
				}
				
				$container = $attribute[2];
				$setting = $attribute[3];

				$session_settings_defaults = $prefs->getElements('general', $container);
				if (! array_key_exists($setting, $session_settings_defaults)) {
					Logger::error('main', 'Authentication SAML2: setting "'.implode('.', $attribute).'" does not exists');
					throw new Exception();
				}
				
				$config_element = clone $session_settings_defaults[$setting];
				$ugp = new User_Preferences($login, 'general', $container, $setting, $config_element->content);
				Logger::info('main', 'Authentication SAML2: set setting "'.implode('.', $attribute).'" to '.str_replace("\n", "", print_r($value, true)));
				$ugp->value = $value;
				
				Abstract_User_Preferences::delete($login, 'general', $container, $setting);
				$ret = Abstract_User_Preferences::save($ugp);
				if (! $ret) {
					Logger::error('main', 'Authentication SAML2: impossible to save setting "'.implode('.', $attribute).'"');
					throw new Exception();
				}
			}
		}
		
		// return true or false.. No redirection to any IdP. We must have a valid ticket at this point. No artifact method
		return $response->getNameId();
	}


	public function authenticate($user_) {
		return $this->userDB->exists($user_->getAttribute('login'));
	}


	public static function prefsIsValid($prefs_, &$log=array()) {
		$my_settings = $prefs_->get('AuthMethod','SAML2');
		if (strlen($my_settings['idp_url']) == 0) {
			return false;
		}
		
		if (strlen($my_settings['idp_fingerprint']) == 0 && strlen($my_settings['idp_cert']) == 0) {
			return false;
		}
		
		if (self::build_saml_settings($my_settings['idp_url'], $my_settings['idp_fingerprint'], $my_settings['idp_cert']) == NULL) {
			return false;
		}
		
		return true;
	}


	public static function configuration() {
		return array(
			new ConfigElement_input('idp_url', 'https://www.ulteoidp.com'),
			new ConfigElement_input('idp_fingerprint', 'certificate fingerprint'),
			new ConfigElement_textarea('idp_cert', ''),
		);
	}


	public static function init($prefs_) {
		return true;
	}


	public static function enable() {
		return true;
	}


	private static function build_saml_settings($idp_url_, $idp_fingerprint_, $idp_cert_) {
		if (strlen($idp_fingerprint_) == 0)
			$idp_fingerprint_ = NULL;
		
		if (strlen($idp_cert_) == 0)
			$idp_cert_ = NULL;
		
		$settingsInfo = array (
			'strict' => false,
			'debug' => true,
			'sp' => array (
				'entityId' => 'https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'],
				'assertionConsumerService' => array (
					'url' => 'https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'],
					'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
				),
			),
			'idp' => array (
				'entityId' => $idp_url_,
				'singleSignOnService' => array (
					'url' => $idp_url_,
					'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
				),
				
				'certFingerprint' => $idp_fingerprint_,
				'x509cert' => $idp_cert_,
			),
		);
		
		try {
			$settings = new OneLogin_Saml2_Settings($settingsInfo);
		}
		catch(Exception $e) {
			Logger::error('main', 'Unable to load SAML2 settings: '.$e->getMessage());
			Logger::debug('main', 'AuthMethod_SAML2::build_saml_settings('.$idp_url_.', '.$idp_fingerprint_.') error...');
			return NULL;
		}
		
		return $settings;
	}


	public function getClientParameters() {
		return $this->prefs->get('AuthMethod','SAML2');
	}

}
