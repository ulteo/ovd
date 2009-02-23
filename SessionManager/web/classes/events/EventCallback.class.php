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

require_once(dirname(__FILE__).'/../../includes/core-minimal.inc.php');

abstract class EventCallback {
	private $is_active = false;

	/* action the callbacks does when the signal is emitted */
	abstract function run();
	abstract function getDescription();

	/* if isInternal returns true, there will not be an option to
	 * active/deactivate it */
	abstract function isInternal();

	/* returns an array of configuration items to display in the admin console */
	public function getConfigItems() {
		return NULL;
	}

	public final function __construct($ev_) {
		$this->ev = $ev_;
    }

	public final function getIsActive() {
		try {
			$prefs = Preferences::getInstance();
		} catch (Exception $e) {
			return false;
		}

		if (! is_object($prefs))
			return false;

		$buf = $prefs->get('events', 'active_callbacks');
		if ($buf && array_key_exists(get_class($this->ev), $buf)) {
			$tmp = $buf[get_class($this->ev)];
			if (in_array(get_class($this), $tmp)) {
				return true;
			}
		}
		return false;
	}
}

