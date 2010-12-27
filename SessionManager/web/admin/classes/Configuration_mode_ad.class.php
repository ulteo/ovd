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

class Configuration_mode_ad extends Configuration_mode {

  public function getPrettyName() {
    return _('Active Directory');
  }

  public function careAbout($sessionmanagement_) {
    return 'microsoft' == $sessionmanagement_;
  }

  public function has_change($oldprefs, $newprefs) {
    $old = $oldprefs->get('UserDB', 'activedirectory');
    $new = $newprefs->get('UserDB', 'activedirectory');

    $change_ad = False;
    foreach(array('hosts', 'domain') as $key) {
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
		    'admin_branch', 'user_group');

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
      $admin_dn = 'cn='.$form['admin_login'].',cn=Users';
	
	elseif ($form['admin_branch'] == 'specific') {
		$abranch = $form['admin_branch_ou'];
		if (strstr($abranch, ',') != False) {
			$buffer = explode(',', $abranch);
			$buffer = array_reverse($buffer);
			for($i=0; $i<count($buffer); $i++)
				$buffer[$i] = trim($buffer[$i]);
			$abranch = implode(',ou=', $buffer);
		}
		
		$admin_dn = 'cn='.$form['admin_login'].',ou='.$abranch;
	}

    $ad_ar = array();
    $ad_ar['hosts'] = array($form['host'], $form['host2']);
    $ad_ar['domain'] = $form['domain'];
    $ad_ar['login'] = $admin_dn;
    $ad_ar['password'] = $form['admin_password'];


    $ad_ar['match'] = array();
    if ($form['user_group'] == 'activedirectory')
      $ad_ar['match']['memberof'] = 'memberOf';

    // Enable modules
    $module_to_enable = array('UserDB', 'UserGroupDB');
    $module_enabled = $prefs->get('general', 'module_enable');
    $prefs->set('general', 'module_enable', array_unique(array_merge($module_enabled, $module_to_enable)));

    // Select AD as UserDB
    $prefs->set('UserDB', 'enable', 'activedirectory');

    // Push AD conf
    $prefs->set('UserDB', 'activedirectory', $ad_ar);
    //var_dump($ad_ar);

    // Select Module for UserGroupDB
    $prefs->set('UserGroupDB', 'enable', $form['user_group']);
    
    if ($form['user_group'] == 'activedirectory') { // ugly hack
      $prefs->set('UserGroupDB', 'ldap_memberof', array('match' => array('description' => 'description','name' => 'sAMAccountName', 'member' => 'member')));
    }
    
    // Set the Session Management module
    $prefs->set('SessionManagement', 'enable', 'microsoft');

    return True;
  }

  public function config2form($prefs) {
    $form = array();
    $config = $prefs->get('UserDB', 'activedirectory');

    $form['host'] = '';
    if (isset($config['hosts'][0]))
      $form['host'] = $config['hosts'][0];
    $form['host2'] = '';
    if (isset($config['hosts'][1]))
      $form['host2'] = $config['hosts'][1];
    
    $form['domain'] = $config['domain'];

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
	if($config['login'] == 'cn='.$admin_login.',cn=Users')
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

    $buf = $prefs->get('UserGroupDB', 'enable');
    $form['user_group'] = ($buf == 'activedirectory')?'activedirectory':'sql';

    return $form;
  }

  public function display($form) {
    $str= '<h1>'._('Active Directory integration').'</h1>';

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

    $str.= '<div class="section">';
    $str.= '<h3>'._('User Groups').'</h3>';
    $str.= '<input class="input_radio" type="radio" name="user_group" value="activedirectory"';
    if ($form['user_group'] == 'activedirectory')
      $str.= ' checked="checked"';
    $str.= ' />'._('Use Active Directory User Groups').'<br/>';

    $str.= '<input class="input_radio" type="radio" name="user_group" value="sql"';
    if ($form['user_group'] == 'sql')
      $str.= ' checked="checked"';
    $str.= '/>'._('Use Internal User Groups');
    $str.= '</div>';

    /*
    $str.= '<div style="display:none" class="section">';
    $str.= '<h3>'._('Windows Applications').'</h3>';
    $str.= _('Allow Windows Application link thanks to TS and AD:');
    $str.= '<input class="input_radio" type="radio" name="ts_link" value="yes" checked="checked"/>';
    $str.= _('yes');
    $str.= '<input class="input_radio" type="radio" name="ts_link" value="no" />'._('no');
    $str.= '</div>';
    */

    return $str;
  }

  public function display_sumup($prefs) {
    $form = $this->config2form($prefs);

    $str= '';
    $str.= '<ul>';
    $str.= '<li><strong>'._('Domain:').'</strong> '.$form['domain'].'</li>';

    $str.= '<li><strong>'._('Administrator account:').'</strong> '.$form['admin_login'].'</li>';
    
    $str.= '<li><strong>'._('User Groups:').'</strong> ';
    if ($form['user_group'] == 'activedirectory')
      $str.= _('Active Directory User Groups');
    else
      $str.= _('Use Internal User Groups');
    $str.= '</li>';
  
    $str.= '</ul>';

    return $str;
  }

}
