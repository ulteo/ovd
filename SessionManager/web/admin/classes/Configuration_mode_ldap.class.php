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
    $old = $oldprefs->get('UserDB', 'ldap');
    $new = $newprefs->get('UserDB', 'ldap');

    $changed = False;
    foreach(array('host', 'suffix', 'userbranch') as $key) {
      if ($old[$key] != $new[$key]) {
	$changed = True;
	break;
      }
    }

    $old = $oldprefs->get('UserGroupDB', 'enable');
    $new = $newprefs->get('UserGroupDB', 'enable');
    $g = !($old == 'sql' && $new == 'sql');

    return array($changed, $changed && $g);
  }

  public function form_valid($form) {
    $fields =  array('host', 'suffix', 'user_branch',
		     'port', 'proto',
		     'bind_dn', 'bind_password',
		     'field_rdn', 'field_displayname',
		     'user_group');

    foreach($fields as $field) {
      if (! isset($form[$field])) {
	return False;
      }
    }

    if (! in_array($form['user_group'], array('ldap_memberof', 'ldap_posix', 'sql')))
      return False;

    return True;
  }

  public function form_read($form, $prefs) {
    $config = array();
    $config['host'] = $form['host'];
    $config['suffix'] = $form['suffix'];
    $config['port'] = $form['port'];
    $config['protocol_version'] = $form['proto'];


    if (isset($form['bind_anonymous'])) {
      $config['login'] = '';
      $config['password'] = '';
    } else {
      $config['login'] = $form['bind_dn'];
      $config['password'] = $form['bind_password'];

    if (! str_endswith($config['login'], ','.$config['suffix']))
      $config['login'].= ','.$config['suffix'];
    }

    $config['userbranch'] = $form['user_branch'];
    $config['uidprefix'] = $form['field_rdn'];
    $config['match'] = array();
    $config['match']['login'] = $form['field_rdn'];
    $config['match']['displayname'] = $form['field_displayname'];
    
    if ($form['user_group'] == 'ldap_memberof')
      $config['match']['memberof'] = 'memberof';

    // Select LDAP as UserDB
    $prefs->set('UserDB', 'enable', 
        array('enable' => 'ldap'));

    // Push LDAP conf
    $prefs->set('UserDB', 'ldap', $config);

    // Select Module for UserGroupDB
    $prefs->set('UserGroupDB', 'enable',
        array('enable' => $form['user_group']));
    // Set the FS type
    $prefs->set('plugins', 'FS',
        array('FS' => 'local'));

    return True;
  }

  public function config2form($prefs) {
    $form = array();
    $config = $prefs->get('UserDB', 'ldap');

    $form['host'] = $config['host'];
    $form['suffix'] = $config['suffix'];
    $form['port'] = ($config['port']=='')?'389':$config['port'];
    $form['proto'] = ($config['protocol_version']=='')?'3':$config['protocol_version'];

    if ($config['login'] == '')
      $form['bind_anonymous'] = 1;
    $form['bind_dn'] = $config['login'];
    $form['bind_password'] = $config['password'];
    $buf = ','.$form['suffix'];
    if (str_endswith($form['bind_dn'], $buf))
      $form['bind_dn'] = substr($form['bind_dn'], 0, strlen($form['bind_dn']) - strlen($buf));

    $form['user_branch'] = $config['userbranch'];
    //$form['user_branch_recursive'] = No Yet Implementd


    $form['field_rdn'] = $config['uidprefix'];
    $form['field_displayname'] = $config['match']['displayname'];

    $config2 = $prefs->get('UserGroupDB', 'enable');
    if ($config2 == 'ldap_memberof')
      $form['user_group'] = 'ldap_memberof';
     elseif($config2 == 'ldap_posix')
       $form['user_group'] = 'ldap_posix';
    else
      $form['user_group'] = 'sql';


    // $form['homedir'] = '';
    return $form;
  }

  public function display($form) {
    $str= '<h1>'._('Lightweight Directory Access Protocol (LDAP)').'</h1>';

    $str.= '<div>';
    $str.= '<h3>Server</h3>';
    $str.= '<table>';
    $str.= '<tr><td>'._('Server Host:').'</td><td><input type="text" name="host" value="'.$form['host'].'" /></td></tr>';
    $str.= '<tr><td>'._('Server Port:').'</td><td><input type="text" name="port" value="'.$form['port'].'" /></td></tr>';
    $str.= '<tr><td>'._('Protocol version:').'</td><td><input type="text" name="proto" value="'.$form['proto'].'" /></td></tr>';
    $str.= '<tr><td>'._('Base DN:').'</td><td><input type="text" name="suffix" value="'.$form['suffix'].'" /></td></tr>';
    $str.= '</table>';
    $str.= '</div>';
    $str.= '<br/><!-- useless => css padding bottom-->'."\n";

    $str.= '<div>';
    $str.= '<h3>'._('Users').'</h3>';
    $str.= '<table>';
    $str.= '<tr><td>'._('User branch:').'</td><td><input type="text" name="user_branch" value="'.$form['user_branch'].'" /></td></tr>';

    // Not yet Implemented
    // $str.= '<tr><td style="text-align: right;"><input type="checkbox" name="user_branch_recursive"/></td>';
    // $str.= '<td>'._('Recursive Mode').'</td></tr>';

    $str.= '<tr><td>'._('Distinguished name field:').'</td><td><input type="text" name="field_rdn" value="'.$form['field_rdn'].'" /></td></tr>';
    $str.= '<tr><td>'._('Display name field:').'</td><td><input type="text" name="field_displayname" value="'.$form['field_displayname'].'" /></td></tr>';
    $str.= '</table>';

    $str.= '<div>';
    $str.= '<h4>'._('Administrator account').'</h4>';
    $str.= '<table>';
    $str.= '<tr><td style="text-align: right;">';
    $str.= '<input type="checkbox" name="bind_anonymous"';
    if (isset($form['bind_anonymous']))
      $str.= ' checked="checked"';
    $str.= '/></td><td>Anonymous bind';
    $str.= '</td></tr>';
    $str.= '<tr><td>'._('Bind DN (without suffix):').'</td><td><input type="text" name="bind_dn" value="'.$form['bind_dn'].'" /></td></tr>';
    $str.= '<tr><td>'._('Bind password:').'</td><td><input type="password" name="bind_password" value="'.$form['bind_password'].'" /></td></tr>';
    $str.= '</table>';
    $str.= '</div>';
    $str.= '</div>';
    $str.= '<br/><!-- useless => css-->'."\n";

    $str.= '<div>';
    $str.= '<h3>'._('User Groups').'</h3>';
    $str.= '<input type="radio" name="user_group" value="ldap_memberof"';
    if ($form['user_group'] == 'ldap_memberof')
      $str.= ' checked="checked"';
    $str.= ' />'._('Use LDAP User Groups using the MemberOf field');
    $str.= '<br/>';
        $str.= '<input type="radio" name="user_group" value="ldap_posix"';
    if ($form['user_group'] == 'ldap_posix')
      $str.= ' checked="checked"';
    $str.= ' />'._('Use LDAP User Groups using Posix group');
    $str.= '<br/>';
    $str.= '<input type="radio" name="user_group" value="sql"';
    if ($form['user_group'] == 'sql')
      $str.= ' checked="checked"';
    $str.= '/>'._('Use Internal User Groups');
    $str.= '</div>';
    $str.= '<br/><!-- useless => css-->'."\n";

    // Not yet Implemented
    /*
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
    */

    return $str;
  }

}
