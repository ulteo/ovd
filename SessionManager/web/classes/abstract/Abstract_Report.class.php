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
        @2 int(10) NOT NULL,
        @3 varchar(255) NOT NULL,
        @4 int(16) NOT NULL,
        @5 int(16) NOT NULL,
        @6 int(8) NOT NULL,
        @7 int(8) NOT NULL,
        @8 int(10) NOT NULL,
        @9 int(4) NOT NULL,
        @10 int(10) NOT NULL
        )', $mysql_conf['prefix'].'servers_report', 'date', 'fqdn', 'down_time', 'maintenance_time',
		    'sessions_count', 'max_connections', 'max_connections_when', 'max_ram', 'max_ram_when');

        if (! $ret) {
            Logger::error('main', 'Unable to create MySQL table \''.$mysql_conf['prefix'].'servers_report\'');
            return false;
        }

        Logger::debug('main', 'MySQL table \''.$mysql_conf['prefix'].'servers_report\' created');

		$ret = $SQL->DoQuery(
		'CREATE TABLE IF NOT EXISTS @1 (
		@2 int(10) NOT NULL,
		@3 varchar(255) NOT NULL,
		@4 int(8) NOT NULL,
		@5 int(8),
		@6 int(8)
		)', $mysql_conf['prefix'].'applications_report', 'date', 'fqdn', 'app_id',
		    'use_count', 'max_use');

		if (! $ret) {
			Logger::error('main', 'Unable to create MySQL table \''.$mysql_conf['prefix'].'applications_report\'');
            return false;
        }

		return true;
	}
}
