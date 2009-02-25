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

class Configuration_mode_internal extends Configuration_mode {

  public function getPrettyName() {
    return _('Internal');
  }

  public function careAbout($userDB) {
    return in_array($userDB, array('fake', 'sql'));
  }

  public function has_change($oldprefs, $newprefs) {
    $old = $oldprefs->get('UserDB', 'enable');
    $new = $newprefs->get('UserDB', 'enable');

    return array($old==$new, False);
  }

  public function form_valid($form) {
    if (! isset($form['user']))
      return False;

    if (! in_array($form['user'], array('fake', 'sql')))
      return False;

    return True;
  }

  public function form_read($form, $prefs) {
    // Select Module as UserDB
    $prefs->set('UserDB', 'enable',
		array('enable' => $form['user']));


    // Select Module for UserGroupDB
    $prefs->set('UserGroupDB', 'enable',
		array('enable' => 'sql'));


    // Set the FS type
    $prefs->set('plugins', 'FS',
		array('FS' => 'local'));

    return True;
  }


  public function config2form($prefs) {
    $form = array();
    $config = $prefs->get('UserDB', 'enable');

    $form['user'] = ($config == 'sql')?'sql':'fake';
    return $form;
  }

  public function display($form) {
    $str= '<h1>'._('Internal Database Profiles').'</h1>';

    $str.= '<div>';
    $str.= _('This is the default Profile manager. This profile manager aave all the data into a the same SQL database as your defined in the system configuration ...');
    $str.= '</div>';
    $str.= '<br/><!-- useless => css padding bottom-->'."\n";

    $str.= '<div>';
    $str.= '<h3>'._('Users').'</h3>';
    $str.= '<input class="input_radio" type="radio" name="user" value="fake"';
    if ($form['user'] == 'fake')
      $str.= ' checked="checked"';
    $str.= '/>'._('Use a static user list (usefull for test)');
    $str.= '<br/>';
    $str.= '<input class="input_radio" type="radio" name="user" value="sql"';
    if ($form['user'] == 'sql')
      $str.= ' checked="checked"';
    $str.= '/>'._('I want to create my own users');
    $str.= '</div>';

    return $str;
  }

}
