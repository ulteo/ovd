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

  public function has_change($oldprefs, $newprefs) {
    return array(True, True);
  }

  public function form_valid($form) {
    return True;
  }

  public function form_read($form, $prefs) {
    return True;
  }

  public function display($prefs) {
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
    $str.= '<tr><td>'._('login (complete DN without base dn):').'</td><td><input type="text" name="admin_login" value="'.$admin_login.'" /></td></tr>';
    $str.= '<tr><td>'._('bind password:').'</td><td><input type="password" name="admin_password" value="'.$form['password'].'" /></td></tr>';
    $str.= '</table>';
    $str.= '</div>';
    $str.= '</div>';
    $str.= '<br/><!-- useless => css-->'."\n";

    $str.= '<div>';
    $str.= '<h3>'._('User Groups').'</h3>';
    $str.= '<input type="radio" name="user_group" value="activedirectory" '.$checked[$user_group_ad].' />'._('Use LDAP User Groups using the MemberOf field');
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
    $str.= _('Use CIFS link using the LDAP field :').' <input type="text" name="homedir" value=""/>';
    $str.= '<br/>';
    $str.= '<input type="radio" name="homedir" value="ad_homedir" '.$checked[$home[2]].'/>';
    $str.= _('Use NFS link using the LDAP field :').' <input type="text" name="homedir" value=""/>';
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
