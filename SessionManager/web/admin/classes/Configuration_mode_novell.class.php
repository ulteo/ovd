<?php
/**
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2010
 * Author Julien LANGLOIS <julien@ulteo.com> 2009
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

class Configuration_mode_novell extends Configuration_mode {

  public function getPrettyName() {
    return _('Novell');
  }

  public function careAbout($sessionmanagement_) {
    return 'novell' == $sessionmanagement_;
  }

  public function has_change($oldprefs, $newprefs) {
    $old = $oldprefs->get('UserDB', 'ldap');
    $new = $newprefs->get('UserDB', 'ldap');

    $change_ad = False;
    foreach(array('hosts', 'suffix') as $key) {
      if ($old[$key] != $new[$key]) {
	$change_ad = True;
	break;
      }
    }

    $old = $oldprefs->get('UserGroupDB', 'enable');
    $new = $newprefs->get('UserGroupDB', 'enable');
    $g = !($old == 'sql' && $new == 'sql');

    return array($change_ad, $change_ad && $g);
  }

  public function form_valid($form) {
    $fields = array('host', 'domain', 
      'admin_login', 'admin_password',
      'admin_branch');

    foreach($fields as $field) {
      if (! isset($form[$field])) {
        return False;
      }
    }

    if (! in_array($form['admin_branch'], array('default', 'specific')))
      return False;

    if ($form['admin_branch'] == 'specific' && ! isset($form['admin_branch_ou'])){
      // Error
      return False;
    }

    return True;

  }

  public function form_read($form, $prefs) {
    if ($form['admin_branch'] == 'default')
      $admin_dn = 'cn='.$form['admin_login'].',cn=Users,'.domain2suffix($form['domain']);
    elseif ($form['admin_branch'] == 'specific') {
      $abranch = $form['admin_branch_ou'];
      if (strstr($abranch, ',') != False) {
        $buffer = explode(',', $abranch);
        $buffer = array_reverse($buffer);
        for($i=0; $i<count($buffer); $i++)
          $buffer[$i] = trim($buffer[$i]);
        
        $abranch = implode(',ou=', $buffer);
      }
    
      $admin_dn = 'cn='.$form['admin_login'].',ou='.$abranch.','.domain2suffix($form['domain']);
    }

    $ad_ar = array();
    $ad_ar['hosts'] = array($form['host'], $form['host2']);
    $ad_ar['suffix'] = domain2suffix($form['domain']);
    $ad_ar['login'] = $admin_dn;
    $ad_ar['password'] = $form['admin_password'];
    $ad_ar['uidprefix'] = 'cn';
    $ad_ar['filter'] = '(&(objectCategory=person)(objectClass=user))';
    $ad_ar['userbranch'] = '';
    $ad_ar['options'] = array('LDAP_OPT_PROTOCOL_VERSION' => '3');


    $ad_ar['match'] = array();
    $ad_ar['match']['login'] = 'cn';
    $ad_ar['match']['displayname'] = 'fullName';
    $ad_ar['match']['memberof'] = 'memberOf';

    // Select AD as UserDB
    $prefs->set('UserDB', 'enable', 'ldap');

    // Push the conf
    $prefs->set('UserDB', 'ldap', $ad_ar);

    // Select Module for UserGroupDB
    $prefs->set('UserGroupDB', 'enable', 'activedirectory');
    $prefs->set('UserGroupDB', 'activedirectory', array('match' => array('description' => 'description','name' => 'sAMAccountName', 'member' => 'member')));
    
    // Set the Session Management module
    $prefs->set('SessionManagement', 'enable', 'novell');

    // Disable the unused module
    $module_to_disable = array('ProfileDB', 'SharedFolderDB');
    
    $module_enabled = $prefs->get('general', 'module_enable');
    foreach ($module_to_disable as $a_module_name) {
      $key = array_search($a_module_name, $module_enabled);
      if ($key !== false) {
        unset($module_enabled[$key]);
      }
    }
    $prefs->set('general', 'module_enable', $module_enabled);
    
    // for now disable profile and sharedlfolder on session settings
    $session_settings_defaults = $prefs->get('general', 'session_settings_defaults');
    if (array_key_exists('enable_profiles', $session_settings_defaults)) {
      $session_settings_defaults['enable_profiles'] = '0';
    }
    if (array_key_exists('enable_sharedfolders', $session_settings_defaults)) {
      $session_settings_defaults['enable_sharedfolders'] = '0';
    }
    $prefs->set('general', 'session_settings_defaults', $session_settings_defaults);
    
    return True;
  }

  public function config2form($prefs) {
    $form = array();
    $config = $prefs->get('UserDB', 'ldap');

    $form['host'] = '';
    if (isset($config['hosts'][0]))
      $form['host'] = $config['hosts'][0];
    $form['host2'] = '';
    if (isset($config['hosts'][1]))
      $form['host2'] = $config['hosts'][1];
    $form['domain'] = suffix2domain($config['suffix']);

    // Admin login - Todo: replace by a regexp
    if ($config['login'] != '') {
      $buf = explode('=', $config['login'], 2);
      $buf = explode(',', $buf[1], 2);
      $admin_login = $buf[0];
		$admin_ou = $buf[1];
	} else {
		$admin_login = '';
		$admin_ou = '';
    }

    $form['admin_login'] = $admin_login;
    $form['admin_password'] = $config['password'];

	$form['admin_branch_ou'] = '';
	if($config['login'] == $config['uidprefix'].'='.$admin_login.',cn=Users,'.$config['suffix'])
		$form['admin_branch'] = 'default';
	else {
		$form['admin_branch'] = 'specific';
		
		$buffer = explode(',', $admin_ou);
		for($i=0; $i<count($buffer); $i++) {
			$buf = explode('=', $buffer[$i], 2);
			if (! isset($buf[1]))
				break;
			$buffer[$i] = $buf[1];
		}
		$form['admin_branch_ou'] = implode(',', array_reverse($buffer));
	}

    return $form;
  }

  public function display($form) {
    $str= '<h1>'._('Novell integration').'</h1>';

    $str.= '<div class="section">';
    $str.= '<h3>Server</h3>';
    $str.= '<table>';
    $str.= '<tr><td>'._('Primary Host:').'</td><td><input type="text" name="host" value="'.$form['host'].'" /></td></tr>';
    $str.= '<tr><td>'._('Secondary Host:').'</td><td><input type="text" name="host2" value="'.$form['host2'].'" /></td>';
    $str.= '<td><span style="font-size: 0.9em; font-style: italic;">('._('optional').')</span></td>';
    $str.= '</tr>';
    $str.= '<tr><td>'._('Domain:').'</td><td><input type="text" name="domain" value="'.$form['domain'].'" /></td></tr>';
    $str.= '</table>';
    $str.= '</div>';

    $str.= '<div class="section">';
    $str.= '<h3>'._('Administrator account').'</h3>';
    $str.= '<table>';
    $str.= '<tr><td>'._('Login:').'</td><td><input type="text" name="admin_login" value="'.$form['admin_login'].'" /></td></tr>';
    $str.= '<tr><td>'._('Password:').'</td><td><input type="password" name="admin_password" value="'.$form['admin_password'].'" /></td></tr>';

    $str.= '<tr><td colspan="2">';
    $str.= '<input class="input_radio" type="radio" name="admin_branch" value="default"';
    if ($form['admin_branch'] == 'default')
      $str.= ' checked="checked"';
    $str.= '/>'._('Default user branch');
    $str.= '<br/>';
    $str.= '<input class="input_radio" type="radio" name="admin_branch" value="specific"';
    if ($form['admin_branch'] == 'specific')
      $str.= ' checked="checked"';
    $str.= '/>'._('Specific Organization Unit:');
    $str.= ' <input type="text" name="admin_branch_ou" value="'.$form['admin_branch_ou'].'" />';
    $str.= '</div>';
    $str.= '</td></tr>';
    $str.= '</table>';
    $str.= '</div>';

    return $str;
  }

  public function display_sumup($prefs) {
    $form = $this->config2form($prefs);

    $str= '';
    $str.= '<ul>';
    $str.= '<li><strong>'._('Domain:').'</strong> '.$form['domain'].'</li>';

    $str.= '<li><strong>'._('Administrator account:').'</strong> '.$form['admin_login'].'</li>';
    
    $str.= '<li><strong>'._('User Groups:').'</strong> ';
    $str.= _('Novell User Groups');
    $str.= '</li>';
  
    $str.= '</ul>';

    return $str;
  }

}
