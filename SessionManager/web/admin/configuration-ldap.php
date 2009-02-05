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

require_once(dirname(__FILE__).'/includes/core.inc.php');

if (isset($_POST['config']) && $_POST['config'] == 'ad') {
  $r = form2obj($_POST);
  if ($r===false)
    echo 'error';

  $_SESSION['save_succed'] = true;
  redirect($_SERVER["PHP_SELF"]);
}

display();


function display() {
  require_once('header.php');

  echo '<h1>'._('Lightweight Directory Access Protocol (LDAP)');
  echo '</h1>';
  echo '<form method="post">';
  echo '<input type="hidden" name="config" value="ad">';

  echo '<div>';
  echo '<h3>Server</h3>';
  echo '<table>';
  echo '<tr><td>'._('Server Host:').'</td><td><input type="text" name="host" value="'.$form['host'].'" /></td></tr>';
  echo '<tr><td>'._('Server Port:').'</td><td><input type="text" name="port" value="389" /></td></tr>';
  echo '<tr><td>'._('Protocol version:').'</td><td><input type="text" name="protocol" value="3" /></td></tr>';
  echo '<tr><td>'._('Server Host:').'</td><td><input type="text" name="host" value="'.$form['host'].'" /></td></tr>';
  echo '<tr><td>'._('Base DN:').'</td><td><input type="text" name="basedn" value="'.$form['domain'].'" /></td></tr>';
  echo '</table>';
  echo '</div>';
  echo '<br/><!-- useless => css padding bottom-->'."\n";


  echo '<div>';
  echo '<h3>'._('Users').'</h3>';
  echo '<table>';
  echo '<tr><td>'._('User branch :').'</td><td><input type="text" name="user_branch" value="" /></td></tr>';
  echo '<tr><td style="text-align: right;"><input type="checkbox" name="user_branch_recursive"/></td><td>'._('Recursive Mode').'</td></tr>';
  echo '<tr><td>'._('Distinguished name field: ').'</td><td><input type="text" name="uidprefix" value="uid" /></td></tr>';
  echo '<tr><td>'._('Display name field: ').'</td><td><input type="text" name="uidprefix" value="cn" /></td></tr>';
  echo '</table>';



     
  echo '<div>';
  echo '<h4>'._('Administrator account').'</h4>';

  echo '<input type="checkbox" name="user_branch_recursive"/> Anonymous bind';
  echo '<table>';
  echo '<tr><td>'._('login (complete DN without base dn):').'</td><td><input type="text" name="admin_login" value="'.$admin_login.'" /></td></tr>';
  echo '<tr><td>'._('bind password:').'</td><td><input type="password" name="admin_password" value="'.$form['password'].'" /></td></tr>';
  echo '</table>';
  echo '</div>';
  echo '</div>';
  echo '<br/><!-- useless => css-->'."\n";

  echo '<div>';
  echo '<h3>'._('User Groups').'</h3>';
  echo '<input type="radio" name="user_group" value="activedirectory" '.$checked[$user_group_ad].' />'._('Use LDAP User Groups using the MemberOf field');
  echo '<br/>';
  echo '<input type="radio" name="user_group" value="sql" '.$checked[!$user_group_ad].'/>'._('Use Internal User Groups');
  echo '</div>';
  echo '<br/><!-- useless => css-->'."\n";

  echo '<div>';
  echo '<h3>'._('Home Directory').'</h3>';
  echo '<input type="radio" name="homedir" value="local" '.$checked[$home[0]].'/>';
  echo _('Use Internal home directory (no server replication)');
  echo '<br/>';
  echo '<input type="radio" name="homedir" value="ad_profile" '.$checked[$home[1]].'/>';
  echo _('Use CIFS link using the LDAP field :').' <input type="text" name="homedir" value=""/>';
  echo '<br/>';
  echo '<input type="radio" name="homedir" value="ad_homedir" '.$checked[$home[2]].'/>';
  echo _('Use NFS link using the LDAP field :').' <input type="text" name="homedir" value=""/>';
  echo '</div>';
  echo '<br/><!-- useless => css-->'."\n";

  echo '<div style="display:none">';
  echo '<h3>'._('Windows Applications').'</h3>';
  echo _('Allow Windows Application link thanks to TS and AD:');
  echo '<input type="radio" name="ts_link" value="yes" checked="checked"/>';
  echo _('yes');
  echo '<input type="radio" name="ts_link" value="no" />'._('no');
  echo '</div>';
  echo '<br/><!-- useless => css-->'."\n";

  echo '<br/>';
  echo '<input type="submit" value="Save"/>';
  echo '</form>';

  require_once('footer.php');
  die();
}
