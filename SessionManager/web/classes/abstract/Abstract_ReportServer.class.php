<?php
/**
 * Copyright (C) 2009-2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Gauvain Pocentek <gauvain@ulteo.com> 2009
 * Author Laurent CLOUET <laurent@ulteo.com> 2009-2010
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

class Abstract_ReportServer {
	static $TYPE_SERVER = 0;
	static $TYPE_APPLICATION = 1;
	
	public static function init($prefs_) {
		Logger::debug('main', 'Starting Abstract_ReportServer::init');

		$sql_conf = $prefs_->get('general', 'sql');
		$SQL = SQL::newInstance($sql_conf);

		$servers_history_table_structure = array(
			'fqdn' => 'VARCHAR(255) NOT NULL',
			'external_name' => 'VARCHAR(255) NOT NULL',
			'timestamp' => 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
			'cpu' => 'FLOAT NOT NULL',
			'ram' => 'FLOAT NOT NULL',
			'data' => 'LONGTEXT NOT NULL');
		
		$ret = $SQL->buildTable($sql_conf['prefix'].'servers_history', $servers_history_table_structure, array());

		if (! $ret) {
			Logger::error('main', 'Unable to create SQL table \''.$sql_conf['prefix'].'servers_history\'');
			return false;
		}

		return true;
	}
}
