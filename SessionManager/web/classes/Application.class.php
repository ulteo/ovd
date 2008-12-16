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

require_once(dirname(__FILE__).'/../includes/core.inc.php');

class Application {
	private $attributes;

	public function __construct($id_=NULL, $name_=NULL, $description_=NULL, $type_=NULL, $executable_path_=NULL, $package_=NULL, $icon_path_=NULL, $published_=true,$desktopfile_=NULL) {
		$this->attributes = array();
		$this->attributes['id'] = $id_;
		$this->attributes['name'] = $name_;
		$this->attributes['description'] = $description_;
		$this->attributes['type'] = $type_;
		$this->attributes['executable_path'] = $executable_path_;
		$this->attributes['icon_path'] = $icon_path_;
		$this->attributes['package'] = $package_;
		$this->attributes['published'] = (bool)($published_);
		$this->attributes['desktopfile'] = $desktopfile_;

	}

	public function setAttribute($myAttribute_,$value_){
		$this->attributes[$myAttribute_] = $value_;
	}

	public function hasAttribute($myAttribute_){
		return isset($this->attributes[$myAttribute_]);
	}

	public function getAttribute($myAttribute_){
		if (isset($this->attributes[$myAttribute_]))
			return $this->attributes[$myAttribute_];
		else
			return NULL;
	}

	public function haveIcon() {
		if (file_exists(CACHE_DIR.'/image/application/'.$this->getAttribute('id').'.png'))
			return true;

		if (!check_folder(CACHE_DIR.'/image') || !check_folder(CACHE_DIR.'/image/application'))
			return false;

		$l = new ApplicationServerLiaison($this->getAttribute('id'), NULL);
		$servers_id = $l->groups();
		$servers = array();
		foreach ($servers_id as $server_id) {
			$buf = Server::load($server_id);

			if ($buf != false && $buf->isOnline())
				$servers[] = $buf;
		}

		if (!is_array($servers) || count($servers) == 0)
			return false;

		$random_server = $servers[array_rand($servers)];

		$buf = query_url('http://'.$random_server->fqdn.'/webservices/icon.php?path='.$this->getAttribute('icon_path'));

		if (!$buf)
			return false;

		@file_put_contents(CACHE_DIR.'/image/application/'.$this->getAttribute('id').'.png', $buf);

		return true;
	}

	public function groups(){
		Logger::debug('main','APPLICATION::groups');
		// TODO use liaison class
		$result = array();
		$l = new AppsGroupLiaison($this->attributes['id'],NULL);
		$rows = $l->groups();
		foreach ($rows as $row){
			$g = new AppsGroup();
			$g->fromDB($row['group']);
			$result []= $g;
		}
		return $result;
	}

	public function getAttributesList() {
		return array_keys($this->attributes);
	}

	protected function delete(){
		Logger::debug('main','APPLICATION::delete');
		unset($this->attributes);
		$this->attributes = array();
	}
}
