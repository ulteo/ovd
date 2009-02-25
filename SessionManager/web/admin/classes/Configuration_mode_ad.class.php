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

  public function careAbout($userDB) {
    return 'activedirectory' == $userDB;
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

    if ($form['user_branch'] == 'specific' && ! isset($form['user_branch_ou'])){
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
    if ( $plugin_fs == 'cifs_no_sfu') {
        $data = array('authentication_method' => 'global', 'global_user_login' => $form['admin_login'], 'global_user_password' => $ad_ar['password']);
        $prefs->set('plugins', 'FS_cifs_no_sfu', $data);
    }

    return True;
  }

  public function config2form($prefs) {
    $form = array();
    $config = $prefs->get('UserDB', 'activedirectory');

    $form['host'] = $config['host'];
    $form['domain'] = $config['domain'];

    $form['user_branch'] = ($config['ou'] == 'cn=Users')?'default':'specific';
    $b = explode('=', $config['ou'], 2);
    $form['user_branch_ou'] = ($form['user_branch'] == 'specific')?$b[1]:'';


    // Admin login - Todo: replace by a regexp
    if ($config['login'] != '') {
      $buf = explode('=', $config['login'], 2);
      $buf = explode(',', $buf[1], 2);
      $admin_login = $buf[0];
    } else
      $admin_login = '';

    $form['admin_login'] = $admin_login;
    $form['admin_password'] = $config['password'];
    $form['admin_branch'] = (str_endswith($config['login'], $config['ou']))?'same':'default';

    $buf = $prefs->get('UserGroupDB', 'enable');
    $form['user_group'] = ($buf == 'activedirectory')?'ad':'sql';

    // plugins fs ...
    $buf = $prefs->get('plugins', 'FS');
    if ($buf == 'cifs_no_sfu') {
      if ($config['match']['homedir'] == 'profilepath')
	$form['homedir'] = 'ad_profile';
      else
	$form['homedir'] = 'ad_homedir';
    }
    else
      $form['homedir'] = 'local';

    return $form;
  }

  public function display($form) {
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
    $str.= '<input class="input_radio" type="radio" name="user_branch" value="default"';
    if ($form['user_branch'] == 'default')
      $str.= ' checked="checked"';
    $str.= '/>'._('Default user branch (Users)').'<br/>';

    $str.= '<input class="input_radio" type="radio" name="user_branch" value="specific"';
    if ($form['user_branch'] == 'specific')
      $str.= ' checked="checked"';
    $str.= '/>'._('Specific Organization Unit:');
    $str.= '<input type="text" name="user_branch_ou" value="'.$form['user_branch_ou'].'" />';

    $str.= '<div>';
    $str.= '<h4>'._('Administrator account').'</h4>';
    $str.= '<table>';
    $str.= '<tr><td>'._('login:').'</td><td><input type="text" name="admin_login" value="'.$form['admin_login'].'" /></td></tr>';
    $str.= '<tr><td>'._('password:').'</td><td><input type="password" name="admin_password" value="'.$form['admin_password'].'" /></td></tr>';

    $str.= '<tr><td colspan="2">';
    $str.= '<input class="input_radio" type="radio" name="admin_branch" value="same"';
    if ($form['admin_branch'] == 'same')
      $str.= ' checked="checked"';
    $str.= '/>'._('Same as Users, ');
    $str.= '<input class="input_radio" type="radio" name="admin_branch" value="default"';
    if ($form['admin_branch'] == 'default')
      $str.= ' checked="checked"';
    $str.= '/>'._('Default user branch');
    $str.= '</td></tr>';
    $str.= '</table>';
    $str.= '</div>';
    $str.= '</div>';
    $str.= '<br/><!-- useless => css-->'."\n";

    $str.= '<div>';
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
    $str.= '<br/><!-- useless => css-->'."\n";

    $str.= '<div>';
    $str.= '<h3>'._('Home Directory').'</h3>';
    $str.= '<input class="input_radio" type="radio" name="homedir" value="local"';
    if ($form['homedir'] == 'local')
      $str.= ' checked="checked"';
    $str.= '/>';
    $str.= _('Use Internal home directory (no server replication)');
    $str.= '<br/>';

    $str.= '<input class="input_radio" type="radio" name="homedir" value="ad_profile" ';
    if ($form['homedir'] == 'ad_profile')
      $str.= ' checked="checked"';
    $str.= '/>';
    $str.= _('Use Active Directory User profiles as Home directory');
    $str.= '<br/>';
    $str.= '<input class="input_radio" type="radio" name="homedir" value="ad_homedir"';
    if ($form['homedir'] == 'ad_homedir')
      $str.= ' checked="checked"';
    $str.= '/>';
    $str.= _('Use Active Directory Home dir');
    $str.= '</div>';
    $str.= '<br/><!-- useless => css-->'."\n";

    /*
    $str.= '<div style="display:none">';
    $str.= '<h3>'._('Windows Applications').'</h3>';
    $str.= _('Allow Windows Application link thanks to TS and AD:');
    $str.= '<input class="input_radio" type="radio" name="ts_link" value="yes" checked="checked"/>';
    $str.= _('yes');
    $str.= '<input class="input_radio" type="radio" name="ts_link" value="no" />'._('no');
    $str.= '</div>';
    $str.= '<br/><!-- useless => css-->'."\n";
    */

    return $str;
  }

  public function display_sumup($prefs) {
    $form = $this->config2form($prefs);

    $str= '';
    $str.= '<ul>';
    $str.= '<li><strong>'._('Domain:').'</strong> '.$form['domain'].'</li>';

    // User
    $str.= '<li><strong>'._('Users branch:').'</strong> ';
    if ($form['user_branch'] == 'default')
      $str.= _('Default user branch (Users)').'<br/>';
    elseif ($form['user_branch'] == 'specific') {
      $str.=_('Specific Organization Unit');
      $str.= ' ('.$form['user_branch_ou'].')';
    }
    $str.= '</li>';

    $str.= '<li><strong>'._('Administrator account:').'</strong> '.$form['admin_login'].'</li>';
    
    $str.= '<li><strong>'._('User Groups:').'</strong> ';
    if ($form['user_group'] == 'activedirectory')
      $str.= _('Active Directory User Groups');
    else
      $str.= _('Use Internal User Groups');
    $str.= '</li>';
  
    $str.= '<li><strong>'._('Home Directory:').'</strong> ';
    if ($form['homedir'] == 'local')
      $str.= _('Use Internal home directory (no server replication)');
    elseif ($form['homedir'] == 'ad_profile')
      $str.= _('Use Active Directory User profiles as Home directory');
    elseif ($form['homedir'] == 'ad_homedir')
      $str.= _('Use Active Directory Home dir');
    $str.= '</li>';

    $str.= '</ul>';

    return $str;
  }

}
