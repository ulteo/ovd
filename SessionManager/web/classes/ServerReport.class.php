<?php
/**
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Gauvain Pocentek <gauvain@ulteo.com>
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
/* Namespaces in PHP? would be nice... */
require_once(CLASSES_DIR.'/ReportItems.class.php');

class ServerReport {
	private static $instance;

	private $datadir;

	private $down_periods;
	private $maintenance_periods;
	private $sessions_count;
	private $max_connections;
	private $max_ram; /* in percents */

	public function __construct() {
		$this->datadir = SESSIONMANAGER_SPOOL.'/serverreport';
		$today = date('Ymd', time());
		$this->file = $this->datadir.'/'.$today;

		if (! is_dir($this->datadir))
			mkdir($this->datadir);

		if (! is_file($this->file))
		{
			/*
			 * If the file doesn't exist, we are likely to handle the first
			 * session of the day; first compute the other files found in the
			 * dir.
			 */
			foreach (glob($this->datadir.'/*') as $day) {
				/* just to be sure */
				if ($day == $this->file)
					continue;
				Logger::debug('main', "ServerReport: processing report file: ".$day);
				self::computeDay(basename($day));
				unlink($day);
			}
		}
	}

	public static function load() {
		$file = self::getFile();

		if (! is_file($file))
			self::$instance = new ServerReport();
		else
			self::$instance = unserialize(file_get_contents($file));

		return self::$instance;
	}

	public static function getFile() {
		return SESSIONMANAGER_SPOOL.'/serverreport/'.date('Ymd', time());
	}

	public function save() {
		if (! isset($this->file))
			return false;

		Logger::debug('main', "ServerReport::save");
		return file_put_contents($this->file,serialize($this));
	}

	public function reportSessionStart($fqdn_) {
		if (! isset($this->sessions_count[$fqdn_]))
			$this->sessions_count[$fqdn_] = new ReportCountItem(1);
		else
			$this->sessions_count[$fqdn_]->inc();

		/* do we have the max sessions nb ever ? */
		$server = Abstract_Server::load($fqdn_);
		if ($server === false)
			return;

		$conn_nb = $server->getNbUsedSessions();
		if (! isset($this->max_connections[$fqdn_]))
			$this->max_connections[$fqdn_] = new ReportMaxItem($conn_nb +1 ); /* current sess */
		else
			$this->max_connections[$fqdn_]->set($conn_nb);

		/* ram usage */
		$ram_percent = $server->getRamUsage();
		if (! isset($this->max_ram[$fqdn_]))
			$this->max_ram[$fqdn_] = new ReportMaxItem($ram_percent);
		else
			$this->max_ram[$fqdn_]->set($ram_percent);
	}

	public function reportIsDown($fqdn_) {
		if (isset($this->down_periods[$fqdn_]))
		{
			foreach ($this->down_periods[$fqdn_] as $period)
			{
				/* if we have an inProgress period we already know that the
				 * serveur is down */
				if (! is_null($period) &&  $period->isDone())
					return;
			}
		} else
			$this->down_periods[$fqdn_][] = new ReportIntervalItem();
	}

	public function reportIsUp($fqdn_) {
		if (! isset($this->down_periods[$fqdn_]))
			return;

		foreach ($this->down_periods[$fqdn_] as $period) {
			if (! $period->isDone())
				$period->end();
		}
	}

	private static function _intervalsToDuration($periods_, $day_) {
		$ret = 0;
		foreach($periods_ as $p) {
			if ($p->isDone())
				$ret += $p->elapsed;
			else {
				$y = int(substr($day_, 0, 4));
				$m = int(substr($day_, 4, 2));
				$d = int(substr($day, 6, 2)) + 1;
				$ret += mktime(0, 0, 0, $m, $d, $y) - $p->$start;
			}
		}

		return $ret;
	}

	static public function computeDay($day_) {
		$servers = Abstract_Server::load_all();
		foreach ($servers as $server) {
			self::computeFqdnDay($day_, $server->fqdn);
		}
	}

	static public function computeFqdnDay($day_, $fqdn_) {
		$file = SESSIONMANAGER_SPOOL.'/serverreport/'.$day_;
		$ret = array();
		if (! is_readable($file))
			return true;

		$obj = unserialize(file_get_contents($file));

		$down_time = 0;
		$maintenance_time = 0;
		$sessions_count = 0;
		$max_connections = 0;
		$max_connections_when = 0;
		$max_ram = 0;
		$max_ram_when = 0;

		if (isset($obj->down_periods[$fqdn_]))
			$down_time = self::_intervalsToDuration($obj->down_periods[$fqdn_], $day_);

		if (isset($obj->maintenance_periods[$fqdn_]))
			$maintenance_time =
				self::_intervalsToDuration($obj->maintenance_periods[$fqdn_], $day_);

		if (isset($obj->sessions_count[$fqdn_]))
			$sessions_count = $obj->sessions_count[$fqdn_]->get();

		if (isset($obj->max_connections[$fqdn_])) {
			$max_connections = $obj->max_connections[$fqdn_]->get();
			$max_connections_when = $obj->max_connections[$fqdn_]->getLastUpdate();
		}

		if (isset($obj->max_ram[$fqdn_])) {
			$max_ram = $obj->max_ram[$fqdn_]->get();
			$max_ram_when = $obj->max_ram[$fqdn_]->getLastUpdate();
		}

		$sql = MySQL::getInstance();
		$sql->DoQuery('SELECT * FROM @1 WHERE date=%2 AND fqdn=%3',
		              SERVERS_REPORT_TABLE, $day_, $fqdn_);
		if ($sql->NumRows() != 0)
			return;

		$res = $sql->DoQuery('INSERT INTO @1 (@2,@3,@4,@5,@6,@7,@8,@9,@10) VALUES '.
		                     '(%11,%12,%13,%14,%15,%16,%17,%18,%19)',
							 SERVERS_REPORT_TABLE,
							 'date', 'fqdn', 'down_time', 'maintenance_time', 'sessions_count',
							 'max_connections', 'max_connections_when',
							 'max_ram', 'max_ram_when',
							 $day_, $fqdn_, $down_time, $maintenance_time, $sessions_count,
							 $max_connections, $max_connections_when,
							 $max_ram, $max_ram_when);
	}

	/* TODO: weekly, monthly and yearly computing */


	public function dump() {
		/* for debug */
		var_dump($this);
	}
}
