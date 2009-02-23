<?php
/**
 * Copyright (C) 2008-2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com>
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
require_once(dirname(__FILE__).'/../includes/core.inc.php');

class Server {
	public $fqdn = NULL;

	public $status = NULL;
	public $registered = NULL;
	public $locked = NULL;
	public $type = NULL;
	public $version = NULL;
	public $external_name = NULL;
	public $web_port = NULL;
	public $max_sessions = NULL;
	public $cpu_model = NULL;
	public $cpu_nb_cores = NULL;
	public $cpu_load = NULL;
	public $ram_total = NULL;
	public $ram_used = NULL;

	public function __construct($fqdn_) {
// 		Logger::debug('main', 'Starting Server::__construct for \''.$fqdn_.'\'');

		$this->fqdn = $fqdn_;
	}

	public function __toString() {
		return 'Server(\''.$this->fqdn.'\', \''.$this->status.'\', \''.$this->registered.'\', \''.$this->locked.'\', \''.$this->type.'\', \''.$this->version.'\', \''.$this->external_name.'\', \''.$this->web_port.'\', \''.$this->max_sessions.'\', \''.$this->cpu_model.'\', \''.$this->cpu_nb_cores.'\', \''.$this->cpu_load.'\', \''.$this->ram_total.'\', \''.$this->ram_used.'\')';
	}

	public function hasAttribute($attrib_) {
// 		Logger::debug('main', 'Starting Server::hasAttribute for \''.$this->fqdn.'\' attribute '.$attrib_);

		if (! isset($this->$attrib_) || is_null($this->$attrib_))
			return false;

		return true;
	}

	public function getAttribute($attrib_) {
// 		Logger::debug('main', 'Starting Server::getAttribute for \''.$this->fqdn.'\' attribute '.$attrib_);

		if (! $this->hasAttribute($attrib_))
			return false;

		return $this->$attrib_;
	}

	public function setAttribute($attrib_, $value_) {
// 		Logger::debug('main', 'Starting Server::setAttribute for \''.$this->fqdn.'\' attribute '.$attrib_.' value '.$value_);

		$this->$attrib_ = $value_;

		return true;
	}

	public function uptodateAttribute($attrib_) {
// 		Logger::debug('main', 'Starting Server::uptodateAttribute for \''.$this->fqdn.'\' attribute '.$attrib_);

		$buf = Abstract_Server::uptodate($this);

		return $buf;
	}

	public function isOK() {
		Logger::debug('main', 'Starting Server::isOK for \''.$this->fqdn.'\'');

		$buf_type = $this->getType();
		if (! $buf_type) {
			Logger::error('main', '"'.$this->fqdn.'": does not send a valid type');
			popup_error('"'.$this->fqdn.'": '._('does not send a valid type'));

			return false;
		}
		$buf_version = $this->getVersion();
		if (! $buf_version) {
			Logger::error('main', '"'.$this->fqdn.'": does not send a valid version');
			popup_error('"'.$this->fqdn.'": '._('does not send a valid version'));

			return false;
		}
		$buf_monitoring = $this->getMonitoring();
		if (! $buf_monitoring) {
			Logger::error('main', '"'.$this->fqdn.'": does not send a valid monitoring');
			popup_error('"'.$this->fqdn.'": '._('does not send a valid monitoring'));

			return false;
		}

		return true;
	}

	public function isAuthorized() {
		Logger::debug('main', 'Starting Server::isAuthorized for \''.$this->fqdn.'\'');

		if (! $this->isOnline()) {
			Logger::critical('main', '"'.$this->fqdn.'": does not accept requests from me!');
			popup_error('"'.$this->fqdn.'": '._('does not accept requests from me!'));

			return false;
		}

		$prefs = Preferences::getInstance();
		if (! $prefs) {
			Logger::critical('get Preferences failed in '.__FILE__.' line '.__LINE__);
			return false;
		}

		$buf = $prefs->get('general', 'application_server_settings');
		$authorized_fqdn = $buf['authorized_fqdn'];
		$fqdn_private_address = $buf['fqdn_private_address'];
		$disable_fqdn_check = $buf['disable_fqdn_check'];

		$address = $_SERVER['REMOTE_ADDR'];
		$name = $this->fqdn;

		$buf = false;
		foreach ($authorized_fqdn as $fqdn) {
			$fqdn = str_replace('*', '.*', str_replace('.', '\.', $fqdn));

			if (preg_match('/'.$fqdn.'/', $name))
				$buf = true;
		}

		if (! $buf) {
			Logger::warning('main', '"'.$this->fqdn.'": server is NOT authorized! ('.$buf.')');
			return false;
		}

		if (preg_match('/[0-9]{1,3}(\.[0-9]{1,3}){3}/', $name)) // if IP?
			return ($name == $address);

		if ($disable_fqdn_check == 1)
			return true;

		$reverse = @gethostbyaddr($address);
		if (($reverse == $name) || (isset($fqdn_private_address[$name]) && $fqdn_private_address[$name] == $address))
			return true;

		Logger::warning('main', '"'.$this->fqdn.'": reverse DNS is invalid! ('.$buf.')');

		return false;
	}

	public function register() {
		Logger::debug('main', 'Starting Server::register for \''.$this->fqdn.'\'');

		if (! $this->isOnline())
			return false;

		if (! $this->isOK())
			return false;

		$this->setAttribute('locked', true);
		$this->setAttribute('registered', true);

		$this->updateApplications();

		return true;
	}

	public function isOnline() {
		Logger::debug('main', 'Starting Server::isOnline for \''.$this->fqdn.'\'');

		$ret = false;

		$ev = new ServerStatusChanged(array(
			'fqdn'	=>	$this->fqdn
		));

		if (! $this->hasAttribute('status') || ! $this->uptodateAttribute('status'))
			$this->getStatus();

		if ($this->hasAttribute('status') && $this->getAttribute('status') == 'ready')
			$ret = true;

		$ev->setAttribute('status', ($ret)?ServerStatusChanged::$ONLINE:ServerStatusChanged::$OFFLINE);

		$ev->emit();

		if ($ret === false) {
			popup_error('"'.$this->fqdn.'": '._('is NOT online!'));
			Logger::error('main', '"'.$this->fqdn.'": is NOT online!');
		}

		return $ret;
	}

	public function isUnreachable() {
		Logger::debug('main', 'Starting Server::isUnreachable for \''.$this->fqdn.'\'');

		$ev = new ServerStatusChanged(array(
			'server'	=>	$this->fqdn,
			'status'	=>	ServerStatusChanged::$UNREACHABLE
		));

		Logger::critical('main', 'Server '.$this->fqdn.':'.$this->web_port.' is unreachable, status switched to "broken"');
		$this->setStatus('broken');

		$ev->emit();
		return true;
	}

	public function returnedError() {
		Logger::debug('main', 'Starting Server::returnedError for \''.$this->fqdn.'\'');

		Logger::error('main', 'Server '.$this->fqdn.':'.$this->web_port.' returned an ERROR, status switched to "broken"');
		$this->setStatus('broken');

		return true;
	}

	public function getStatus() {
		Logger::debug('main', 'Starting Server::getStatus for \''.$this->fqdn.'\'');

		if ($this->getAttribute('registered') === true && ($this->getAttribute('status') == 'offline' || $this->getAttribute('status') == 'broken'))
			return false;

		$ret = query_url('http://'.$this->fqdn.':'.$this->web_port.'/webservices/server_status.php');

		if (! $ret) {
			$this->isUnreachable();
			return false;
		}

		$this->setStatus($ret);

		if ($ret !== 'ready') {
			$prefs = Preferences::getInstance();
			if (! $prefs) {
				Logger::critical('get Preferences failed in '.__FILE__.' line '.__LINE__);
				return false;
			}

			$buf = $prefs->get('general', 'application_server_settings');
			if ($buf['action_when_as_not_ready'] == 1)
				$this->setAttribute('locked', true);
		}

		return true;
	}

	public function setStatus($status_) {
		Logger::debug('main', 'Starting Server::setStatus for \''.$this->fqdn.'\'');

		switch ($status_) {
			case 'ready':
				if ($this->getAttribute('status') != 'ready') {
					Logger::info('main', 'Status set to "ready" for \''.$this->fqdn.'\'');
					$this->setAttribute('status', 'ready');
				}
				break;
			case 'down':
				if ($this->getAttribute('status') != 'down') {
					Logger::warning('main', 'Status set to "down" for \''.$this->fqdn.'\'');
					$this->setAttribute('status', 'down');
				}
				break;
			case 'broken':
			default:
				if ($this->getAttribute('status') != 'broken') {
					Logger::error('main', 'Status set to "broken" for \''.$this->fqdn.'\'');
					$this->setAttribute('status', 'broken');
				}
				break;
		}

		Abstract_Server::save($this);

		return true;
	}

	public function stringStatus() {
// 		Logger::debug('main', 'Starting Server::stringStatus for \''.$this->fqdn.'\'');

		$string = '';

		if ($this->getAttribute('locked'))
			$string .= '<span class="msg_unknown">'._('Under maintenance').'</span> ';

		$buf = $this->getAttribute('status');
		if ($buf == 'ready')
			$string .= '<span class="msg_ok">'._('Online').'</span>';
		elseif ($buf == 'down')
			$string .= '<span class="msg_warn">'._('Offline').'</span>';
		elseif ($buf == 'broken')
			$string .= '<span class="msg_error">'._('Broken').'</span>';
		else
			$string .= '<span class="msg_other">'._('Unknown').'</span>';

		return $string;
	}

	public function getType() {
		Logger::debug('main', 'Starting Server::getType for \''.$this->fqdn.'\'');

		if (! $this->isOnline())
			return false;

		$buf = query_url('http://'.$this->fqdn.':'.$this->web_port.'/webservices/server_type.php');

		if (! $buf) {
			$this->isUnreachable();
			return false;
		}

		if (! is_string($buf))
			return false;

		if ($buf == '')
			return false;

		$this->setAttribute('type', $buf);

// 		Abstract_Server::save($this);

		return true;
	}

	public function stringType() {
// 		Logger::debug('main', 'Starting Server::stringType for \''.$this->fqdn.'\'');

		if ($this->hasAttribute('type'))
			return $this->getAttribute('type');

		return _('Unknown');
	}

	public function getVersion() {
		Logger::debug('main', 'Starting Server::getVersion for \''.$this->fqdn.'\'');

		if (! $this->isOnline())
			return false;

		$buf = query_url('http://'.$this->fqdn.':'.$this->web_port.'/webservices/server_version.php');

		if (! $buf) {
			$this->isUnreachable();
			return false;
		}

		if (! is_string($buf))
			return false;

		if ($buf == '')
			return false;

		$this->setAttribute('version', $buf);

// 		Abstract_Server::save($this);

		return true;
	}

	public function stringVersion() {
// 		Logger::debug('main', 'Starting Server::stringVersion for \''.$this->fqdn.'\'');

		if ($this->hasAttribute('version'))
			return $this->getAttribute('version');

		return _('Unknown');
	}

	public function getNbMaxSessions() {
		Logger::debug('main', 'Starting Server::getNbMaxSessions for \''.$this->fqdn.'\'');

		return $this->getAttribute('max_sessions');
	}

	public function getNbUsedSessions() {
		Logger::debug('main', 'Starting Server::getNbUsedSessions for \''.$this->fqdn.'\'');

  		$buf = Sessions::getByServer($this->fqdn);

		return count($buf);
	}

	public function getNbAvailableSessions() {
		Logger::debug('main', 'Starting Server::getNbAvailableSessions for \''.$this->fqdn.'\'');

		$max_sessions = $this->getNbMaxSessions();
		$used_sessions = $this->getNbUsedSessions();

		return ($max_sessions-$used_sessions);
	}

	public function getMonitoring() {
		Logger::debug('main', 'Starting Server::getMonitoring for \''.$this->fqdn.'\'');

		if (! $this->isOnline())
			return false;

		$xml = query_url('http://'.$this->fqdn.':'.$this->web_port.'/webservices/server_monitoring.php');

		if (! $xml) {
			$this->isUnreachable();
			return false;
		}

		if (! is_string($xml))
			return false;

		if (substr($xml, 0, 5) == 'ERROR') {
			$this->returnedError();
			return false;
		}

		if ($xml == '')
			return false;

		$dom = new DomDocument();
		$ret = @$dom->loadXML($xml);
		if (! $ret)
			return false;

		$keys = array();

		$cpu_node = $dom->getElementsByTagname('cpu')->item(0);
		$keys['cpu_model'] = $cpu_node->firstChild->nodeValue;
		$keys['cpu_nb_cores'] = $cpu_node->getAttribute('nb_cores');
		$keys['cpu_load'] = $cpu_node->getAttribute('load');

		$ram_node = $dom->getElementsByTagname('ram')->item(0);
		$keys['ram_total'] = $ram_node->getAttribute('total');
		$keys['ram_used'] = $ram_node->getAttribute('used');

		foreach ($keys as $k => $v)
			$this->setAttribute($k, trim($v));

// 		Abstract_Server::save($this);

		return true;
	}

	public function getCpuUsage() {
		Logger::debug('main', 'Starting Server::getCpuUsage for \''.$this->fqdn.'\'');

		$cpu_load = $this->getAttribute('cpu_load');
		$cpu_nb_cores = $this->getAttribute('cpu_nb_cores');

		if ($cpu_nb_cores == 0)
			return false;

		return round(($cpu_load/$cpu_nb_cores)*100);
	}

	public function getRamUsage() {
		Logger::debug('main', 'Starting Server::getRamUsage for \''.$this->fqdn.'\'');

		$ram_used = $this->getAttribute('ram_used');
		$ram_total = $this->getAttribute('ram_total');

		if ($ram_total == 0)
			return false;

		return round(($ram_used/$ram_total)*100);
	}

	public function getSessionUsage() {
		Logger::debug('main', 'Starting Server::getSessionUsage for \''.$this->fqdn.'\'');

		$max_sessions = $this->getNbMaxSessions();
		$used_sessions = $this->getNbUsedSessions();

		if ($max_sessions == 0)
			return false;

		return round(($used_sessions/$max_sessions)*100);
	}

	public function orderDeletion() {
		Logger::debug('main', 'Starting Server::orderDeletion for \''.$this->fqdn.'\'');

		return true;
	}

	// ? unclean?
	public function getApplications() {
		Logger::debug('main', 'Starting SERVER::getApplications for server '.$this->fqdn);

		$prefs = Preferences::getInstance();
		if (! $prefs) {
			Logger::critical('get Preferences failed in '.__FILE__.' line '.__LINE__);
			return NULL;
		}

		$mods_enable = $prefs->get('general', 'module_enable');
		if (!in_array('ApplicationDB', $mods_enable)) {
			Logger::error('Module ApplicationDB must be enabled');
			return NULL;
		}

		$mod_app_name = 'ApplicationDB_'.$prefs->get('ApplicationDB', 'enable');
		$applicationDB = new $mod_app_name();

		$ls = Abstract_Liaison::load('ApplicationServer', NULL, $this->fqdn);
		if (is_array($ls)) {
			$res = array();
			foreach ($ls as $l) {
				$a = $applicationDB->import($l->element);
				if (is_object($a) && $applicationDB->isOK($a))
					$res []= $a;
			}
			return $res;
		} else {
			Logger::error('main', 'SERVER::getApplications elements is not array');
			return NULL;
		}
	}

	public function applications() {
		$prefs = Preferences::getInstance();
		if (! $prefs)
			die_error('get Preferences failed',__FILE__,__LINE__);
		$mods_enable = $prefs->get('general','module_enable');
		if (!in_array('ApplicationDB',$mods_enable)){
			die_error(_('Module ApplicationDB must be enabled'),__FILE__,__LINE__);
		}
		$mod_app_name = 'admin_ApplicationDB_'.$prefs->get('ApplicationDB','enable');
		$applicationDB = new $mod_app_name();

		$apps = array();
		$l = Abstract_Liaison::load('ApplicationServer', NULL, $this->fqdn);
		$elements = $l->elements();
		foreach ($elements as $app_id) {
			$app = $applicationDB->import($app_id);
			if (is_object($app)) {
				$apps []= $app;
			}
		}
		return $apps;
	}

	public function appsGroups() {
		$apps_roups_id = array();
		$asl = Abstract_Liaison::load('ApplicationServer', NULL, $this->fqdn);
// 		$applications_on_server = $asl->elements();
		foreach ($applications_on_server as $app_id) {
			$ag = Abstract_Liaison::load('AppsGroup', $app_id, NULL);
			$apps_roups_id = array_merge($apps_roups_id, $ag);
		}
		$apps_roups_id = array_unique($apps_roups_id);
		$apps_roups = array();
		foreach ($apps_roups_id as $id) {
			$ag = new AppsGroup();
			$ag->fromDB($id);
			if ($ag->isOK())
				$apps_roups []= $ag;
		}
		return $apps_roups;
	}

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

		$xml = query_url('http://'.$this->fqdn.':'.$this->web_port.'/webservices/applications.php');

		if (! $xml) {
			$this->isUnreachable();
			return false;
		}

		if (! is_string($xml))
			return false;

		if (substr($xml, 0, 5) == 'ERROR') {
			$this->returnedError();
			return false;
		}

		if ($xml == '')
			return false;

		$dom = new DomDocument();
		@$dom->loadXML($xml);
		$root = $dom->documentElement;

		// before adding application, we remove all previous applications
		$previous_liaison = Abstract_Liaison::load('ApplicationServer', NULL, $this->fqdn); // see and of function

		$application_node = $dom->getElementsByTagName("application");
		foreach($application_node as $app_node){
			$app_name = '';
			$app_description = '';
			$app_path_exe = '';
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
					$ret = Abstract_Liaison::save('ApplicationServer', $a->getAttribute('id'),$this->fqdn);
					if ($ret === false) {
						Logger::error('main', 'Server::updateApplications failed to save application');
						return $ret;
					}
				}
				else{
					//echo "Application not ok<br>\n";
				}
			}
		}

// 		Abstract_Server::save($this);

		$current_liaison = Abstract_Liaison::load('ApplicationServer', NULL, $this->fqdn);
		$current_liaison_key = array_keys($current_liaison);
		$previous_liaison_key = array_keys($previous_liaison);
		foreach ($previous_liaison_key as $key){
			if (in_array($key, $current_liaison_key) == false) {
				$liaison = $previous_liaison_key[$key];
				Abstract_Liaison::delete('ApplicationServer', $liaison->element, $liaison->group);
			}
		}
		return true;
	}
}
