<?php
/**
 * Copyright (C) 2009-2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com>
 * Author Laurent CLOUET <laurent@ulteo.com>
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

  public function careAbout($sessionmanagement_) {
    return 'ldap' == $sessionmanagement_;
  }

  public function has_change($oldprefs, $newprefs) {
    $old = $oldprefs->get('UserDB', 'ldap');
    $new = $newprefs->get('UserDB', 'ldap');

    $changed = False;
    foreach(array('host', 'suffix', 'userbranch', 'uidprefix') as $key) {
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
		     'field_rdn', 'field_displayname', 'field_countrycode', 'field_filter',
		     'group_branch_dn',
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
    $config['filter'] = $form['field_filter'];
    $config['match'] = array();
    $config['match']['login'] = $form['field_rdn'];
    $config['match']['displayname'] = $form['field_displayname'];
    if ( $form['field_countrycode'] != '')
      $config['match']['countrycode'] = $form['field_countrycode'];

    if ($form['user_group'] == 'ldap_memberof')
      $config['match']['memberof'] = 'memberOf';

    // Select LDAP as UserDB
    $prefs->set('UserDB', 'enable', 'ldap');

    // Push LDAP conf
    $prefs->set('UserDB', 'ldap', $config);

    // Select Module for UserGroupDB
    $prefs->set('UserGroupDB', 'enable', $form['user_group']);

    if ($form['user_group'] == 'ldap_posix') {
      $prefs->set('UserGroupDB', 'ldap_posix',
		  array('group_dn' => $form['group_branch_dn']));
    }
    
    // Set the Session Management module
    $prefs->set('SessionManagement', 'enable', 'ldap');

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
    if (isset($config['match']['displayname']))
      $form['field_displayname'] = $config['match']['displayname'];
    else
      $form['field_displayname'] = '';
    if (isset($config['match']['countrycode']))
      $form['field_countrycode'] = $config['match']['countrycode'];
    else
      $form['field_countrycode'] = '';
    if (isset($config['filter']))
      $form['field_filter'] = $config['filter'];
    else
      $form['field_filter'] = '';
    

    $config2 = $prefs->get('UserGroupDB', 'enable');
    if ($config2 == 'ldap_memberof')
      $form['user_group'] = 'ldap_memberof';
     elseif($config2 == 'ldap_posix')
       $form['user_group'] = 'ldap_posix';
    else
      $form['user_group'] = 'sql';

    $form['group_branch_dn'] = '';
    $buf = $prefs->get('UserGroupDB', 'ldap_posix');
    if (isset($buf['group_dn']))
      $form['group_branch_dn'] = $buf['group_dn'];

    return $form;
  }

  public function display($form) {
    $str= '<h1>'._('Lightweight Directory Access Protocol (LDAP)').'</h1>';

    $str.= '<div class="section">';
    $str.= '<h3>Server</h3>';
    $str.= '<table>';
    $str.= '<tr><td>'._('Server Host:').'</td><td><input type="text" name="host" value="'.$form['host'].'" /></td></tr>';
    $str.= '<tr><td>'._('Server Port:').'</td><td><input type="text" name="port" value="'.$form['port'].'" /></td></tr>';
    $str.= '<tr><td>'._('Protocol version:').'</td><td><input type="text" name="proto" value="'.$form['proto'].'" /></td></tr>';
    $str.= '<tr><td>'._('Base DN:').'</td><td><input type="text" name="suffix" value="'.$form['suffix'].'" /></td></tr>';
    $str.= '</table>';
    $str.= '</div>';

    $str.= '<div class="section">';
    $str.= '<h3>'._('Users').'</h3>';
    $str.= '<table>';
    $str.= '<tr><td>'._('User branch:').'</td><td><input type="text" name="user_branch" value="'.$form['user_branch'].'" /></td></tr>';

    // Not yet Implemented
    // $str.= '<tr><td style="text-align: right;"><input class="input_checkbox" type="checkbox" name="user_branch_recursive"/></td>';
    // $str.= '<td>'._('Recursive Mode').'</td></tr>';

    $str.= '<tr><td>'._('Distinguished name field:').'</td><td><input type="text" name="field_rdn" value="'.$form['field_rdn'].'" /></td></tr>';
    $str.= '<tr><td>'._('Display name field:').'</td><td><input type="text" name="field_displayname" value="'.$form['field_displayname'].'" /></td></tr>';
    $str.= '<tr><td>'._('Locale field').'('._('optional').'):</td><td><input type="text" name="field_countrycode" value="'.$form['field_countrycode'].'" /></td></tr>';
    $str.= '<tr><td>'._('Filter:').'</td><td><input type="text" name="field_filter" value="'.$form['field_filter'].'" /></td></tr>';
    $str.= '</table>';
    $str.= '</div>';

    $str.= '<div class="section">';
    $str.= '<h3>'._('Administrator account').'</h3>';
    $str.= '<table>';
    $str.= '<tr><td style="text-align: left;" colspan="2">';
    $str.= '<input class="input_checkbox" type="checkbox" name="bind_anonymous"';
    if (isset($form['bind_anonymous']))
      $str.= ' checked="checked"';
    $str.= '/> '._('Anonymous bind');
    $str.= '</td></tr>';
    $str.= '<tr><td>'._('Bind DN (without suffix):').'</td><td><input type="text" name="bind_dn" value="'.$form['bind_dn'].'" /></td></tr>';
    $str.= '<tr><td>'._('Bind password:').'</td><td><input type="password" name="bind_password" value="'.$form['bind_password'].'" /></td></tr>';
    $str.= '</table>';
    $str.= '</div>';

    $str.= '<div class="section">';
    $str.= '<h3>'._('User Groups').'</h3>';
    $str.= '<input class="input_radio" type="radio" name="user_group" value="ldap_memberof"';
    if ($form['user_group'] == 'ldap_memberof')
      $str.= ' checked="checked"';
    $str.= ' />'._('Use LDAP User Groups using the MemberOf field');
    $str.= '<br/>';
        $str.= '<input class="input_radio" type="radio" name="user_group" value="ldap_posix"';
    if ($form['user_group'] == 'ldap_posix')
      $str.= ' checked="checked"';
    $str.= ' />'._('Use LDAP User Groups using Posix groups');
    $str.= '<br/><div style="padding-left: 3%;">';
    $str.= _('Group Branch DN:').' <input type="text" name="group_branch_dn" value="'.$form['group_branch_dn'].'"/>';
    $str.= '</div>';

    $str.= '<div class="section">';
    $str.= '<input class="input_radio" type="radio" name="user_group" value="sql"';
    if ($form['user_group'] == 'sql')
      $str.= ' checked="checked"';
    $str.= '/>'._('Use Internal User Groups');
    $str.= '</div>';

    return $str;
  }

  public function display_sumup($prefs) {
    $form = $this->config2form($prefs);
    $str = '';

    $ldap_url = 'ldap://'.$form['host'];
    if ($form['port'] != 389)
      $ldap_url.= ':'.$form['port'];
    $ldap_url.= '/'.$form['suffix'];

    $str.= '<ul>';
    $str.= '<li><strong>'._('Server:').'</strong> '.$ldap_url.'</li>';;
    $str.= '<li><strong>'._('Administrator account').'</strong> '.$form['bind_dn'].'</li>';
    $str.= '<li><strong>'._('User branch:').'</strong> '.$form['user_branch'].'</li>';;

    $str.= '<li><strong>'._('User Groups:').'</strong> ';
    if ($form['user_group'] == 'ldap_memberof')
      $str.= _('Use LDAP User Groups using the MemberOf field');
    elseif ($form['user_group'] == 'ldap_posix')
      $str.= _('Use LDAP User Groups using Posix group');
    elseif ($form['user_group'] == 'sql')
      $str.= _('Use Internal User Groups');
    $str.= '</li>';
    $str.= '</ul>';

    return $str;
  }
}
