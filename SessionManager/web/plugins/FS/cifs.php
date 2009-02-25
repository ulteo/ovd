<?php
/**
 * Copyright (C) 2008,2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com>
 * Author Jeremy DESVAGES <jeremy@ulteo.com>
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
require_once(dirname(__FILE__).'/../../includes/core.inc.php');

class FS_cifs extends Plugin {
	public function start_session($params_) {
		global $user;
		
		$prefs = Preferences::getInstance();
		if (! $prefs)
			die_error('get Preferences failed',__FILE__,__LINE__);
		
		$conf = $prefs->get('plugins', get_class($this));
		
		if (is_array($conf)) {
			if (isset($conf['authentication_method'])) {
				Logger::debug('main', 'FS_cifs plugin authentication_method \''.$conf['authentication_method'].'\'');
				$this->redir_args = array(
						'module_fs' => array(
						'user_homedir' => $user->getAttribute('homedir')
						)
					);
				
				if ( $conf['authentication_method'] == 'anonymous') {
					Logger::debug('main', 'FS_cifs plugin authentication_method  => anonymous, we do nothing');
					// we do nothing (no set login)
				}
				else if ( $conf['authentication_method'] == 'user') {
					if ($user->hasAttribute('real_login')) {
						Logger::debug('main', 'FS_cifs plugin authentication_method  => user (login=\''.$user->getAttribute('real_login').'\')(password=\''.$_SESSION['password'].'\')');
						$this->redir_args['module_fs']['login'] = $user->getAttribute('real_login');
						// dirty hack for password
						$this->redir_args['module_fs']['password'] = $_SESSION['password'];
					}
					else {
						Logger::error('main', 'FS_cifs plugin authentication_method  => user, user has not attribute \'real_login\'');
					}
				}
				else if ( $conf['authentication_method'] == 'global_user') {
					
					if ( isset($conf['global_user_login']) && isset($conf['global_user_password'])) {
						Logger::debug('main', 'FS_cifs plugin authentication_method  => global_user (login=\''.$conf['global_user_login'].'\')(password=\''.$conf['global_user_password'].'\')');
						$this->redir_args['module_fs']['login'] = $conf['global_user_login'];
						$this->redir_args['module_fs']['password'] = $conf['global_user_password'];
					}
					else {
						Logger::error('main', 'FS_cifs plugin authentication_method global_user, global_user_login or global_user_password not set');
					}
				}
				else {
					// bug
					Logger::error('main', 'FS_cifs plugin authentication_method \''.$conf['authentication_method'].'\' unknow');
				}
			}
			else {
				Logger::error('main', 'FS_cifs plugin no authentication method founded');
			}
		}
		else {
			Logger::error('main', 'FS_cifs plugin configuration not valid (not array)');
		}
	}

	public function requirements() {
		$req = array();
		$req['UserDB'] = array('homedir');
		return $req;
	}

	public function prettyName() {
		return _('Common Internet File System (CIFS)');
	}

	public static function enable() {
		return true;
	}
	
	public function configuration() {
		$ret = array();
		$c = new ConfigElement('authentication_method', 'authentication_method', 'authentication_method', 'authentication_method', 'anonymous', array('anonymous' => 'anonymous', 'user' => 'user','global_user' => 'global_user'), ConfigElement::$SELECT);
		$ret []= $c;
		$c = new ConfigElement('global_user_login', _('login'), _('login'), _('login'), '', NULL, ConfigElement::$INPUT);
		$ret []= $c;
		$c = new ConfigElement('global_user_password', _('password'), _('password'), _('password'), 'servldap.example.com', NULL, ConfigElement::$PASSWORD);
		$ret []= $c;
		return $ret;
	}
}
