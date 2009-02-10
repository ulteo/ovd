<?php
/**
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com>
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

  public function has_change($oldprefs, $newprefs) {
    $old = $oldprefs->get('UserDB', 'activedirectory');
    $new = $newprefs->get('UserDB', 'activedirectory');

    $change_ad = False;
    foreach(array('host', 'domain', 'ou') as $key) {
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
    $fields = array('host', 'domain', 'user_branch',
		    'admin_login', 'admin_password',
		    'admin_branch', 'user_group');

    foreach($fields as $field) {
      if (! isset($form[$field])) {
	return False;
      }
    }

    if (! in_array($form['user_branch'], array('default', 'specific')))
      return False;

    if (! in_array($form['admin_branch'], array('default', 'same')))
      return False;

    if ($fields['user_branch'] == 'specific' &&
	! isset($form['user_branch_ou'])){
      // Error
      return False;
    }

    return True;

  }

  public function form_read($form, $prefs) {
    if ($form['user_branch'] == 'default')
      $ou = 'cn=Users';
    else
      $ou = 'ou='.$form['user_branch_ou'];

    if ($form['admin_branch'] == 'default')
      $admin_dn = 'cn='.$form['admin_login'].',cn=Users';
    else
      $admin_dn = 'cn='.$form['admin_login'].','.$ou;

    $ad_ar = array();
    $ad_ar['host'] = $form['host'];
    $ad_ar['domain'] = $form['domain'];
    $ad_ar['login'] = $admin_dn;
    $ad_ar['password'] = $form['admin_password'];
    $ad_ar['ou'] = $ou;


    $ad_ar['match'] = array();
    if ($form['user_group'] == 'activedirectory')
      $ad_ar['match']['memberof'] = 'memberof';


    // plugins fs ... 
    if ($form['homedir'] == 'local')
      $plugin_fs = 'local';
    elseif ($form['homedir'] == 'ad_profile') {
      $plugin_fs = 'cifs_no_sfu';
      $ad_ar['match']['homedir'] = 'profilepath';

    }
    else {
      $plugin_fs = 'cifs_no_sfu';
      $ad_ar['match']['homedir'] = 'homedirectory';    
    }

    // Select AD as UserDB
    $prefs->set('UserDB', 'enable', 
		array('enable' => 'activedirectory'));

    // Push AD conf
    $prefs->set('UserDB', 'activedirectory', $ad_ar);
    //var_dump($ad_ar);

    // Select Module for UserGroupDB
    $prefs->set('UserGroupDB', 'enable',
		array('enable' => $form['user_group']));


    // Set the FS type
    $prefs->set('plugins', 'FS',
		array('FS' => $plugin_fs));

    return True;
  }

  public function display($prefs) {
    $form = $prefs->get('UserDB', 'activedirectory');

    // Users
    $user_branch_default = ($form['ou'] == 'cn=Users');
    $user_branch = '';
    if (! $user_branch_default) {
      $buf = explode('=', $form['ou'], 2);
      $user_branch = $buf[1];
    }

    // Admin infos
    $admin_branch = (str_endswith($form['login'], $form['ou']));

    $buf = explode('=', $form['login'], 2);
    $buf = explode(',', $buf[1], 2);
    $admin_login = $buf[0];


    // User groups
    $buf = $prefs->get('UserGroupDB', 'enable');
    $user_group_ad = ($buf == 'activedirectory');

    // plugins fs ... 
    $buf = $prefs->get('plugins', 'FS');
    if ($buf == 'local')
      $home = array(true, false, false);
    elseif ($form['match']['homedir'] == 'profilepath')
      $home = array(false, true, false);
    else
      $home = array(false, false, true);      

    $checked = array(true => 'checked="checked"', false => '');

    $str= '<h1>'._('Active Directory integration').'</h1>';

    $str.= '<div>';
    $str.= '<h3>Server</h3>';
    $str.= '<table>';
    $str.= '<tr><td>'._('Server Host:').'</td><td><input type="text" name="host" value="'.$form['host'].'" /></td></tr>';
    $str.= '<tr><td>'._('Domain:').'</td><td><input type="text" name="domain" value="'.$form['domain'].'" /></td></tr>';
    $str.= '</table>';
    $str.= '</div>';
    $str.= '<br/><!-- useless => css padding bottom-->'."\n";

    $str.= '<div>';
    $str.= '<h3>'._('Users').'</h3>';
    $str.= '<input type="radio" name="user_branch" value="default" '.$checked[$user_branch_default].'/>'._('Default user branch (Users)');
    $str.= '<br/>';
    $str.= '<input type="radio" name="user_branch" value="specific" '.$checked[!$user_branch_default].'/>'._('Specific Organization Unit:');
    $str.= '<input type="text" name="user_branch_ou" value="'.$user_branch.'" />';
     
    $str.= '<div>';
    $str.= '<h4>'._('Administrator account').'</h4>';
    $str.= '<table>';
    $str.= '<tr><td>'._('login:').'</td><td><input type="text" name="admin_login" value="'.$admin_login.'" /></td></tr>';
    $str.= '<tr><td>'._('password:').'</td><td><input type="password" name="admin_password" value="'.$form['password'].'" /></td></tr>';
    $str.= '<tr><td colspan="2">';

    $str.= '<input type="radio" name="admin_branch" value="same" '.$checked[$admin_branch].'/>'._('Same as Users, ');
    $str.= '<input type="radio" name="admin_branch" value="default" '.$checked[!$admin_branch].'/>'._('Default user branch');
    $str.= '</td></tr>';
    $str.= '</table>';
    $str.= '</div>';
    $str.= '</div>';
    $str.= '<br/><!-- useless => css-->'."\n";

    $str.= '<div>';
    $str.= '<h3>'._('User Groups').'</h3>';
    $str.= '<input type="radio" name="user_group" value="activedirectory" '.$checked[$user_group_ad].' />'._('Use Active Directory User Groups');
    $str.= '<br/>';
    $str.= '<input type="radio" name="user_group" value="sql" '.$checked[!$user_group_ad].'/>'._('Use Internal User Groups');
    $str.= '</div>';
    $str.= '<br/><!-- useless => css-->'."\n";

    $str.= '<div>';
    $str.= '<h3>'._('Home Directory').'</h3>';
    $str.= '<input type="radio" name="homedir" value="local" '.$checked[$home[0]].'/>';
    $str.= _('Use Internal home directory (no server replication)');
    $str.= '<br/>';
    $str.= '<input type="radio" name="homedir" value="ad_profile" '.$checked[$home[1]].'/>';
    $str.= _('Use Active Directory Users profiles as Home directory');
    $str.= '<br/>';
    $str.= '<input type="radio" name="homedir" value="ad_homedir" '.$checked[$home[2]].'/>';
    $str.= _('Use Active Directory Home dir');
    $str.= '</div>';
    $str.= '<br/><!-- useless => css-->'."\n";

    $str.= '<div style="display:none">';
    $str.= '<h3>'._('Windows Applications').'</h3>';
    $str.= _('Allow Windows Application link thanks to TS and AD:');
    $str.= '<input type="radio" name="ts_link" value="yes" checked="checked"/>';
    $str.= _('yes');
    $str.= '<input type="radio" name="ts_link" value="no" />'._('no');
    $str.= '</div>';
    $str.= '<br/><!-- useless => css-->'."\n";

    return $str;
  }

}
