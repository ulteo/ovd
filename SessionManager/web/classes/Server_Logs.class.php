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

	public $log = NULL; //pointer to the fopen resource

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
		if (is_resource($this->log))
			@fclose($this->log);
	}

	public function __toString() {
		return 'Server_Logs(\''.$this->server->fqdn.'\')';
	}

	public function fetchLogs($since_=NULL) {
		Logger::debug('main', 'Starting Server_Logs::fetchLogs for server \''.$this->server->fqdn.'\'');

		if (is_null($since_))
			$since_ = time();

		$ret = query_url($this->server->getWebservicesBaseURL().'/server/logs/since/'.$since_, false);
		if ($ret === false) {
			$this->server->isUnreachable();
			Logger::error('main', 'Server_Logs::fetchLogs server \''.$this->server->fqdn.'\' is unreachable');
			return false;
		}

		@file_put_contents($this->logsdir.'/'.date('Ymd').'.log', $ret."\n", FILE_APPEND);

		$obj = new FileTailer($this->logsdir.'/'.date('Ymd').'.log');
		$buf = $obj->tail_str(1);
		if ($buf != '') {
			$buf = preg_match('/^([0-9]+)-([0-9]+)-([0-9]+) ([0-9+]):([0-9]+):([0-9]+),/', $buf, $matches);
			if (is_array($matches) && count($matches) == 7)
				$last = mktime($matches[4], $matches[5], $matches[6], $matches[2], $matches[3], $matches[1]);
		}

		@file_put_contents($this->logsdir.'/last', ((isset($last))?$last:time()));
		@file_put_contents($this->logsdir.'/since', time());

		return true;
	}

	public function getLog($nb_lines_=NULL) {
		if (is_null($nb_lines_) || ! is_numeric($nb_lines_) || $nb_lines_ == 0)
			return @file_get_contents($this->logsdir.'/'.date('Ymd').'.log');
		else {
			$obj = new FileTailer($this->logsdir.'/'.date('Ymd').'.log');
			return $obj->tail_str($nb_lines_);
		}
	}

	public function getContent() {
		if (! is_resource($this->log))
			$this->log = @fopen($this->logsdir.'/'.date('Ymd').'.log', 'r');
		$fp = $this->log;

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
