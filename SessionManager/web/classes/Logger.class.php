<?php
/**
 * Copyright (C) 2008-2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2009
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2008
 * Author Julien LANGLOIS <julien@ulteo.com> 2010
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
require_once(dirname(__FILE__).'/../includes/core-minimal.inc.php');

class Logger {
	protected static $instances = array();
	protected $filename = NULL;
	protected $fd = NULL;

	protected $level_flags = NULL;

	public function __construct($module_='main') {
		$this->filename = SESSIONMANAGER_LOGS.'/'.strtolower($module_).'.log';
		$this->fd = @fopen($this->filename, 'a');
		if ($this->fd === false)
			$this->fd = NULL;

		try {
			$prefs = Preferences::getInstance();
			$level_flags2 = array();

			if (is_object($prefs))
				$level_flags2 = $prefs->get('general', 'log_flags');
			if (! isset($level_flags2) || ! is_array($level_flags2))
				$level_flags2 = array($level_);

			$this->level_flags = $level_flags2;
		} catch (Exception $e) {}
	}

	public function __destruct() {
		if (! is_null($this->fd))
			@fclose($this->fd);
	}

	public function write($data_, $level_='info') {
		if (is_array($this->level_flags) && ! in_array($level_, $this->level_flags))
			return;

		if (is_null($this->fd))
			return;

		$msg = @date('M j H:i:s').' - '.@$_SERVER['REMOTE_ADDR'].' - '.strtoupper($level_).' - ';
		if (is_null($this->level_flags))
			$msg.=  'NOPREFS - ';
		$msg.= $data_."\r\n";

		@fwrite($this->fd, $msg);
	}

	public static function append($module_='main', $data_='', $level_='info') {
		if ($data_ == '')
			return;

		if (! array_key_exists($module_, self::$instances))
			self::$instances[$module_] = new Logger($module_);

		self::$instances[$module_]->write($data_, $level_);
	}

	public static function debug($module_='main', $data_='') {
		Logger::append($module_, $data_, 'debug');
	}

	public static function info($module_='main', $data_='') {
		Logger::append($module_, $data_, 'info');
	}

	public static function warning($module_='main', $data_='') {
		Logger::append($module_, $data_, 'warning');
	}

	public static function error($module_='main', $data_='') {
		Logger::append($module_, $data_, 'error');
	}

	public static function critical($module_='main', $data_='') {
		Logger::append($module_, $data_, 'critical');
	}
}
