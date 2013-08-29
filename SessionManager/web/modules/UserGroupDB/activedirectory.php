<?php
/**
 * Copyright (C) 2009-2013 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2009-2011
 * Author Julien LANGLOIS <julien@ulteo.com> 2013
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
class UserGroupDB_activedirectory extends UserGroupDB_ldap {
	public function __construct() {
		parent::__construct();
		
		$prefs = Preferences::getInstance();
		if (! $prefs)
			die_error('get Preferences failed',__FILE__,__LINE__);
		
		$a_pref = $prefs->get('UserGroupDB', 'activedirectory');
		if (is_array($a_pref)) {
			foreach($a_pref as $k => $v) {
				$this->preferences[$k] = $v;
			}
		}
		
		// Generate parent (ldap) settings
		$this->preferences['filter'] = '(objectClass=group)';
		$this->preferences['match'] = array(
			'name' => 'name',
			'description' => 'description',
		);
		
		$this->preferences['group_match_user'] = array('user_member', 'group_membership');
		$this->preferences['user_member_field'] = 'member';
		$this->preferences['user_member_type'] = 'dn';
		$this->preferences['group_membership_field'] = 'memberOf';
		$this->preferences['group_membership_type'] = 'dn';
		$this->preferences['ou'] = '';
		if (array_key_exists('use_child_group', $this->preferences)) {
			if (in_array($this->preferences['use_child_group'], array(1, '1'))) {
				$this->preferences['user_member_field'].= ':1.2.840.113556.1.4.1941:';
				$this->preferences['group_membership_field'].= ':1.2.840.113556.1.4.1941:';
			}
		}
	}
	
	public static function configuration() {
		$ret = array();
		
		$c = new ConfigElement_select('use_child_group', 0);
		$c->setContentAvailable(array(0, 1));
		$ret []= $c;
		
		return $ret;
	}
	
	public function customize_field($field_) {
		$pref = $this->preferences;
		if (array_key_exists('use_child_group', $pref)) {
			if (in_array($pref['use_child_group'], array(1, '1'))) {
				$field_.= ':1.2.840.113556.1.4.1941:';
			}
		}
		
		return $field_;
	}
}
