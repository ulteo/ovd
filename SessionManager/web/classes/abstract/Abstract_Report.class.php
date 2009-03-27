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

require_once(dirname(__FILE__).'/../../includes/core.inc.php');

class Abstract_Report {
	static $TYPE_SERVER = 0;
	static $TYPE_APPLICATION = 1;

	public static function init($prefs_) {
		Logger::debug('main', 'Starting Abstract_Report::init');

		$mysql_conf = $prefs_->get('general', 'mysql');
		$SQL = MySQL::newInstance($mysql_conf['host'], $mysql_conf['user'],
		                          $mysql_conf['password'], $mysql_conf['database']);

		$ret = $SQL->DoQuery(
		'CREATE TABLE IF NOT EXISTS @1 (
		 @2 VARCHAR(255) NOT NULL,
		 @3 VARCHAR(255) NOT NULL,
		 @4 TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
		 @5 FLOAT NOT NULL,
		 @6 FLOAT NOT NULL,
		 @7 LONGTEXT NOT NULL
		)', $mysql_conf['prefix'].'servers_history', 'fqdn', 'external_name',
		    'timestamp', 'cpu', 'ram', 'data');

		if (! $ret) {
			Logger::error('main', 'Unable to create MySQL table \''.$mysql_conf['prefix'].'servers_history\'');
			return false;
		}

		$ret = $SQL->DoQuery(
        'CREATE TABLE IF NOT EXISTS @1 (
		 @2 INT(16) NOT NULL auto_increment,
		 @3 TIMESTAMP NOT NULL default CURRENT_TIMESTAMP,
		 @4 TIMESTAMP NULL default NULL,
		 @5 VARCHAR(16) default NULL,
		 @6 VARCHAR(255) NOT NULL,
		 @7 VARCHAR(255) NOT NULL,
		 @8 LONGTEXT NOT NULL,
		 UNIQUE KEY `id` (`id`)
		)', $mysql_conf['prefix'].'sessions_history', 'id', 'start_stamp',
		    'stop_stamp', 'stop_why', 'user', 'server', 'data');

		if (! $ret) {
			Logger::error('main', 'Unable to create MySQL table \''.$mysql_conf['prefix'].'sessions_history\'');
			return false;
		}

		return true;
	}
}
