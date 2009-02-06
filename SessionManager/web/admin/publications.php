<?php
/**
 * Copyright (C) 2008 Ulteo SAS
 * http://www.ulteo.com
 * Author Jeremy DESVAGES <jeremy@ulteo.com>
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

show_default();

function show_default() {
  $publications = array();

  $groups_apps = getAllAppsGroups();
  if (is_null($groups_apps))
      $groups_apps = array();
  foreach($groups_apps as $i => $group_apps) {
    if (! $group_apps->published)
      unset($groups_apps[$i]);
  }

  $groups_users = get_all_usergroups();
  foreach($groups_users as $i => $group_users) {
    if (! $group_users->published)
      unset($groups_users[$i]);
  }


  foreach($groups_users as $group_users) {
    foreach($group_users->appsGroups() as $group_apps) {
          if (! $group_apps->published)
	    continue;
      $publications[]= array('user' => $group_users, 'app' => $group_apps);
    }
  }

  $has_publish = count($publications);

  $can_add_publish = true;
  if (count($groups_users) == 0)
    $can_add_publish = false;
  elseif (count($groups_apps) == 0)
    $can_add_publish = false;
  elseif (count($groups_users) * count($groups_apps) <= count($publications))
    $can_add_publish = false;

  $count = 0;

  include_once('header.php');
//   echo '<div class="container rounded" style="background: #fff; width: 98%; margin-left: auto; margin-right: auto;">';

  echo '<table style="width: 98.5%; margin-left: 10px; margin-right: 10px;" border="0" cellspacing="0" cellpadding="0">';
  echo '<tr>';
  echo '<td style="width: 150px; text-align: center; vertical-align: top; background: url(\'media/image/submenu_bg.png\') repeat-y right;">';
  include_once(dirname(__FILE__).'/submenu/publications.php');
  echo '</td>';
  echo '<td style="text-align: center; vertical-align: top;">';
  echo '<div class="container" style="background: #fff; border-top: 1px solid  #ccc; border-right: 1px solid  #ccc; border-bottom: 1px solid  #ccc;">';

  echo '<div>';
  echo '<h1>'._('Publications').'</h1>';

  echo '<table class="main_sub sortable" id="publications_list_table" border="0" cellspacing="1" cellpadding="5">';
  echo '<tr class="title">';
  echo '<th>'._('Users group').'</th>';
  echo '<th>'._('Application group').'</th>';
  echo '</tr>';

  if (! $has_publish) {
    $content = 'content'.(($count++%2==0)?1:2);
    echo '<tr class="'.$content.'"><td colspan="3">'._('No publication').'</td></tr>';
  } else {
    foreach($publications as $publication){
      $content = 'content'.(($count++%2==0)?1:2);
      $group_u = $publication['user'];
      $group_a = $publication['app'];

      echo '<tr class="'.$content.'">';
      echo '<td><a href="usersgroup.php?action=manage&id='.$group_u->id.'">'.$group_u->name.'</a></td>';
      echo '<td><a href="appsgroup.php?action=manage&id='.$group_a->id.'">'.$group_a->name.'</a></td>';

      echo '<td><form action="actions.php" metthod="post" onsubmit="return confirm(\''._('Are you sure you want to delete this publication?').'\');">';

      echo '<input type="hidden" name="action" value="del" />';
      echo '<input type="hidden" name="name" value="Publication" />';
      echo '<input type="hidden" name="group_a" value="'.$group_a->id.'" />';
      echo '<input type="hidden" name="group_u" value="'.$group_u->id.'" />';
      echo '<input type="submit" value="'._('Delete').'"/>';
      echo '</form></td>';
      echo '</tr>';
    }
  }


  if ($can_add_publish) {
    $content = 'content'.(($count++%2==0)?1:2);

    echo '<tfoot>';
    echo '<tr class="'.$content.'">';
    echo '<form action="actions.php" method="post">';
    echo '<input type="hidden" name="action" value="add" />';
    echo '<input type="hidden" name="name" value="Publication" />';

    echo '<td>';
    echo '<select name="group_u">';
    foreach($groups_users as $group_users)
      echo '<option value="'.$group_users->id.'" >'.$group_users->name.'</option>';
    echo '</select>';
    echo '</td>';

    echo '<td>';
    echo '<select name="group_a">';
    foreach($groups_apps as $group_apps)
      echo '<option value="'.$group_apps->id.'" >'.$group_apps->name.'</option>';
    echo '</select>';
    echo '</td>';
    echo '<td><input type="submit" value="'._('Add').'" /></td>';
    echo '</form>';
    echo '</tr>';
    echo '</tfoot>';
  }

  echo '</table>';
  echo '<br /><br /><br />';
  echo '</div>';

  echo '</div>';
  echo '</div>';
  echo '</td>';
  echo '</tr>';
  echo '</table>';
  include_once('footer.php');
}
