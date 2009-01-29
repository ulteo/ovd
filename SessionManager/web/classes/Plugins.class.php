<?php
/**
 * Copyright (C) 2008 Ulteo SAS
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

class Plugins {
	public function doLoad() {
		Logger::debug('main', 'Plugins::doLoad()');

		//set_error_handler('plugin_error');

		$prefs = Preferences::getInstance();
		if (! $prefs)
			return false;

		$plugins_enable = $prefs->get('plugins', 'plugin_enable');
		$fs = $prefs->get('plugins', 'FS');
		// for now we can use one FS at the same time
		//if (!is_array($fs) || count($fs) == 0)
		if (is_null($fs))
			return false;
		//$module_fs = $fs[0];
		$module_fs = $fs;

		$this->plugins = array();

		$fs = 'FS_'.$module_fs;
		$this->plugins[$module_fs] = new $fs();

		foreach ($plugins_enable as $use_plugin) {
			$plugin = 'Plugin_'.strtolower($use_plugin);

			$this->plugins[$use_plugin] = new $plugin();
			$this->plugins[$use_plugin]->redir_args = array();
		}

		return $this->plugins;

		//restore_error_handler();
	}

	public function doInit($params_=array()) {
		Logger::debug('main', 'Plugins::doInit()');

		//set_error_handler('plugin_error');

		foreach ($this->plugins as $k => $v)
			$this->plugins[$k]->init($params_);

		return $this->plugins;

		//restore_error_handler();
	}

	public function doStartsession($params_=array()) {
		Logger::debug('main', 'Plugins::doStartsession()');

		//set_error_handler('plugin_error');

		foreach ($this->plugins as $k => $v)
			$this->plugins[$k]->start_session($params_);

		return $this->plugins;

		//restore_error_handler();
	}

	public function doRemovesession($params_=array()) {
		Logger::debug('main', 'Plugins::doRemovesession()');

		//set_error_handler('plugin_error');

		foreach ($this->plugins as $k => $v)
			$this->plugins[$k]->remove_session($params_);

		return $this->plugins;

		//restore_error_handler();
	}

	// ? unclean?
	public function getAvailablePlugins() {
		$ret = array();

		$files = glob(PLUGINS_DIR.'/*');
		foreach ($files as $path) {
			if (is_dir($path)) {
				$files2 = glob($path.'/*');
				foreach ($files2 as $file2) {
					if (is_file($file2)) {
						$pathinfo = pathinfo_filename($file2);

						if (!isset($ret[basename($pathinfo['dirname'])]))
							$ret[basename($pathinfo['dirname'])] = array();

						if ($pathinfo['extension'] == 'php') {
							$plugin_name = strtoupper(basename($pathinfo['dirname'])).'_'.$pathinfo['filename'];
							$p = new $plugin_name();

							$ret[basename($pathinfo['dirname'])][$pathinfo['filename']] = $p->requirements();
						}
					}
				}
			}

			if (is_file($path)) {
				$pathinfo = pathinfo_filename($path);

				if (!isset($ret['plugins']))
					$ret['plugins'] = array();

				if ($pathinfo['extension'] == 'php') {
					$plugin_name = 'Plugin_'.$pathinfo['filename'];
					$p = new $plugin_name();

					$ret['plugins'][$pathinfo['filename']] = $p->requirements();
				}
			}
		}

		return $ret;
	}
}
