<?php
/**
 * Copyright (C) 2013 Ulteo SAS
 * http://www.ulteo.com
 * Author David PHAM-VAN <d.pham-van@ulteo.com> 2013
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

class Plugin {
	private static $plugins = NULL;

	public function getName() {
		return get_class($this);
	}

	public function __toString() {
		return $this->getName();
	}

	private static final function getPlugins() {
		if (self::$plugins == NULL) {
			self::$plugins = array();

			foreach (glob(SESSIONMANAGER_PLUGINS_DIR . '/*Plugin.inc.php') as $file) {
				require_once($file);
				$file = basename($file);
				$classname = substr($file, 0, strlen($file)-8);
				if (class_exists($classname, false) && is_subclass_of($classname, 'Plugin')) {
					self::$plugins[$classname] = new $classname();
				}
			}
		}

		return self::$plugins;
	}

	public static final function dispatch() {
		$args = func_get_args();
		$name = array_shift($args);
		
		$result = array();
		
		foreach (self::getPlugins() as $pname=>$plugin) {
			if (is_callable(array($plugin, $name), false)) {
				try {
					$result[$pname] = call_user_func_array(array($plugin, $name), $args);
				} catch (Exception $e) {
					Logger::error('main', 'Exception while running ' . $pname . '::' . $name . ' : ' . $e->getMessage());
				}
			}
		}
		
		return $result;
	}

	public static final function dispatch_one() {
		$args = func_get_args();
		$name = array_shift($args);
		$pname = array_shift($args);
		
		$plugins = self::getPlugins();
		if (array_key_exists($pname, $plugins) && is_callable(array($plugins[$pname], $name), false)) {
				return call_user_func_array(array($plugins[$pname], $name), $args);
		}
		
		throw new BadMethodCallException($pname . '::' . $name . ' not found');
	}

}
