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

class Server_admin extends Server {
	public function updateApplications(){
		Logger::debug('admin','SERVERADMIN::updateApplications');
		$prefs = Preferences::getInstance();
		if (! $prefs)
			return false;

		if (!$this->isOnline())
			return false;

		$mods_enable = $prefs->get('general','module_enable');
		if (!in_array('ApplicationDB',$mods_enable)){
			die_error(_('Module ApplicationDB must be enabled'),__FILE__,__LINE__);
		}
		$mod_app_name = 'admin_ApplicationDB_'.$prefs->get('ApplicationDB','enable');
		$applicationDB = new $mod_app_name();

		$xml = query_url('http://'.$this->fqdn.'/webservices/applications.php');

		if ($xml == false) {
			Logger::error('main', 'Server '.$this->fqdn.' is unreachable, status switched to "broken"');
			$this->setAttribute('status', 'broken');
			return false;
		}

		if (substr($xml, 0, 5) == 'ERROR') {
			Logger::error('main', 'Webservice returned an ERROR, status switched to "broken"');
			$this->setAttribute('status', 'broken');
			return false;
		}

		$dom = new DomDocument();
		@$dom->loadXML($xml);
		$root = $dom->documentElement;

		// before adding application, we remove all previous applications
		// TODO BETTER (must use liaison class)
		$sql2 = MySQL::getInstance();
		$res = $sql2->DoQuery('DELETE FROM @1 WHERE @2 = %3', LIAISON_APPLICATION_SERVER_TABLE, 'group', $this->fqdn);

		$application_node = $dom->getElementsByTagName("application");
		foreach($application_node as $app_node){
			$app_name = NULL;
			$app_description = NULL;
			$app_path_exe = NULL;
			$app_path_args = NULL;
			$app_path_icon = NULL;
			$app_package = NULL;
			$app_desktopfile = NULL;
			if ($app_node->hasAttribute("name"))
				$app_name = $app_node->getAttribute("name");
			if ($app_node->hasAttribute("description"))
				$app_description = $app_node->getAttribute("description");
			if ($app_node->hasAttribute("package"))
				$app_package = $app_node->getAttribute("package");
			if ($app_node->hasAttribute("desktopfile"))
				$app_desktopfile = $app_node->getAttribute("desktopfile");

			$exe_node = $app_node->getElementsByTagName('executable')->item(0);
			if ($exe_node->hasAttribute("command")) {
				$command = $exe_node->getAttribute("command");
				$command = str_replace(array("%U","%u","%c","%i","%f","%m",'"'),"",$command);
				$app_path_exe = trim($command);
			}
			if ($exe_node->hasAttribute("icon"))
				$app_path_icon =  ($exe_node->getAttribute("icon"));
			$a = new Application(NULL,$app_name,$app_description,$this->getAttribute('type'),$app_path_exe,$app_package,$app_path_icon,true,$app_desktopfile);
			$a_search = $applicationDB->search($app_name,$app_description,$this->getAttribute('type'),$app_path_exe);
			if (is_object($a_search)){
				//already in DB
				// echo $app_name." already in DB\n";
				$a = $a_search;
			}
			else {
				// echo $app_name." NOT in DB\n";
				if ($applicationDB->isWriteable() == false){
					Logger::debug('admin','applicationDB is not writeable');
				}
				else{
					if ($applicationDB->add($a) == false){
						//echo 'app '.$app_name." not insert<br>\n";
						return false;
					}
				}
			}
			if ($applicationDB->isWriteable() == true){
				if ($applicationDB->isOK($a) == true){
					// we add the app to the server
					$l = new ApplicationServerLiaison($a->getAttribute('id'),$this->fqdn);
					if ($l->onDB() == false){
						if ($l->insertDB()){
							// insert ok
							//echo "insert liaison OK (app )".$a->getAttribute('id')." fqdn ".$this->fqdn."<br>";
						}
						else {
							//echo "insert liaison fail\n";
							return false;
						}
					}
					else {
						//echo "ApplicationServerLiaison already on DB<br>\n";
					}
				}
				else{
					//echo "Application not ok<br>\n";
				}
			}
		}
		return true;
	}
}
