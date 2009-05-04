<?php
/**
 * Copyright (C) 2009 Ulteo SAS
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

class Application_weblink extends Application{
	protected $default_browser;

	public function __construct($id_, $name_, $description_, $url_) {
		Logger::debug('main', "Application_weblink::construct('$id_','$name_','$description_','$url_')");
		parent::__construct($id_, $name_, $description_, 'weblink', $url_, NULL, NULL, true, $name_.'.weblink');
		// executable_path <=> url;
		
		$prefs = Preferences::getInstance();
		if (! $prefs)
			die_error('get Preferences failed',__FILE__,__LINE__);
		
		$mods_enable = $prefs->get('general','module_enable');
		if (!in_array('ApplicationDB',$mods_enable)){
			die_error(_('Module ApplicationDB must be enabled'),__FILE__,__LINE__);
		}
		
		
		$default_browser = $prefs->get('general','default_browser');
		if (!is_array($default_browser)) {
			Logger::error('main', 'Application_weblink::construct failed to get default_browser preferences');
		}
		$this->default_browser = $default_browser;
	}
	
	public function haveIcon() {
		if (file_exists(CACHE_DIR.'/image/application/'.$this->getAttribute('id').'.png'))
			return true;

		if (!check_folder(CACHE_DIR.'/image') || !check_folder(CACHE_DIR.'/image/application'))
			return false;

		@copy(SESSIONMANAGER_ROOT_ADMIN.'/media/image/server-weblink.png',  CACHE_DIR.'/image/application/'.$this->getAttribute('id').'.png');

		return true;
	}
	
	public function toXML($ApS=NULL) {
		$list_attr = $this->getAttributesList();
		foreach ($list_attr as $k => $v) {
			if (in_array($v, array('executable_path', 'icon_path')))
				unset($list_attr[$k]);
		}
		
		if (is_object($ApS)) {
			$this->attributes['type'] = $ApS->type;
			if (array_key_exists($ApS->type ,$this->default_browser)) {
				$browser_id = $this->default_browser[$ApS->type];
				$prefs = Preferences::getInstance();
				if (! $prefs)
					die_error('get Preferences failed',__FILE__,__LINE__);
				
				$mods_enable = $prefs->get('general','module_enable');
				if (!in_array('ApplicationDB',$mods_enable)){
					die_error(_('Module ApplicationDB must be enabled'),__FILE__,__LINE__);
				}
				$mod_app_name = 'ApplicationDB_'.$prefs->get('ApplicationDB','enable');
				$applicationDB = new $mod_app_name();
				$browser = $applicationDB->import($browser_id);
				if (!is_object($browser)) {
					Logger::error('main', 'Application_weblink::toXML failed to load browser (id='.$browser_id.')');
				}
				else {
					$this->attributes['icon_path'] = $browser->getAttribute('icon_path');
				}
			}
		}
		else {// ugly 
			echo "is NOT object(ApS)\n";
			$this->attributes['type'] = 'linux';
			$this->attributes['icon_path'] = 'firefox';
		}
		
		$dom = new DomDocument();
		$application_node = $dom->createElement('application');
		$executable_node = $dom->createElement('executable');

		if ( $this->hasAttribute('executable_path'))
			$executable_node->setAttribute('command', 'firefox '.$this->attributes['executable_path']); // executable_path <=> url
		if ( $this->hasAttribute('icon_path'))
			$executable_node->setAttribute('icon', $this->attributes['icon_path']);

		foreach ($list_attr as $attr_name) {
			$application_node->setAttribute($attr_name, $this->attributes[$attr_name]);
		}
		$application_node->appendChild($executable_node);
		$dom->appendChild($application_node);
		return $dom->saveXML();
	}
}
