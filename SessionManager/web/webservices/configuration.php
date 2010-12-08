<?php
/**
 * Copyright (C) 2008-2010 Ulteo SAS
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
require_once(dirname(__FILE__).'/../includes/core.inc.php');

$prefs = Preferences::getInstance();
if (! $prefs) {
	die_error(_('Internal error'),__FILE__,__LINE__, true);
	die();
}

$system_in_maintenance = $prefs->get('general', 'system_in_maintenance');
if ($system_in_maintenance == '1') {
	die_error(_('The system is on maintenance mode'), __FILE__, __LINE__, true);
}

if (isset($prefs->elements['general']['session_settings_defaults']['language']))
	$list_languages = $prefs->elements['general']['session_settings_defaults']['language']->content_available;

$list_desktop_sizes = array(
	'auto'	=>	_('Maximum')
);

if (isset($prefs->elements['general']['session_settings_defaults']['timeout']))
	$list_desktop_timeouts = $prefs->elements['general']['session_settings_defaults']['timeout']->content_available;

if (isset($prefs->elements['general']['session_settings_defaults']['session_mode']))
	$list_session_modes = $prefs->elements['general']['session_settings_defaults']['session_mode']->content_available;

$default_settings = $prefs->get('general', 'session_settings_defaults');
$session_settings_defaults = $default_settings;

$session_mode = $default_settings['session_mode'];
$language = $default_settings['language'];
$desktop_size = 'auto';
$desktop_timeout = $default_settings['timeout'];
$persistent = $default_settings['persistent'];
//$shareable = $default_settings['shareable'];
$desktop_icons = $default_settings['desktop_icons'];
$debug = 0;

$default_settings = $prefs->get('general', 'web_interface_settings');
$web_interface_settings = $default_settings;

$mods_enable = $prefs->get('general', 'module_enable');
if (!in_array('UserDB', $mods_enable))
	die_error(_('UserDB module must be enabled'),__FILE__,__LINE__);

$userDB = UserDB::getInstance();

$buf = $prefs->get('general', 'web_interface_settings');
$show_list_users = $buf['show_list_users'];
if ($userDB->canShowList() == false) {
	$show_list_users = false;
}

if ($show_list_users && $userDB->canShowList()) {
	$list_users = $userDB->getList();
	if (is_null($list_users))
		die_error(_('Getting userlist failed'), __FILE__, __LINE__);
	elseif (count($list_users) == 0)
		die_error(_('No available user'), __FILE__, __LINE__);
}

$password_field = $userDB->needPassword();

$advanced_settings_session = $prefs->get('general', 'session_settings_defaults');
$advanced_settings_session = $advanced_settings_session['advanced_settings_startsession'];

if (!is_array($advanced_settings_session))
	$advanced_settings_session = array();

$advanced_settings_webinterface = $prefs->get('general', 'web_interface_settings');
$advanced_settings_webinterface = $advanced_settings_webinterface['advanced_settings_startsession'];

if (!is_array($advanced_settings_webinterface))
	$advanced_settings_webinterface = array();

$advanced_settings = array_merge($advanced_settings_session, $advanced_settings_webinterface);
$default_settings = array_merge($session_settings_defaults, $web_interface_settings, array('debug' => 0));

$forceable_parameters = array();

$forceable_parameters['session_mode'] = $list_session_modes;
$forceable_parameters['language'] = $list_languages;
// $forceable_parameters['server'] = ... ;
$forceable_parameters['size'] = $list_desktop_sizes;
$forceable_parameters['timeout'] = $list_desktop_timeouts; // $seconds => $text
$forceable_parameters['persistent'] = array('0' => _('No'), '1' => _('Yes'));
//$forceable_parameters['shareable'] = array('0' => _('No'), '1' => _('Yes'));
$forceable_parameters['desktop_icons'] = array('0' => _('No'), '1' => _('Yes'));
$forceable_parameters['popup'] = array('0' => _('No'), '1' => _('Yes'));
$forceable_parameters['debug'] = array('0' => _('No'), '1' => _('Yes'));

header('Content-Type: text/xml; charset=utf-8');

$dom = new DomDocument('1.0', 'utf-8');
$node = $dom->createElement('configuration');

$node_users = $dom->createElement('users');

$node_users->setAttribute('showlist', $show_list_users?1:0);
$node_users->setAttribute('needpassword', $password_field?1:0);
if ($userDB->canShowList()) {
	foreach ( $list_users as $user) {
		$node_user = $dom->createElement('user');
		$node_user->setAttribute('id', $user->getAttribute('login'));
		$node_user->setAttribute('displayname', $user->getAttribute('displayname'));
		
		$node_users->appendChild($node_user);
	}
}

$node_parameters = $dom->createElement('settings');

$node_forceable = $dom->createElement('forceable');
foreach ($advanced_settings as $a_setting) {
	$node_setting = $dom->createElement('setting');
	$node_setting->setAttribute('id', $a_setting);
	if (isset($default_settings[$a_setting])) {
		$node_setting->setAttribute('default', $default_settings[$a_setting]);
	}
	
	if (isset($forceable_parameters[$a_setting])) {
		foreach ($forceable_parameters[$a_setting] as $content_key => $content_value) {
			$node_setting_content = $dom->createElement('content');
			$node_setting_content->setAttribute('name', $content_key);
			$node_setting_content->setAttribute('value', $content_value);
			$node_setting->appendChild($node_setting_content);
		}
	}
	
	$node_forceable->appendChild($node_setting);
}

$node_parameters->appendChild($node_forceable);

$dom->appendChild($node);
$node->appendChild($node_users);
$node->appendChild($node_parameters);
echo $dom->saveXML();
