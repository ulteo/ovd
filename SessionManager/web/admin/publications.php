<?php
/**
 * Copyright (C) 2008-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2009
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2008
 * Author Samuel BOVEE <samuel@ulteo.com> 2010
 * Author Julien LANGLOIS <julien@ulteo.com> 2011
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
require_once(dirname(__FILE__).'/includes/page_template.php');

if (! checkAuthorization('viewPublications'))
	redirect('index.php');


show_default();

function show_default() {
  $applicationsGroupDB = ApplicationsGroupDB::getInstance();
  $publications = array();

  $groups_apps = $applicationsGroupDB->getList(true);
  if (is_null($groups_apps))
      $groups_apps = array();
  foreach($groups_apps as $i => $group_apps) {
    if (! $group_apps->published)
      unset($groups_apps[$i]);
  }

  $usergroupdb = UserGroupDB::getInstance();
  $groups_users = $usergroupdb->getList(true);
  foreach($groups_users as $i => $group_users) {
    if (! $group_users->published)
      unset($groups_users[$i]);
  }


	// Starts from the applications groups instead of users groups because 
	// it's possible to not be able to have the complete users groups list (LDAP)
	foreach($groups_apps as $group_apps) {
		foreach($group_apps->userGroups() as $group_users) {
			if (! $group_users->published)
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

	$can_manage_publications = isAuthorized('managePublications');

  page_header(array('js_files' => array('media/script/publication.js')));


  echo '<div>';
  echo '<h1>'._('Publications').'</h1>';

  echo '<table class="main_sub sortable" id="publications_list_table" border="0" cellspacing="1" cellpadding="5">';
  echo '<thead>';
  echo '<tr class="title">';
  echo '<th>'._('Users group').'</th>';
  echo '<th>'._('Applications group').'</th>';
  echo '</tr>';
  echo '</thead>';

  echo '<tbody>';
  if (! $has_publish) {
    $content = 'content'.(($count++%2==0)?1:2);
    echo '<tr class="'.$content.'"><td colspan="3">'._('No publication').'</td></tr>';
  } else {
    foreach($publications as $publication){
      $content = 'content'.(($count++%2==0)?1:2);
      $group_u = $publication['user'];
      $group_a = $publication['app'];

      echo '<tr class="'.$content.'">';
      echo '<td><a href="usersgroup.php?action=manage&amp;id='.$group_u->getUniqueID().'">'.$group_u->name.'</a></td>';
      echo '<td><a href="appsgroup.php?action=manage&amp;id='.$group_a->id.'">'.$group_a->name.'</a></td>';

			if ($can_manage_publications) {
				echo '<td><form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to delete this publication?').'\');"><div>';
				echo '<input type="hidden" name="action" value="del" />';
				echo '<input type="hidden" name="name" value="Publication" />';
				echo '<input type="hidden" name="group_a" value="'.$group_a->id.'" />';
				echo '<input type="hidden" name="group_u" value="'.$group_u->getUniqueID().'" />';
				echo '<input type="submit" value="'._('Delete').'"/>';
				echo '</div></form></td>';
			}
      echo '</tr>';
    }
  }
  echo '</tbody>';

  $nb_groups_apps  = count($groups_apps);
  $nb_groups_users = count($groups_users);

  if ($can_add_publish and $can_manage_publications) {
    $content = 'content'.(($count++%2==0)?1:2);

    echo '<tfoot>';
    echo '<tr class="'.$content.'">';
    echo '<td>';
    echo '<select id="select_group_u" name="group_u" onchange="ovdsm_publication_hook_select(this)" style="width: 100%;">';
    echo '<option value="">*</option>';
    foreach($groups_users as $group_users)
      if (count($group_users->appsGroups()) < $nb_groups_apps)
        echo '<option value="'.$group_users->getUniqueID().'" >'.$group_users->name.'</option>';
    echo '</select>';
    echo '</td>';

    echo '<td>';
    echo '<select id="select_group_a" name="group_a" onchange="ovdsm_publication_hook_select(this)" style="width: 100%;">';
    echo '<option value="" >*</option>';
    foreach($groups_apps as $group_apps)
      if (count($group_apps->userGroups()) < $nb_groups_users)
        echo '<option value="'.$group_apps->id.'" >'.$group_apps->name.'</option>';
    echo '</select>';
    echo '</td><td>';
    echo '<form action="actions.php" method="post" ><div>';
    echo '<input type="hidden" name="action" value="add" />';
    echo '<input type="hidden" name="name" value="Publication" />';
    echo '<input type="hidden" name="group_u" value="" id="input_group_u" />';
    echo '<input type="hidden" name="group_a" value="" id="input_group_a" />';
    echo '<input type="button" value="'._('Add').'" onclick="if($(\'input_group_u\').value == \'\') {alert(\''.addslashes(_('Please select an users group')).'\'); return;} if($(\'input_group_a\').value == \'\') {alert(\''.addslashes(_('Please select an applications group')).'\'); return;} this.form.submit();" />';
    echo '</div></form>';
    echo '</td>';
    echo '</tr>';
    echo '</tfoot>';
  }

  echo '</table>';
  echo '<br /><br /><br />';
  echo '</div>';

  echo '</div>';
  page_footer();
}
