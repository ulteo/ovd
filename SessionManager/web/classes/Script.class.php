<?php
/**
 * Copyright (C) 2013 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com>
 * Author Vincent ROULLIER <vincent.roullier@ulteo.com>
 * Author David LECHEVALIER <david@ulteo.com> 2013
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

class Script {
	public $id = NULL;
	public $name = NULL;
	public $type = NULL;
	public $os = NULL;
	public $data = NULL;

	public function __construct($id_) {
		$this->id = $id_;
	}
	
	public function __toString() {
		return 'Script(\''.$this->id.'\', \''.$this->name.'\', \''.$this->os.'\', \''.$this->type.'\', \''.$this->data.'\')';
	}

	public function hasAttribute($attrib_) {
		Logger::debug('main', 'Starting Script::hasAttribute for \''.$this->id.'\' attribute '.$attrib_);

		if (! isset($this->$attrib_) || is_null($this->$attrib_))
			return false;

		return true;
	}
	
	public function getAttribute($attrib_) {
		Logger::debug('main', 'Starting Script::getAttribute for \''.$this->id.'\' attribute '.$attrib_);

		if (! $this->hasAttribute($attrib_))
			return false;

		return $this->$attrib_;
	}
	
	public function setAttribute($attrib_, $value_) {
		Logger::debug('main', 'Starting Script::setAttribute for \''.$this->id.'\' attribute '.$attrib_.' value '.$value_);

		$this->$attrib_ = $value_;

		return true;
	}
	
	public function usersGroups() {
		Logger::debug('main', 'Script::usersGroups');
		$userGroupDB = UserGroupDB::getInstance();
		$result = array();
		// add the default user group is enable
		$prefs = Preferences::getInstance();
		if (!$prefs) {
			Logger::critical('main', 'Script::usersGroups get prefs failed');
			die_error('get Preferences failed',__FILE__,__LINE__);
		}
		$liaison = Abstract_Liaison::load('Scripts', $this->getAttribute('name'), NULL);
		if (is_null($liaison)) {
			Logger::error('main', 'Script::usersGroups load('.$this->getAttribute('name').') is null');
			return $result;
		}
		
		foreach ($liaison as $row){
			$g = $userGroupDB->import($row->group);
			if (is_object($g))
				$result[]= $g;
		}
		return $result;
	}
}
