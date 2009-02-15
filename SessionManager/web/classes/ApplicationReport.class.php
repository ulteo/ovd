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

class RunningApp extends ReportRunningItem {
	public $app_id;
}

class ApplicationReport {
	private static $instance;
	private $datadir;

	private $state = array();
	private $record = array();
	private $max_use = array();

	public function __construct() {
		$this->datadir = SESSIONMANAGER_SPOOL.'/applicationsreport';
		$today = date('Ymd', time());
		$this->file = $this->datadir.'/'.$today;

		if (! is_dir($this->datadir))
			mkdir($this->datadir);

		if (! is_file($this->file))
		{
			/* Compute previous days, applications still running will be kept
			 * in the new object */
			foreach (glob($this->datadir.'/*') as $day) {
				/* just to be sure */
				if ($day == $this->file)
					continue;
				Logger::debug('main', "ApplicationReport: processing report file: ".$day);
				self::computeDay(basename($day), $this);
				unlink($day);
			}
		}
	}

	public static function load() {
		$file = self::getFile();

		if (! is_file($file))
			self::$instance = new ApplicationReport();
		else
			self::$instance = unserialize(file_get_contents($file));

		return self::$instance;
	}

	public function update($data_) {
		$perappcount = array();

		/* first read the current state to find items that ended */
		foreach ($this->state as $server => $server_data) {
			$perappcount[$server] = array();

			if (! array_key_exists($server, $this->record))
				$this->record[$server] = array();

			/* no session on $server, means that all registred apps ended */
			if (! array_key_exists($server, $data_)) {
				foreach ($server_data as $pid => $running_app) {
					/* save the finished job */
					$this->state[$server][$pid]->stop();
					$this->record[$server][] = clone $this->state[$server][$pid];
				}
				/* cleanup and process the next server*/
				unset($this->state[$server]);
				continue;
			}

			/* check each registred element in $server[] */
			foreach ($server_data as $pid => $running_app) {
				if (! array_key_exists($pid, $data_[$server])) {
					/* if we don't find the element in the data, it means it
					 * ended */
					$this->state[$server][$pid]->stop();
					$this->record[$server][] = clone $this->state[$server][$pid];
					unset($this->state[$server][$pid]);
				} else {
					/* if the key was found, the application still exists, we
					 * do nothing but remove this key from the list so that it
					 * is not computed in the next loop */
					/* Also do a small check to be sure that the pid doesn't
					 * match an other software. This is very unlikely to happen
					 * though */
					$app_id = $this->state[$server][$pid]->app_id;
					if ($app_id == $data_[$server][$pid]) {
						unset($data_[$server][$pid]);

						if (! array_key_exists($app_id, $perappcount[$server]))
							$perappcount[$server][$app_id] = new ReportCountItem(1);
						else
							$perappcount[$server][$app_id]->inc();
					}
				}
			}
		}

		/* now read the remaining data to deal with new process */
		foreach ($data_ as $server => $app_data_) {
			foreach ($app_data_ as $pid => $app_id) {
				if (! isset($this->state[$server]))
					$this->state[$server] = array();
				$this->state[$server][$pid] = new RunningApp();
				$this->state[$server][$pid]->app_id = $app_id;

				if (! array_key_exists($app_id, $perappcount[$server]))
					$perappcount[$server][$app_id] = new ReportCountItem(1);
				else
					$perappcount[$server][$app_id]->inc();
			}
		}

		if (! array_key_exists($server, $this->max_use))
			$this->max_use[$server] = array();

		foreach ($perappcount[$server] as $app_id => $count) {
			if (! array_key_exists($app_id, $this->max_use[$server]))
				$this->max_use[$server][$app_id] = new ReportMaxItem();

			$tmp = $perappcount[$server][$app_id]->get();
			$this->max_use[$server][$app_id]->set($tmp);
		}
	}

	public static function getFile() {
		return SESSIONMANAGER_SPOOL.'/applicationsreport/'.date('Ymd', time());
	}

	public function save() {
		if (! isset($this->file))
			return false;

		Logger::debug('main', "ApplicationReport::save");
		return file_put_contents($this->file,serialize($this));
	}

	static public function computeDay($day_, $current_) {
		$servers = Abstract_Server::load_all();

		$file = SESSIONMANAGER_SPOOL.'/applicationsreport/'.$day_;
		$ret = array();
		if (! is_readable($file))
			return true;

		$obj = unserialize(file_get_contents($file));

		$SQL = MySQL::getInstance();

		foreach ($servers as $server) {
			$fqdn = $server->fqdn;

			/* get applications still running and duplicate them in the current
			 * report */
			if (array_key_exists($fqdn,$obj->state)) {
				if (! array_key_exists($fqdn, $current_->state))
					$current_->state[$fqdn] = array();

				foreach ($obj->state[$fqdn] as $pid => $running_app) {
					$current_->state[$fqdn][$pid] = clone $running_app;
				}

				$current_->save();
			}

			/* we only store applications that ended in the DB */
			if (! array_key_exists($fqdn, $obj->record))
				continue;

			$count = array();
			$max_count = array();
			$max_count_when = array();
			foreach ($obj->record[$fqdn] as $app_data) {
				$app_id = $app_data->app_id;
				if (! array_key_exists($app_id, $count))
					$count[$app_id] = 1;
				else
					$count[$app_id]++;

				/* this should always be verified */
				if (isset ($obj->max_use[$fqdn][$app_id])) {
					$max_count[$app_id] = $obj->max_use[$fqdn][$app_id]->get();
					$max_count_when[$app_id] =
						$obj->max_use[$fqdn][$app_id]->getLastUpdate();
				} else {
					/* just in case */
					$max_count[$app_id] = 0;
					$max_count_when[$app_id] = 0;
				}
			}

			foreach ($max_count as $app_id => $max_use) {
				if (! array_key_exists($app_id, $count)) {
					/* this shouldn't happen */
					$use_count = 0;
				} else {
					$use_count = $count[$app_id];
				}

				$SQL->DoQuery('SELECT * FROM @1 WHERE date=%2 AND fqdn=%3 AND app_id=%4',
				              APPLICATIONS_REPORT_TABLE, $day_, $fqdn, $app_id);
				if ($SQL->NumRows() != 0)
					continue;

				$res = $SQL->DoQuery('INSERT INTO @1 (@2,@3,@4,@5,@6,@7) VALUES '.
				                     '(%8,%9,%10,%11,%12,%13)',
				                     APPLICATIONS_REPORT_TABLE,
				                     'date', 'fqdn', 'app_id', 'use_count',
									 'max_use', 'max_use_when',
				                     $day_, $fqdn, $app_id, $use_count,
									 $max_use, $max_count_when[$app_id]);
			}
		}
		unset ($obj);
	}

	/* TODO: monthly and yearly computing */

	public function dump() {
		/* for debug */
		var_dump($this);
	}
}
