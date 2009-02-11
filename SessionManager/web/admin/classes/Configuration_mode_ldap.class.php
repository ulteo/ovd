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

class Configuration_mode_ldap extends Configuration_mode {

  public function getPrettyName() {
    return _('Lightweight Directory Access Protocol (LDAP)');
  }

  public function careAbout($userDB) {
    return 'ldap' == $userDB;
  }

  public function has_change($oldprefs, $newprefs) {
    return array(True, True);
  }

  public function form_valid($form) {
    return True;
  }

  public function form_read($form, $prefs) {
    return True;
  }

  public function config2form($prefs) {
    $form = array();

    $form['host'] = '';
    $form['domain'] = '';
    $form['admin_login'] = '';
    $form['password'] = '';
    $form['user_group'] = '';
    $form['homedir'] = '';

    return $form;
  }

  public function display($form) {
    $str= '<h1>'._('Lightweight Directory Access Protocol (LDAP)').'</h1>';

    $str.= '<div>';
    $str.= '<h3>Server</h3>';
    $str.= '<table>';
    $str.= '<tr><td>'._('Server Host:').'</td><td><input type="text" name="host" value="'.$form['host'].'" /></td></tr>';
    $str.= '<tr><td>'._('Server Port:').'</td><td><input type="text" name="port" value="389" /></td></tr>';
    $str.= '<tr><td>'._('Protocol version:').'</td><td><input type="text" name="protocol" value="3" /></td></tr>';
    $str.= '<tr><td>'._('Server Host:').'</td><td><input type="text" name="host" value="'.$form['host'].'" /></td></tr>';
    $str.= '<tr><td>'._('Base DN:').'</td><td><input type="text" name="basedn" value="'.$form['domain'].'" /></td></tr>';
    $str.= '</table>';
    $str.= '</div>';
    $str.= '<br/><!-- useless => css padding bottom-->'."\n";

    $str.= '<div>';
    $str.= '<h3>'._('Users').'</h3>';
    $str.= '<table>';
    $str.= '<tr><td>'._('User branch :').'</td><td><input type="text" name="user_branch" value="" /></td></tr>';
    $str.= '<tr><td style="text-align: right;"><input type="checkbox" name="user_branch_recursive"/></td>';
    $str.= '<td>'._('Recursive Mode').'</td></tr>';
    $str.= '<tr><td>'._('Distinguished name field: ').'</td><td><input type="text" name="uidprefix" value="uid" /></td></tr>';
    $str.= '<tr><td>'._('Display name field: ').'</td><td><input type="text" name="uidprefix" value="cn" /></td></tr>';
    $str.= '</table>';

    $str.= '<div>';
    $str.= '<h4>'._('Administrator account').'</h4>';

    $str.= '<input type="checkbox" name="user_branch_recursive"/> Anonymous bind';
    $str.= '<table>';
    $str.= '<tr><td>'._('login (complete DN without base dn):').'</td><td><input type="text" name="admin_login" value="'.$form['admin_login'].'" /></td></tr>';
    $str.= '<tr><td>'._('bind password:').'</td><td><input type="password" name="admin_password" value="'.$form['password'].'" /></td></tr>';
    $str.= '</table>';
    $str.= '</div>';
    $str.= '</div>';
    $str.= '<br/><!-- useless => css-->'."\n";

    $str.= '<div>';
    $str.= '<h3>'._('User Groups').'</h3>';
    $str.= '<input type="radio" name="user_group" value="activedirectory"';
    if ($form['user_group'] == 'activedirectory')
      $str.= ' checked="checked"';
    $str.= ' />'._('Use LDAP User Groups using the MemberOf field');
    $str.= '<br/>';
    $str.= '<input type="radio" name="user_group" value="sql"';
    if ($form['user_group'] == 'sql')
      $str.= ' checked="checked"';
    $str.= '/>'._('Use Internal User Groups');
    $str.= '</div>';
    $str.= '<br/><!-- useless => css-->'."\n";

    $str.= '<div>';
    $str.= '<h3>'._('Home Directory').'</h3>';
    $str.= '<input type="radio" name="homedir" value="local"';
    if ($form['homedir'] == 'local')
      $str.= ' checked="checked"';
    $str.= '/>';
    $str.= _('Use Internal home directory (no server replication)');
    $str.= '<br/>';
    $str.= '<input type="radio" name="homedir" value="ad_profile"';
    if ($form['homedir'] == 'ad_profile')
      $str.= ' checked="checked"';
    $str.= '/>';
    $str.= _('Use CIFS link using the LDAP field :').' <input type="text" name="homedir" value=""/>';
    $str.= '<br/>';
    $str.= '<input type="radio" name="homedir" value="ad_homedir"';
    if ($form['homedir'] == 'ad_homedir')
      $str.= ' checked="checked"';
    $str.= '/>';
    $str.= _('Use NFS link using the LDAP field :').' <input type="text" name="homedir" value=""/>';
    $str.= '</div>';
    $str.= '<br/><!-- useless => css-->'."\n";

    return $str;
  }

}
