<?php
/**
 * Copyright (C) 2008 Ulteo SAS
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
	public $folder = NULL;
	public $status = NULL;

	public function __construct($fqdn_, $create_=true) {
		Logger::debug('main', 'Starting SERVER::_construct for server '.$fqdn_);

		$this->fqdn = $fqdn_;
		$this->folder = SESSIONS_DIR.'/'.$this->fqdn;

		if (!file_exists($this->folder) && check_ip($fqdn_) && $create_)
			$this->create();

		if (!$this->hasAttribute('type') || $this->getAttribute('type') ===  '')
			$this->getType();

		if (!$this->hasAttribute('version') || $this->getAttribute('version') === '')
			$this->getVersion();
	}

	public static function load($fqdn_) {
		Logger::debug('main', 'Starting SERVER::load for server '.$fqdn_);

		if (!file_exists(SESSIONS_DIR.'/'.$fqdn_))
			return false;

		return new Server($fqdn_, false);
	}

	public function create($die_=true) {
		Logger::debug('main', 'Starting SERVER::create for server '.$this->fqdn);

		$prefs = Preferences::getInstance();
		if (! $prefs) {
			Logger::error('get Preferences failed');
			return false;
		}

		$buf = $prefs->get('general', 'application_server_settings');
		$disable_fqdn_check = $buf['disable_fqdn_check'];

		if ($this->fqdn !== @gethostbyaddr(@gethostbyname($this->fqdn))) {
			$_SESSION['errormsg'] = '"'.$this->fqdn.'" reverse DNS seems invalid !';

			Logger::error('main', '"'.$this->fqdn.'" reverse DNS seems invalid !');

			if ($disable_fqdn_check == '0') {
				if ($die_ == true)
					die('"'.$this->fqdn.'" reverse DNS seems invalid !');
				else
					return false;
			}
		}

		if (!$this->getStatus(0)) {
			$_SESSION['errormsg'] = '"'.$this->fqdn.'" does not accept requests from me !';

			Logger::error('main', '"'.$this->fqdn.'" does not accept requests from me !');
			if ($die_ == true)
				die('"'.$this->fqdn.'" does not accept requests from me !');
			else
				return false;
		}

// 		if (!$this->isOnline()) {
// 			$_SESSION['errormsg'] = '"'.$this->fqdn.'" is NOT "online"';
//
// 			Logger::error('main', 'Server NOT "online" : '.$this->folder);
// 			if ($die_ == true)
// 				die('Server NOT "online" : '.$this->folder);
// 			else
// 				return false;
// 		}
// 		Logger::info('main', 'Server "online" : '.$this->folder);

		if (file_exists($this->folder)) {
			Logger::error('main', 'Server already exists  : '.$this->folder);
			if ($die_ == true)
				die('Server already exists  : '.$this->folder);
			else
				return false;
		}
		Logger::info('main', 'Server folder does not exist : '.$this->folder);

		if (! @mkdir($this->folder, 0750)) {
			Logger::error('main', 'Server folder NOT created : '.$this->folder);
			if ($die_ == true)
				die('Server folder NOT created : '.$this->folder);
			else
				return false;
		}
		Logger::info('main', 'Server folder created : '.$this->folder);

		if (! @touch($this->folder.'/unregistered')) {
			Logger::error('main', 'Server "unregistered" file NOT created : '.$this->folder.'/unregistered');
			if ($die_ == true)
				die('Server "unregistered" file NOT created : '.$this->folder.'/unregistered');
			else
				return false;
		}
		Logger::info('main', 'Server "unregistered" file created : '.$this->folder.'/unregistered');

		return true;
	}

	public function register($die_=true) {
		Logger::debug('main', 'Starting SERVER::register for server '.$this->fqdn);

		if (!$this->hasAttribute('unregistered'))
			return false;

		$prefs = Preferences::getInstance();
		if (! $prefs) {
			Logger::error('get Preferences failed');
			return false;
		}

		$buf = $prefs->get('general', 'application_server_settings');
		$disable_fqdn_check = $buf['disable_fqdn_check'];

		if ($this->fqdn !== @gethostbyaddr(@gethostbyname($this->fqdn))) {
			$_SESSION['errormsg'] = '"'.$this->fqdn.'" reverse DNS seems invalid !';

			Logger::error('main', '"'.$this->fqdn.'" reverse DNS seems invalid !');

			if ($disable_fqdn_check == '0') {
				if ($die_ == true)
					die('"'.$this->fqdn.'" reverse DNS seems invalid !');
				else
					return false;
			}
		}

		if (!$this->getStatus(1)) {
			$_SESSION['errormsg'] = '"'.$this->fqdn.'" does not accept requests from me !';

			Logger::error('main', '"'.$this->fqdn.'" does not accept requests from me !');
			if ($die_ == true)
				die('"'.$this->fqdn.'" does not accept requests from me !');
			else
				return false;
		}

		if (!$this->isOnline()) {
			$_SESSION['errormsg'] = '"'.$this->fqdn.'" is NOT "online"';

			Logger::error('main', 'Server NOT "online" : '.$this->folder);
			if ($die_ == true)
				die('Server NOT "online" : '.$this->folder);
			else
				return false;
		}
		Logger::info('main', 'Server "online" : '.$this->folder);

		if (! @unlink($this->folder.'/unregistered')) {
			Logger::error('main', 'Server NOT registered : '.$this->folder);
			if ($die_ == true)
				die('Server NOT registered : '.$this->folder);
			else
				return false;
		}
		Logger::info('main', 'Server registered : '.$this->folder);

		$this->setAttribute('nb_sessions', 10);
		$this->setAttribute('locked', 1);

		$admin = new Server_admin($this->fqdn);
		$admin->updateApplications();

		$this->getStatus();
		$this->getType();
		$this->getVersion();

		return true;
	}

	public function delete($die_=true) {
		Logger::debug('main', 'Starting SERVER::delete for server '.$this->fqdn);

		if (!$this->hasAttribute('locked'))
			return false;

		if (!file_exists($this->folder)) {
			Logger::error('main', 'Server does not exist : '.$this->folder);
			if ($die_ == true)
				die('Server does not exist : '.$this->folder);
			else
				return false;
		}

		$buf = glob($this->folder.'/*', GLOB_ONLYDIR);
		if (count($buf) > 0) {
			Logger::error('main', 'Server have active sessions : '.$this->folder);
			if ($die_ == true)
				die('Server have active sessions : '.$this->folder);
			else
				return false;
		}

		$remove_files = glob($this->folder.'/*');
		foreach ($remove_files as $remove_file)
			@unlink($remove_file);
		unset($remove_files);

		if (! @rmdir($this->folder)) {
			Logger::error('main', 'Server folder NOT removed : '.$this->folder);
			if ($die_ == true)
				die('Server folder NOT removed : '.$this->folder);
			else
				return false;
		}
		Logger::info('main', 'Server folder removed : '.$this->folder);

		return true;
	}

	public function hasAttribute($attrib_) {
		Logger::debug('main', 'Starting SERVER::hasAttribute for \''.$this->fqdn.'\' attribute '.$attrib_);

		if (!is_readable($this->folder.'/'.$attrib_)) {
			Logger::error('main', 'Attribute '.$attrib_.' NOT readable for server '.$this->fqdn);
			return false;
		}

		if (!is_writable2($this->folder.'/'.$attrib_))
			Logger::warning('main', 'Attribute '.$attrib_.' NOT writable for server '.$this->fqdn);

		return true;
	}

	public function getAttribute($attrib_) {
		Logger::debug('main', 'Starting SERVER::getAttribute for \''.$this->fqdn.'\' attribute '.$attrib_);

		if (!$this->hasAttribute($attrib_)) {
			Logger::warning('main', 'Attribute '.$attrib_.' NOT readable for server '.$this->fqdn);
			return false;
		}

		return trim(@file_get_contents($this->folder.'/'.$attrib_));
	}

	public function setAttribute($attrib_, $data_) {
		Logger::debug('main', 'Starting SERVER::setAttribute for attribute '.$attrib_.' with data '.$data_);

		if (!is_writable($this->folder.'/'.$attrib_) && !is_writable($this->folder)) {
			Logger::error('main', 'Attribute '.$attrib_.' NOT writable for server '.$this->fqdn);
			return false;
		}

		if (is_null($data_)) {// && $this->hasAttribute($attrib_)) {
			if (! @unlink($this->folder.'/'.$attrib_)) {
				Logger::error('main', 'Attribute "'.$attrib_.'" NOT removed for server '.$this->fqdn);
				return false;
			}
			Logger::info('main', 'Attribute "'.$attrib_.'" removed for server '.$this->fqdn);
			return true;
		}

		if (! @file_put_contents($this->folder.'/'.$attrib_, trim($data_))) {
			Logger::error('main', 'Attribute "'.$attrib_.'" NOT set for server '.$this->fqdn);
			return false;
		}
		Logger::info('main', 'Attribute "'.$attrib_.'" set to '.$data_.' for server '.$this->fqdn);

		return true;
	}

	public function getNbAvailableSessions() {
		Logger::debug('main', 'Starting SERVER::getNbAvailableSessions for server '.$this->fqdn);

		return (int)$this->getAttribute('nb_sessions');
	}

	public function getNbUsedSessions() {
		Logger::debug('main', 'Starting SERVER::getNbUsedSessions for server '.$this->fqdn);

		$buf = glob($this->folder.'/*', GLOB_ONLYDIR);

		return (int)count($buf);
	}

	public function isOnline() {
		Logger::debug('main', 'Starting SERVER::isOnline for server '.$this->fqdn);

		$this->getStatus(0);

		if ($this->hasAttribute('status') && $this->getAttribute('status') == 'ready')
			return true;

		return false;
	}

	public function getStatus($write_=true) {
		Logger::debug('main', 'Starting SERVER::getStatus for server '.$this->fqdn);

		$ret = query_url('http://'.$this->fqdn.'/webservices/server_status.php');

		if ($ret == false) {
			Logger::error('main', 'Server '.$this->fqdn.' is unreachable, status switched to "broken"');
			if ($write_ == true)
				$this->setStatus('broken');
			return false;
		}

		if ($write_ == true)
			$this->setStatus($ret);

		if ($ret !== 'ready') {
			$prefs = Preferences::getInstance();
			if (! $prefs)
				die_error('get Preferences failed',__FILE__,__LINE__);

			$buf = $what_to_do = $prefs->get('general', 'application_server_settings');
			$what_to_do = $buf['action_when_as_not_ready'];

			if ($what_to_do == 1)
				$this->setAttribute('locked', 1);
		}

		return true;
	}

	public function setStatus($status_) {
		Logger::debug('main', 'Starting SERVER::setStatus for server '.$this->fqdn);

		switch ($status_) {
			case 'ready':
				Logger::info('main', 'Status set to "ready" for server '.$this->fqdn);
				$this->setAttribute('status', 'ready');
				return true;
				break;
			case 'down':
				Logger::info('main', 'Status set to "down" for server '.$this->fqdn);
				$this->setAttribute('status', 'down');
				return true;
				break;
			case 'broken':
				Logger::info('main', 'Status set to "broken" for server '.$this->fqdn);
				$this->setAttribute('status', 'broken');
				return true;
				break;
		}

		Logger::info('main', 'Status set to "broken" for server '.$this->fqdn);
		$this->setAttribute('status', 'broken');

		return false;
	}

	public function getMonitoring() {
		Logger::debug('main', 'Starting SERVER::getMonitoring for server '.$this->fqdn);

		if (!$this->isOnline())
			return false;

		$xml = query_url('http://'.$this->fqdn.'/webservices/server_monitoring.php');

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

		$keys = array();

		$cpu_node = $dom->getElementsByTagname('cpu')->item(0);
		$keys['cpu_model'] = $cpu_node->firstChild->nodeValue;
		$keys['cpu_nb'] = $cpu_node->getAttribute('nb_cores');
		$keys['cpu_load'] = $cpu_node->getAttribute('load');

		$ram_node = $dom->getElementsByTagname('ram')->item(0);
		$keys['ram'] = $ram_node->getAttribute('total');
		$keys['ram_used'] = $ram_node->getAttribute('used');

		foreach ($keys as $k => $v)
			$this->setAttribute($k, $v);

		return true;
	}

	public function getApplications() {
		Logger::debug('main', 'Starting SERVER::getApplications for server '.$this->fqdn);

		if (!$this->isOnline())
			return false;

		$prefs = Preferences::getInstance();
		if (! $prefs) {
			Logger::error('get Preferences failed');
			return NULL;
		}

		$mods_enable = $prefs->get('general', 'module_enable');
		if (!in_array('ApplicationDB', $mods_enable)) {
			Logger::error('Module ApplicationDB must be enabled');
			return NULL;
		}

		$mod_app_name = 'ApplicationDB_'.$prefs->get('ApplicationDB', 'enable');
		$applicationDB = new $mod_app_name();

		$sal = new ApplicationServerLiaison(NULL, $this->fqdn);
		$ls = $sal->elements();
		if (is_array($ls)) {
			$res = array();
			foreach ($ls as $l) {
				$a = $applicationDB->import($l);
				if (is_object($a) && $applicationDB->isOK($a))
					$res []= $a;
			}
			return $res;
		} else {
			Logger::error('main', 'SERVER::getApplications elements is not array');
			return NULL;
		}
	}

	public function stringType() {
		Logger::debug('main', 'Starting SERVER::stringStatus for server '.$this->fqdn);
		if ($this->hasAttribute('type'))
			return $this->getAttribute('type');
		else
			return _('Unknown');
	}

	public function stringVersion() {
		Logger::debug('main', 'Starting SERVER::stringStatus for server '.$this->fqdn);
	if ($this->hasAttribute('version'))
			return $this->getAttribute('version');
		else
			return _('Unknown');
	}

	public function stringStatus() {
		Logger::debug('main', 'Starting SERVER::stringStatus for server '.$this->fqdn);

		$string = '';

		if ($this->hasAttribute('locked'))
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

	public function stringSessions() {
		Logger::debug('main', 'Starting SERVER::stringSessions for server '.$this->fqdn);

		$string = '';

		$string .= $this->getNbUsedSessions().'/'.$this->getNbAvailableSessions();

		return $string;
	}

	public function getCpuUsage() {
		$cpu_load = $this->getAttribute('cpu_load');
		$cpu_nb = $this->getAttribute('cpu_nb');

		if ($cpu_nb == 0)
			return false;

		return round(($cpu_load/$cpu_nb)*100).'%';
	}

	public function getRamUsage() {
		$ram_used = $this->getAttribute('ram_used');
		$ram = $this->getAttribute('ram');

		if ($ram == 0)
			return false;

		return round(($ram_used/$ram)*100).'%';
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
		$l = new ApplicationServerLiaison(NULL,$this->fqdn);
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
		$asl = new ApplicationServerLiaison(NULL,$this->fqdn);
		$applications_on_server = $asl->elements();
		foreach ($applications_on_server as $app_id) {
			$agl = new AppsGroupLiaison($app_id, NULL);
			$ag = $agl->groups();
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

	public function getType() {
		$buf = query_url('http://'.$this->fqdn.'/webservices/server_type.php');

		if ($buf === false)
			return false;

		$this->setAttribute('type', $buf);

		return true;
	}

	public function getVersion() {
		$buf = query_url('http://'.$this->fqdn.'/webservices/server_version.php');

		if ($buf === false)
			return false;

		$this->setAttribute('version', $buf);

		return true;
	}

	// ?
	public function isOK() {
		return true;
	}

	public function onDB() {
		return true;
	}
}
