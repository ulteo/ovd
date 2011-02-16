<?php
/**
 * Copyright (C) 2009-2011 Ulteo SAS
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

class Configuration_mode_internal extends Configuration_mode {

  public function getPrettyName() {
    return _('Internal');
  }

  public function careAbout($sessionmanagement_) {
    return $sessionmanagement_ == 'internal';
  }

  public function has_change($oldprefs, $newprefs) {
    $old = $oldprefs->get('UserDB', 'enable');
    $new = $newprefs->get('UserDB', 'enable');

    return array($old==$new, False);
  }

  public function form_valid($form) {
    return True;
  }

  public function form_read($form, $prefs) {
    // Enable modules
    $module_to_enable = array('SessionManagement', 'UserDB', 'UserGroupDB', 'UserGroupDBDynamic', 'UserGroupDBDynamicCached', 'ProfileDB', 'SharedFolderDB');
    $module_enabled = $prefs->get('general', 'module_enable');
    $prefs->set('general', 'module_enable', array_unique(array_merge($module_enabled, $module_to_enable)));
    
    // Select Module as UserDB
    $prefs->set('UserDB', 'enable', 'sql');


    // Select Module for UserGroupDB
    $prefs->set('UserGroupDB', 'enable', 'sql');
    
    // Select Module for UserGroupDB
    $prefs->set('UserGroupDBDynamic', 'enable', 'internal');
    
    // Select Module for UserGroupDB
    $prefs->set('UserGroupDBDynamicCached', 'enable', 'internal');
    
    // Set the Session Management module
    $prefs->set('SessionManagement', 'enable', 'internal');

    return True;
  }


  public function config2form($prefs) {
    $form = array();
    $config = $prefs->get('UserDB', 'enable');

    return $form;
  }

  public function display($form) {
    $str= '<h1>'._('Internal Database Profiles').'</h1>';

    $str.= '<div class="section">';
    $str.= _('This is the default profile manager. This profile manager saves all the data into a the same SQL database as you defined it in the system configuration.');
    $str.= '</div>';

    return $str;
  }

  public function display_sumup($prefs) {
    $config = $prefs->get('UserDB', 'enable');

    $str = '';
    if ($config == 'sql')
      $str.= _('Use a dynamic internal User Group');

    return $str;
  }

}
