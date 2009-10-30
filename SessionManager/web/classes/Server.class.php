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

	public function getBaseURL($redir_ = false) {
		Logger::debug('main', 'Starting Server::getBaseURL for \''.$this->fqdn.'\'');

		$name = $redir_ ? $this->external_name : $this->fqdn;
		return 'http://'.$name.':'.$this->web_port.'/applicationserver';
	}

	public function getWebservicesBaseURL($redir_ = false) {
		Logger::debug('main', 'Starting Server::getWebservicesBaseURL for \''.$this->fqdn.'\'');

		return $this->getBaseURL($redir_).'/webservices';
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

		$prefs = Preferences::getInstance();
		if (! $prefs) {
			Logger::critical('main', 'get Preferences failed in '.__FILE__.' line '.__LINE__);
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

		if (preg_match('/[0-9]{1,3}(\.[0-9]{1,3}){3}/', $name)) {// if IP?
			$ret = ($name == $address);
			if (! $ret) {
				Logger::error('main', "FQDN does NOT match ApplicationServer's source address (source='$address' sent='$name')");
			}
			return $ret;
		}

		if ($disable_fqdn_check == 1)
			return true;

		$reverse = @gethostbyaddr($address);
		if (($reverse == $name) || (isset($fqdn_private_address[$name]) && $fqdn_private_address[$name] == $address))
			return true;

		Logger::warning('main', '"'.$this->fqdn.'": reverse DNS is invalid! ('.$reverse.')');

		return false;
	}

	public function register() {
		Logger::debug('main', 'Starting Server::register for \''.$this->fqdn.'\'');

		if (! $this->isOnline()) {
			Logger::debug('main', 'Server::register server "'.$this->fqdn.':'.$this->web_port.'" is not online');
			return false;
		}

		if (! $this->isOK()) {
			Logger::debug('main', 'Server::register server "'.$this->fqdn.':'.$this->web_port.'" is not OK');
			return false;
		}

		$this->setAttribute('registered', true);

		return true;
	}

	public function isOnline() {
		Logger::debug('main', 'Starting Server::isOnline for \''.$this->fqdn.'\'');

		$warn = false;

		if (! $this->hasAttribute('status') || ! $this->uptodateAttribute('status')) {
			$warn = true;
			$this->getStatus();
		}

		if ($this->hasAttribute('status') && $this->getAttribute('status') == 'ready')
			return true;

		if ($warn === true) {
			popup_error('"'.$this->fqdn.'": '._('is NOT online!'));
			Logger::error('main', '"'.$this->fqdn.'": is NOT online!');
		}

		$this->isNotReady();

		return false;
	}

	public function isUnreachable() {
		Logger::debug('main', 'Starting Server::isUnreachable for \''.$this->fqdn.'\'');

		if ($this->getAttribute('status') == 'broken') {
			Logger::debug('main', 'Server::isUnreachable server "'.$this->fqdn.':'.$this->web_port.'" is already "broken"');
			return false;
		}

		$ev = new ServerStatusChanged(array(
			'fqdn'		=>	$this->fqdn,
			'status'	=>	ServerStatusChanged::$UNREACHABLE
		));

		Logger::critical('main', 'Server "'.$this->fqdn.':'.$this->web_port.'" is unreachable, status switched to "broken"');
		$this->setAttribute('status', 'broken');

		$ev->emit();

		$this->isNotReady();

		Abstract_Server::modify($this);

		return true;
	}

	public function isNotReady() {
		Logger::debug('main', 'Starting Server::isNotReady for \''.$this->fqdn.'\'');

		if ($this->getAttribute('status') == 'ready') {
			Logger::debug('main', 'Server::isNotReady server "'.$this->fqdn.':'.$this->web_port.'" is "ready"');
			return false;
		}

		$sessions = Sessions::getByServer($this->fqdn);
		foreach ($sessions as $session) {
			if (! is_object($session))
				continue;

			$session->setStatus(3);

			Abstract_Session::save($session);
		}

		$prefs = Preferences::getInstance();
		if (! $prefs) {
			Logger::critical('main', 'get Preferences failed in '.__FILE__.' line '.__LINE__);
			return false;
		}

		$buf = $prefs->get('general', 'application_server_settings');
		if ($buf['action_when_as_not_ready'] == 1)
			if ($this->getAttribute('locked') === false)
				$this->setAttribute('locked', true);

		Abstract_Server::modify($this);

		return true;
	}

	public function returnedError() {
		Logger::debug('main', 'Starting Server::returnedError for \''.$this->fqdn.'\'');

		if ($this->getAttribute('status') == 'broken') {
			Logger::debug('main', 'Server::returnedError server "'.$this->fqdn.':'.$this->web_port.'" is already "broken"');
			return false;
		}

		Logger::error('main', 'Server "'.$this->fqdn.':'.$this->web_port.'" returned an ERROR, status switched to "broken"');
		$this->setAttribute('status', 'broken');

		$this->isNotReady();
		return true;
	}

	public function getStatus() {
		Logger::debug('main', 'Starting Server::getStatus for \''.$this->fqdn.'\'');

		if ($this->getAttribute('status') == 'down' || $this->getAttribute('status') == 'broken') {
			$this->isNotReady();
			return false;
		}

		$ret = query_url_return_errorcode($this->getWebservicesBaseURL().'/server_status.php');
		list($returncode, $returntext) = $ret;

		if ($returncode != 200) {
			$this->returnedError();
			return false;
		}

		if (! $returntext) {
			$this->isUnreachable();
			return false;
		}

		$this->setStatus($returntext);

		if ($ret !== 'ready')
			$this->isNotReady();

		return true;
	}

	public function setStatus($status_) {
		Logger::debug('main', 'Starting Server::setStatus for \''.$this->fqdn.'\'');

		$ev = new ServerStatusChanged(array(
			'fqdn'		=>	$this->fqdn,
			'status'	=>	($status_ == 'ready')?ServerStatusChanged::$ONLINE:ServerStatusChanged::$OFFLINE
		));

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

		$ev->emit();

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

		if (! $this->isOnline()) {
			Logger::debug('main', 'Server::getType server "'.$this->fqdn.':'.$this->web_port.'" is not online');
			return false;
		}

		$buf = query_url($this->getWebservicesBaseURL().'/server_type.php');

		if (! $buf) {
			$this->isUnreachable();
			return false;
		}

		if (! is_string($buf))
			return false;

		if ($buf == '')
			return false;

		$this->setAttribute('type', $buf);

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

		if (! $this->isOnline()) {
			Logger::debug('main', 'Server::getVersion server "'.$this->fqdn.':'.$this->web_port.'" is not online');
			return false;
		}

		$buf = query_url($this->getWebservicesBaseURL().'/server_version.php');

		if (! $buf) {
			$this->isUnreachable();
			return false;
		}

		if (! is_string($buf))
			return false;

		if ($buf == '')
			return false;

		$this->setAttribute('version', $buf);

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

		if (! $this->isOnline()) {
			Logger::debug('main', 'Server::getMonitoring server "'.$this->fqdn.':'.$this->web_port.'" is not online');
			return false;
		}

		$xml = query_url($this->getWebservicesBaseURL().'/server_monitoring.php');

		if (! $xml) {
			$this->isUnreachable();
			Logger::error('main', 'Server::getMonitoring server \''.$this->fqdn.'\' is unreachable');
			return false;
		}

		if (! is_string($xml)) {
			Logger::error('main', 'Server::getMonitoring invalid xml1');
			return false;
		}

		if (substr($xml, 0, 5) == 'ERROR') {
			$this->returnedError();
			Logger::error('main', 'Server::getMonitoring invalid xml2');
			return false;
		}

		if ($xml == '') {
			Logger::error('main', 'Server::getMonitoring invalid xml3');
			return false;
		}

		$dom = new DomDocument('1.0', 'utf-8');
		$ret = @$dom->loadXML($xml);
		if (! $ret) {
			Logger::error('main', 'Server::getMonitoring loadXML failed');
			return false;
		}

		$keys = array();

		$cpu_node = $dom->getElementsByTagname('cpu')->item(0);
		if (is_object($cpu_node)) {
			$keys['cpu_model'] = $cpu_node->firstChild->nodeValue;
			if ($cpu_node->hasAttribute('nb_cores'))
				$keys['cpu_nb_cores'] = $cpu_node->getAttribute('nb_cores');
			if ($cpu_node->hasAttribute('load'))
				$keys['cpu_load'] = $cpu_node->getAttribute('load');
		}

		$ram_node = $dom->getElementsByTagname('ram')->item(0);
		if (is_object($ram_node)) {
			if ($ram_node->hasAttribute('total'))
				$keys['ram_total'] = $ram_node->getAttribute('total');
			if ($ram_node->hasAttribute('used'))
				$keys['ram_used'] = $ram_node->getAttribute('used');
		}

		foreach ($keys as $k => $v)
			$this->setAttribute($k, trim($v));

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

		Abstract_Liaison::delete('StaticApplicationServer', NULL, $this->fqdn);

		return true;
	}

	public function getSessionStatus($session_id_) {
		Logger::debug('main', 'Starting Server::getSessionStatus for session \''.$session_id_.'\' on server \''.$this->fqdn.'\'');

		$ret = query_url_no_error($this->getWebservicesBaseURL().'/session_status.php?session='.$session_id_);

		return $ret;
	}

	public function orderSessionDeletion($session_id_) {
		Logger::debug('main', 'Starting Server::orderSessionDeletion for session \''.$session_id_.'\' on server \''.$this->fqdn.'\'');

		$ret = query_url($this->getWebservicesBaseURL().'/kill_session.php?session='.$session_id_);

		return $ret;
	}

	public function getWebLog($nb_lines_=NULL) {
		Logger::debug('main', 'Starting Server::getLog for server \''.$this->fqdn.'\'');

		if ($nb_lines_ === NULL)
			$ret = query_url($this->getWebservicesBaseURL().'/server_log.php?type=web', false);
		else
			$ret = query_url($this->getWebservicesBaseURL().'/server_log.php?type=web&nb_lines='.$nb_lines_, false);

		return $ret;
	}

	public function getDaemonLog($nb_lines_=NULL) {
		Logger::debug('main', 'Starting Server::getLog for server \''.$this->fqdn.'\'');

		if ($this->getAttribute('type') == 'windows') {
			Logger::error('main', 'Server::getDaemonLog - No daemon log for windows server');
			return false;
		}

		if ($nb_lines_ === NULL)
			$ret = query_url($this->getWebservicesBaseURL().'/server_log.php?type=daemon', false);
		else
			$ret = query_url($this->getWebservicesBaseURL().'/server_log.php?type=daemon&nb_lines='.$nb_lines_, false);

		return $ret;
	}

	public function getWebLogFile() {
		Logger::debug('main', 'Starting Server::getWebLogFile for server \''.$this->fqdn.'\'');

		$ret = query_url_request($this->getWebservicesBaseURL().'/server_log.php?type=web', false, true);
		if (is_array($ret)) {
			return $ret['data'];
		}
		else {
			return false;
		}
	}

	public function getDaemonLogFile() {
		Logger::debug('main', 'Starting Server::getDaemonLogFile for server \''.$this->fqdn.'\'');

		if ($this->getAttribute('type') == 'windows') {
			Logger::error('main', 'Server::getDaemonLogFile - No daemon log for windows server');
			return false;
		}

		$ret = query_url_request($this->getWebservicesBaseURL().'/server_log.php?type=daemon', false, true);
		if (is_array($ret)) {
			return $ret['data'];
		}
		else {
			return false;
		}

	}

	public function getApplicationIcon($icon_path_, $desktopfile_) {
		Logger::debug('main', 'Starting Server::getApplicationIcon for path \''.$icon_path_.'\', desktop_file \''.$desktopfile_.'\' on server \''.$this->fqdn.'\'');

		$ret = query_url($this->getWebservicesBaseURL().'/icon.php?path='.base64_encode($icon_path_).'&desktopfile='.base64_encode($desktopfile_));

		if ($ret == '')
			return false;

		return $ret;
	}

	// ? unclean?
	public function getApplications() {
		Logger::debug('main', 'SERVER::getApplications for server '.$this->fqdn);

		$applicationDB = ApplicationDB::getInstance();

		$ls = Abstract_Liaison::load('ApplicationServer', NULL, $this->fqdn);
		if (is_array($ls)) {
			$res = array();
			foreach ($ls as $l) {
				$a = $applicationDB->import($l->element);
				if (is_object($a))
					$res []= $a;
			}
			return $res;
		} else {
			Logger::error('main', 'SERVER::getApplications elements is not array');
			return NULL;
		}
	}

	public function updateApplications(){
		Logger::debug('main', 'Server::updateApplications');
		$prefs = Preferences::getInstance();
		if (! $prefs) {
			Logger::critical('main', 'get Preferences failed in '.__FILE__.' line '.__LINE__);
			return false;
		}

		if (! $this->isOnline()) {
			Logger::debug('main', 'Server::updateApplications server "'.$this->fqdn.':'.$this->web_port.'" is not online');
			return false;
		}

		$mods_enable = $prefs->get('general','module_enable');
		if (!in_array('ApplicationDB',$mods_enable)){
			die_error(_('Module ApplicationDB must be enabled'),__FILE__,__LINE__);
		}
		$mod_app_name = 'admin_ApplicationDB_'.$prefs->get('ApplicationDB','enable');
		$applicationDB = new $mod_app_name();

		$xml = query_url($this->getWebservicesBaseURL().'/applications.php');

		if (! $xml) {
			$this->isUnreachable();
			Logger::error('main', 'Server::updateApplications server \''.$this->fqdn.'\' is unreachable');
			return false;
		}

		if (! is_string($xml)) {
			Logger::error('main', 'Server::updateApplications invalid xml1');
			return false;
		}

		if (substr($xml, 0, 5) == 'ERROR') {
			$this->returnedError();
			Logger::error('main', 'Server::updateApplications invalid xml2');
			return false;
		}

		if ($xml == '') {
			Logger::error('main', 'Server::updateApplications invalid xml3');
			return false;
		}

		$dom = new DomDocument('1.0', 'utf-8');
		@$dom->loadXML($xml);
		$root = $dom->documentElement;

		// before adding application, we remove all previous applications
		$previous_liaison = Abstract_Liaison::load('ApplicationServer', NULL, $this->fqdn); // see end of function
		$current_liaison_key = array();

		$application_node = $dom->getElementsByTagName("application");
		foreach($application_node as $app_node){
			$app_name = '';
			$app_description = '';
			$app_path_exe = '';
			$app_path_args = NULL;
			$app_path_icon = NULL;
			$app_package = NULL;
			$app_desktopfile = NULL;
			$app_mimetypes = NULL;
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
			if ($exe_node->hasAttribute("mimetypes"))
				$app_mimetypes = $exe_node->getAttribute("mimetypes");

			$a = new Application(NULL,$app_name,$app_description,$this->getAttribute('type'),$app_path_exe,$app_package,$app_path_icon,$app_mimetypes,true,$app_desktopfile);
			$a_search = $applicationDB->search($app_name,$app_description,$this->getAttribute('type'),$app_path_exe);
			if (is_object($a_search)){
				//already in DB
				// echo $app_name." already in DB\n";
				$a = $a_search;
			}
			else {
				// echo $app_name." NOT in DB\n";
				if ($applicationDB->isWriteable() == false){
					Logger::debug('main', 'Server::updateApplications applicationDB is not writeable');
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
					if (!is_object(Abstract_Liaison::load('ApplicationServer', $a->getAttribute('id'),$this->fqdn))) {
						$ret = Abstract_Liaison::save('ApplicationServer', $a->getAttribute('id'),$this->fqdn);
						if ($ret === false) {
							Logger::error('main', 'Server::updateApplications failed to save application');
							return $ret;
						}
					}
					$current_liaison_key[] = $a->getAttribute('id');
				}
				else{
					//echo "Application not ok<br>\n";
				}
			}
		}

		$previous_liaison_key = array_keys($previous_liaison);
		foreach ($previous_liaison_key as $key){
			if (in_array($key, $current_liaison_key) == false) {
				$a = $applicationDB->import($key);
				if ( is_null($a) || $a->getAttribute('static') == false)
					Abstract_Liaison::delete('ApplicationServer', $key, $this->fqdn);
			}
		}
		return true;
	}
	
	public function getInstallableApplications() {
		Logger::debug('main', 'Server::getInstallableApplications');
		return query_url($this->getWebservicesBaseURL().'/installable_applications.php', false);
	}
}
