<?php
/**
 * Copyright (C) 2010 Ulteo SAS
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

class SessionManagement_microsoft extends SessionManagement {
	public static function getUserDB() {
		return array('activedirectory');
	}

	/* Module methods */
	public static function configuration() {
		return array();
	}

	public static function prefsIsValid($prefs_) {
		return true;
	}

	public static function prettyName() {
		return _('Microsoft');
	}

	public static function isDefault() {
		return false;
	}

	public static function init($prefs_) {
		return true;
	}

	public static function enable() {
		return true;
	}
}
