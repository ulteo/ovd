<?php
/**
 * Copyright (C) 2008-2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2008-2009
 * Author Samuel BOVEE <samuel@ulteo.com> 2010
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

class AppsGroup {
	public $id;
	public $name; // (ex: Officeapps)
	public $description; // (ex: Office application)
	public $published; //(yes/no) (the group is available to user)
	
	public function __construct($id_=NULL, $name_=NULL, $description_=NULL, $published_=false) {
		Logger::debug('main', "APPSGROUP::contructor ($id_,$name_,$description_,$published_)");
		$this->id = $id_;
		$this->name = $name_;
		$this->description = $description_;
		$this->published = (bool)$published_;
	}
	
	public function __toString() {
		return get_class($this).'(id: \''.$this->id.'\' name: \''.$this->name.'\' description: \''.$this->description.'\' published: '.$this->published.')';
	}

	public function userGroups() {
		Logger::debug('main', 'APPSGROUPS::userGroups (for id='.$this->id.')');
		$UserGroupDB = UserGroupDB::getInstance();
		$groups = Abstract_Liaison::load('UsersGroupApplicationsGroup', NULL, $this->id);
		if (is_array($groups)) {
			$result = array();
			foreach ($groups as $UGAG_liaison){
				$g = $UserGroupDB->import($UGAG_liaison->element);
				if (is_object($g))
					$result[$UGAG_liaison->element]= $g;
			}
			return $result;
		}
		else {
			Logger::error('main', 'APPSGROUPS::userGroups (for id='.$this->id.') load liaison liaison failed');
			return NULL;
		}
	}
}
