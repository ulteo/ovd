<?php
/**
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
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

class Server_Logs {
	public $server = NULL;

	public $logsdir = NULL;

	public $since = NULL;
	public $last = NULL;

	public $weblog = NULL; //pointer to the fopen resource
	public $daemonlog = NULL; //pointer to the fopen resource

	public function __construct($server_) {
// 		Logger::debug('main', 'Starting Server_Logs::__construct for \''.$server_->fqdn.'\'');

		if (! check_folder(SESSIONMANAGER_SPOOL.'/cache/logs')) {
			Logger::error('main', 'Server_Logs::__construct Unable to create global logs cache folder');
			die_error(SESSIONMANAGER_SPOOL.'/cache/logs does not exist and cannot be created!', __FILE__, __LINE__);
		}

		$this->server = $server_;

		$this->logsdir = SESSIONMANAGER_SPOOL.'/cache/logs/'.$this->server->fqdn;
		if (! check_folder($this->logsdir)) {
			Logger::error('main', 'Server_Logs::__construct Unable to create logs cache folder for server \''.$this->server->fqdn.'\'');
			die_error($this->logsdir.' does not exist and cannot be created!', __FILE__, __LINE__);
		}

		$this->since = @file_get_contents($this->logsdir.'/since');
		if (! $this->since)
			$this->since = 0;
		$this->last = @file_get_contents($this->logsdir.'/last');
		if (! $this->last)
			$this->last = 0;
	}

	public function __destruct() {
		if (is_resource($this->weblog))
			@fclose($this->weblog);
		if (is_resource($this->daemonlog))
			@fclose($this->daemonlog);
	}

	public function __toString() {
		return 'Server_Logs(\''.$this->server->fqdn.'\')';
	}

	public function fetchLogs($since_=NULL) {
		Logger::debug('main', 'Starting Server_Logs::fetchLogs for server \''.$this->server->fqdn.'\'');

		if (is_null($since_))
			$since_=time();

		$ret = query_url($this->server->getWebservicesBaseURL().'/server_log.php?since='.$since_, false);

		$dom = new DomDocument('1.0', 'utf-8');
		$buf = @$dom->loadXML($ret);
		if (! $buf) {
			Logger::error('main', 'Server_Logs::fetchLogs Invalid XML');
			return false;
		}

		if (! $dom->hasChildNodes()) {
			Logger::error('main', 'Server_Logs::fetchLogs Invalid XML');
			return false;
		}

		$log_node = $dom->getElementsByTagname('log')->item(0);
		if (is_null($log_node)) {
			Logger::error('main', 'Server_Logs::fetchLogs Missing element \'log\'');
			return false;
		}

		$log_web_node = $log_node->getElementsByTagname('web')->item(0);
		if (is_null($log_web_node)) {
			Logger::error('main', 'Server_Logs::fetchLogs Missing element \'web\'');
			return false;
		}

		$log_daemon_node = $log_node->getElementsByTagname('daemon')->item(0);
		if (is_null($log_daemon_node)) {
			Logger::error('main', 'Server_Logs::fetchLogs Missing element \'daemon\'');
			return false;
		}

		@file_put_contents($this->logsdir.'/since', time());
		@file_put_contents($this->logsdir.'/last', $log_node->getAttribute('last'));

		$buf = base64_decode($log_web_node->firstChild->nodeValue);
		@file_put_contents($this->logsdir.'/web-'.date('Ymd').'.log', $buf, FILE_APPEND);

		$buf = base64_decode($log_daemon_node->firstChild->nodeValue);
		@file_put_contents($this->logsdir.'/daemon-'.date('Ymd').'.log', $buf, FILE_APPEND);

		return true;
	}

	public function getWebLog($nb_lines_=NULL) {
		if (is_null($nb_lines_) || ! is_numeric($nb_lines_) || $nb_lines_ == 0)
			return @file_get_contents($this->logsdir.'/web-'.date('Ymd').'.log');
		else
			return shell_exec('tail -n '.$nb_lines_.' '.$this->logsdir.'/web-'.date('Ymd').'.log');
	}

	public function getDaemonLog($nb_lines_=NULL) {
		if (is_null($nb_lines_) || ! is_numeric($nb_lines_) || $nb_lines_ == 0)
			return @file_get_contents($this->logsdir.'/daemon-'.date('Ymd').'.log');
		else
			return shell_exec('tail -n '.$nb_lines_.' '.$this->logsdir.'/daemon-'.date('Ymd').'.log');
	}

	public function getContent($type_) {
		if ($type_ == 'web') {
			if (! is_resource($this->weblog))
				$this->weblog = @fopen($this->logsdir.'/web-'.date('Ymd').'.log', 'r');
			$fp = $this->weblog;
		} elseif ($type_ == 'daemon') {
			if (! is_resource($this->daemonlog))
				$this->daemonlog = @fopen($this->logsdir.'/daemon-'.date('Ymd').'.log', 'r');
			$fp = $this->daemonlog;
		}

		if ($fp !== false)
			return fgets($fp, 4096);

		return false;
	}

	public function process() {
		Logger::debug('main', 'Starting Server_Logs::process for server \''.$this->server->fqdn.'\'');

		$prefs = Preferences::getInstance();
		$cache_update_interval = (int)$prefs->get('general', 'cache_update_interval');
		$cache_expiry_time = (int)$prefs->get('general', 'cache_expiry_time');

		$this->purge_expired_logs();

		if (! $this->server->isOnline()) {
			Logger::debug('main', 'Server_Logs::process Server \''.$this->server->fqdn.'\' is NOT online');
			return false;
		}

		if ($this->since >= (time()-$cache_update_interval)) {
			Logger::debug('main', 'Server_Logs::process Logs cache for Server \''.$this->server->fqdn.'\' is up-to-date');
			return true;
		}

		if (! $this->fetchLogs($this->last)) {
			Logger::error('main', 'Server_Logs::process Error while fetching logs for Server \''.$this->server->fqdn.'\'');
			return false;
		}

		Logger::debug('main', 'Server_Logs::process Logs cache for Server \''.$this->server->fqdn.'\' updated');

		return true;
	}

	public function purge_expired_logs() {
		Logger::debug('main', 'Starting Server_Logs::purge for server \''.$this->server->fqdn.'\'');

		$prefs = Preferences::getInstance();
		$cache_expiry_time = (int)$prefs->get('general', 'cache_expiry_time');
		$delete_before = (int)(date('Ymd', (time()-$cache_expiry_time)));

		$files = glob($this->logsdir.'/*');
		foreach ($files as $file) {
			$buf = preg_match('@.+\-([0-9]+)\.log@', basename($file), $matches);
			if (! $buf)
				continue;

			$file_date = (int)$matches[1];

			if ($file_date < $delete_before)
				@unlink($file);
		}

		return true;
	}
}
