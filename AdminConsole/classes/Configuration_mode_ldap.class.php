<?php
/**
 * Copyright (C) 2009-2014 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2009-2011, 2013
 * Author Laurent CLOUET <laurent@ulteo.com> 2009-2011
 * Author David LECHEVALIER <david@ulteo.com> 2014
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
    foreach(array('hosts', 'suffix', 'userbranch') as $key) {
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
		     'port', 
		     'bind_dn', 'bind_password',
		     'field_rdn', 'field_displayname', 'field_countrycode', 'field_filter',
		     'group_branch_dn',
		     'user_group');

    foreach($fields as $field) {
      if (! isset($form[$field])) {
	return False;
      }
    }

    if (! in_array($form['user_group'], array('ldap', 'sql')))
      return False;

    return True;
  }

  public function form_read($form, $prefs) {
    $config = array();
    $config['hosts'] = array($form['host'], $form['host2']);
    $config['suffix'] = $form['suffix'];
    $config['port'] = $form['port'];
    $config['options'] = array('LDAP_OPT_PROTOCOL_VERSION' => '3');


    if (isset($form['bind_anonymous'])) {
      $config['login'] = '';
      $config['password'] = '';
    } else {
      $config['login'] = $form['bind_dn'];
      $config['password'] = $form['bind_password'];

    if (! str_endswith($config['login'], ','.$config['suffix']))
      $config['login'].= ','.$config['suffix'];
    }

    $config['ou'] = $form['user_branch'];
    $config['filter'] = $form['field_filter'];
    $config['match'] = array();
    $config['match']['login'] = $form['field_rdn'];
    $config['match']['displayname'] = $form['field_displayname'];
    if ( $form['field_countrycode'] != '')
      $config['match']['countrycode'] = $form['field_countrycode'];

    // Enable modules
    $module_to_enable = array('SessionManagement', 'UserDB', 'UserGroupDB');
    $module_enabled = $prefs->get('general', 'module_enable');
    $prefs->set('general', 'module_enable', array_unique(array_merge($module_enabled, $module_to_enable)));
    
    // Select LDAP as UserDB
    $prefs->set('UserDB', 'enable', 'ldap');

    // Push LDAP conf
    $prefs->set('UserDB', 'ldap', $config);

    // Select Module for UserGroupDB
    $prefs->set('UserGroupDB', 'enable', $form['user_group']);

	if ($form['user_group'] == 'ldap') {
		if (! array_key_exists('group_match_using', $form)) {
			$form['group_match_using'] = array();
		}
		elseif(! is_array($form['group_match_using'])) {
			$form['group_match_using'] = array($form['group_match_using']);
		}
		
		$settings = array(
			'ou' => $form['group_branch_dn'],
			'filter' => $form['group_filter'],
			'match' => array(
				'name' => $form['group_field_name'],
				'description' => $form['group_field_desc'],
			),
			
			'group_match_user' => $form['group_match_using'],
			'user_field' => $form['group_match_using_user_value'],
			'user_field_type' => $form['group_match_using_user_type'],
			'group_field' => $form['group_match_using_group_value'],
			'group_field_type' => $form['group_match_using_group_type'],
		);
		
		$prefs->set('UserGroupDB', 'ldap', $settings);
	}
    
    // Set the Session Management module
    $prefs->set('SessionManagement', 'enable', 'internal');

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
    $form['suffix'] = $config['suffix'];
    $form['port'] = ($config['port']=='')?'389':$config['port'];

    if ($config['login'] == '')
      $form['bind_anonymous'] = 1;
    $form['bind_dn'] = $config['login'];
    $form['bind_password'] = $config['password'];
    $buf = ','.$form['suffix'];
    if (str_endswith($form['bind_dn'], $buf))
      $form['bind_dn'] = substr($form['bind_dn'], 0, strlen($form['bind_dn']) - strlen($buf));

    $form['user_branch'] = $config['ou'];
    //$form['user_branch_recursive'] = No Yet Implementd


    $form['field_rdn'] = '';
    if (isset($config['match']['login']))
      $form['field_rdn'] = $config['match']['login'];
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
    if ($config2 == 'ldap')
      $form['user_group'] = 'ldap';
    else
      $form['user_group'] = 'sql';

    $form['group_branch_dn'] = '';
    $buf = $prefs->get('UserGroupDB', 'ldap');
    if (isset($buf['ou']))
      $form['group_branch_dn'] = $buf['ou'];

	$form['group_filter'] = $buf['filter'];
	$form['group_field_name'] = $buf['match']['name'];
	$form['group_field_desc'] = $buf['match']['description'];
	$form['group_match_using'] = $buf['group_match_user'];
	$form['group_match_using_group_value'] = $buf['group_field'];
	$form['group_match_using_group_type'] = $buf['group_field_type'];
	$form['group_match_using_user_value'] = $buf['user_field'];
	$form['group_match_using_user_type'] = $buf['user_field_type'];

    return $form;
  }

  public function display($form) {
		if (! array_key_exists('group_match_using', $form)) {
			$form['group_match_using'] = array();
		}
		elseif(! is_array($form['group_match_using'])) {
			$form['group_match_using'] = array($form['group_match_using']);
		}


    $str= '<h1>'._('Lightweight Directory Access Protocol (LDAP)').'</h1>';

    $str.= '<div class="section">';
    $str.= '<h3>'._('Server').'</h3>';
    $str.= '<table>';
    $str.= '<tr><td>'._('Primary Host:').'</td><td><input type="text" name="host" value="'.$form['host'].'" /></td></tr>';
    $str.= '<tr><td>'._('Secondary Host:').'</td><td><input type="text" name="host2" value="'.$form['host2'].'" /></td>';
    $str.= '<td><span style="font-size: 0.9em; font-style: italic;">('._('optional').')</span></td>';
    $str.= '</tr>';
    $str.= '<tr><td>'._('Server Port:').'</td><td><input type="text" name="port" value="'.$form['port'].'" /></td></tr>';
    $str.= '<tr><td>'._('Base DN:').'</td><td><input type="text" name="suffix" value="'.$form['suffix'].'" /></td></tr>';
    $str.= '</table>';
    $str.= '</div>';

    $str.= '<div class="section">';
    $str.= '<h3>'._('Authentication (require a user with read right on the tree)').'</h3>';
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
    $str.= '<h3>'._('Users').'</h3>';
    $str.= '<table>';
    $str.= '<tr><td>'._('Filter:').'</td><td><input type="text" name="field_filter" value="'.$form['field_filter'].'" /></td></tr>';
    $str.= '<tr><td>'._('Specific OU (optionnal):').'</td><td><input type="text" name="user_branch" value="'.$form['user_branch'].'" /></td></tr>';
    $str.= '<tr><td>'._('Distinguished name field:').'</td><td><input type="text" name="field_rdn" value="'.$form['field_rdn'].'" /></td></tr>';
    $str.= '<tr><td>'._('Display name field:').'</td><td><input type="text" name="field_displayname" value="'.$form['field_displayname'].'" /></td></tr>';
    $str.= '<tr><td>'._('Locale field').'('._('optional').'):</td><td><input type="text" name="field_countrycode" value="'.$form['field_countrycode'].'" /></td></tr>';

    $str.= '</table>';
    $str.= '</div>';

    $str.= '<div class="section">';
    $str.= '<h3>'._('User Groups').'</h3>';

    $str.= '<input class="input_radio" type="radio" name="user_group" value="sql"';
    if ($form['user_group'] == 'sql')
      $str.= ' checked="checked"';
    $str.= '/>'._('Use Internal User Groups');
    $str.= '<br/>';

	$str.= '<input class="input_radio" type="radio" name="user_group" value="ldap"';
	if ($form['user_group'] == 'ldap')
	  $str.= ' checked="checked"';
	$str.= ' />'._('Use LDAP User Groups');
	$str.= '<br/>';
	$str.= '<table style="padding-left: 3%;">';
	$str.= '<tr><td>'._('Filter:').'</td><td><input type="text" name="group_filter" value="'.$form['group_filter'].'" /></td></tr>';
	$str.= '<tr><td>'._('Specific OU (optionnal):').'</td><td><input type="text" name="group_branch_dn" value="'.$form['group_branch_dn'].'"/></td></tr>';
	$str.= '<tr><td>'._('Name field:').'</td><td><input type="text" name="group_field_name" value="'.$form['group_field_name'].'" /></td></tr>';
	$str.= '<tr><td>'._('Description field (Optionnal):').'</td><td><input type="text" name="group_field_desc" value="'.$form['group_field_desc'].'" /></td></tr>';
	$str.= '<tr><td colspan="2"><input type="checkbox" name="group_match_using[]" value="user_field"';
	if (in_array('user_field', $form['group_match_using'])) {
	    $str.= ' checked="checked"';
	}
	$str.='/>'._('Use the following field from the user entry').'</td></tr>';
	$str.= '<tr><td></td><td><input type="text" name="group_match_using_user_value" value="'.$form['group_match_using_user_value'].'" />';
	$str.=' '._('which is').' ';
	$str.='<select name="group_match_using_user_type">';
	$str.='<option value="group_dn"';
	if ($form['group_match_using_group_type'] == 'group_dn') {
	    $str.= ' selected="selected"';
	}
	$str.='>'._('Group DN').'</option>';
	$str.='<option value="group_name"';
	if ($form['group_match_using_group_type'] == 'group_name') {
	    $str.= ' selected="selected"';
	}
	$str.='>'._('Group name').'</option</select></td></tr>';
	$str.= '<tr><td colspan="2"><input type="checkbox" name="group_match_using[]" value="group_field"';
	if (in_array('group_field', $form['group_match_using'])) {
	    $str.= ' checked="checked"';
	}
	$str.=' />'._('Use the following field from the group entry').'</td></tr>';
	$str.= '<tr><td></td><td><input type="text" name="group_match_using_group_value" value="'.$form['group_match_using_group_value'].'" />';
	$str.=' '._('which is').' ';
	$str.='<select name="group_match_using_group_type">';
	$str.='<option value="user_dn"';
	if ($form['group_match_using_group_type'] == 'user_dn') {
	    $str.= ' selected="selected"';
	}
	$str.='>'._('User DN').'</option>';
	$str.='<option value="user_login"';
	if ($form['group_match_using_group_type'] == 'user_login') {
	    $str.= ' selected="selected"';
	}
	$str.='>'._('User login').'</option</select></td></tr>';
	$str.= '</table>';
    $str.= '</div>';

    return $str;
  }

  public function display_sumup($prefs) {
    $form = $this->config2form($prefs);
    $str = '';

    if (in_array(strtolower(parse_url($form['host'], PHP_URL_SCHEME)), array('ldap', 'ldaps')))
      $ldap_url = $form['host'];
    else
      $ldap_url = 'ldap://'.$form['host'];
    
    if ($form['port'] != 389)
      $ldap_url.= ':'.$form['port'];
    $ldap_url.= '/'.$form['suffix'];

    $str.= '<ul>';
    $str.= '<li><strong>'._('Server:').'</strong> '.$ldap_url.'</li>';;
    $str.= '<li><strong>'._('Administrator account').'</strong> '.$form['bind_dn'].'</li>';
    $str.= '<li><strong>'._('User branch:').'</strong> '.$form['user_branch'].'</li>';;

    $str.= '<li><strong>'._('User Groups:').'</strong> ';
    if ($form['user_group'] == 'ldap')
      $str.= _('Use LDAP User Groups');
    elseif ($form['user_group'] == 'sql')
      $str.= _('Use Internal User Groups');
    $str.= '</li>';
    $str.= '</ul>';

    return $str;
  }
}
