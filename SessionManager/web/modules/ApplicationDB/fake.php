<?php
/**
 * Copyright (C) 2008 Ulteo SAS
 * http://www.ulteo.com
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
require_once(dirname(__FILE__).'/../../includes/core.inc.php');/**/

class ApplicationDB_fake {
	public function __construct(){
		Logger::debug('main','ApplicationDB_fake::contructor');
		$this->applications = array(
			301 => new Application(301,'OpenOffice.org Base','Base', 'linux', '/usr/bin/soffice -base', 'openoffice.org-base', NULL,true,'/usr/share/applications/ooo-base.desktop'),
			302 => new Application(302,'OpenOffice.org Calc','Calc', 'linux', '/usr/bin/soffice -calc', 'openoffice.org-calc', NULL,true,'/usr/share/applications/ooo-calc.desktop'),
			303 => new Application(303,'OpenOffice.org Draw','Draw', 'linux', '/usr/bin/soffice -draw', 'openoffice.org-draw', NULL,true,'/usr/share/applications/ooo-draw.desktop'),
			304 => new Application(304,'OpenOffice.org Impress','Impress', 'linux', '/usr/bin/soffice -impress', 'openoffice.org-impress', NULL,true,'/usr/share/applications/ooo-impress.desktop'),
			305 => new Application(305,'OpenOffice.org Writer','Writer', 'linux', '/usr/bin/soffice -writer', 'openoffice.org-writer', NULL,true,'/usr/share/applications/ooo-writer.desktop'),
			306 => new Application(306,'Full desktop', 'Full desktop', 'linux', '', '', NULL,true)
		);
	}

	public function import($id_){
		Logger::debug('main','ApplicationDB::fake::import('.$id_.')');
		if (isset($this->applications[$id_]))
			return $this->applications[$id_];
		else
			return NULL;
	}

	// todo ugly
	public function search($app_name,$app_description,$app_type,$app_path_exe){
	Logger::debug('main',"ApplicationDB_fake::search ('".$app_name."','".$app_description."','".$app_type."','".$app_path_exe."')");
		foreach ($this->applications as $a){
			if ( ($a->getAttribute('name') == $app_name) && ($a->getAttribute('description') == $app_description) && ($a->getAttribute('executable_path') == $app_path_exe) && ($a->getAttribute('type') == $app_type))
				return $a;
		}
		return NULL;
	}

	public function isWriteable(){
		return false;
	}

	public function isOK($app_){
		$minimun_attribute = array('id','name','type','executable_path','published');
		if (is_object($app_)){
			foreach ($minimun_attribute as $attribute){
				if ($app_->hasAttribute($attribute) == false)
					return false;
			}
			return true;
		}
		else
			return false;
	}

	public function getList(){
		$apps =  $this->applications;
		if ($sort_) {
			usort($apps, "application_cmp");
		}
		return $apps;
		
	}

	public function configuration(){
		return array();
	}
	
	public function prefsIsValid($prefs_) {
		return true;
	}
	
	public static function prettyName() {
		return _('fake');
	}
	
	public static function isDefault() {
		return false;
	}
}
