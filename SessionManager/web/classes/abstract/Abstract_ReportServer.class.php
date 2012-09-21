<?php
/**
 * Copyright (C) 2009-2012 Ulteo SAS
 * http://www.ulteo.com
 * Author Gauvain Pocentek <gauvain@ulteo.com> 2009
 * Author Laurent CLOUET <laurent@ulteo.com> 2009-2010
 * Author Jocelyn DELALANDE <j.delalande@ulteo.com> 2012
 * Author Julien LANGLOIS <julien@ulteo.com> 2012
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

/**
 * Abstraction layer between the ReportServer instances and the SQL backend.
 */
class Abstract_ReportServer {
	static $TYPE_SERVER = 0;
	static $TYPE_APPLICATION = 1;
	
	const table = 'servers_history';
	
	public static function init($prefs_) {
		Logger::debug('main', 'Starting Abstract_ReportServer::init');

		$sql_conf = $prefs_->get('general', 'sql');
		$SQL = SQL::newInstance($sql_conf);

		$servers_history_table_structure = array(
			'id' => 'VARCHAR(255) NOT NULL',
			'fqdn' => 'VARCHAR(255) NOT NULL',
			'external_name' => 'VARCHAR(255) NOT NULL',
			'timestamp' => 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
			'cpu' => 'FLOAT NOT NULL',
			'ram' => 'FLOAT NOT NULL',
			'data' => 'LONGTEXT NOT NULL');
		
		$ret = $SQL->buildTable(self::table, $servers_history_table_structure, array());

		if (! $ret) {
			Logger::error('main', 'Unable to create SQL table \''.self::table.'\'');
			return false;
		}

		return true;
	}
	
	
	public static function save($report_) {
		$sql = SQL::getInstance();
		$res = $sql->DoQuery(
			'INSERT INTO #1 (@2,@3,@4,@5,@6,@7) VALUES (%8,%9,%10,%11,%12,%13)',
			self::table,'id','fqdn','external_name','cpu','ram','data',
			$report_->getID(), $report_->getFQDN(), $report_->getExternalName(), $report_->getCPU(), $report_->getRAM(), $report_->getData());
		return ($res !== false);
	}
	
	
	public static function load_partial($t0_, $t1_) {
		$sql = SQL::getInstance();
		$sql->DoQuery('SELECT * FROM #1 WHERE @2 BETWEEN %3 AND %4 ORDER BY @2 ASC;', self::table, 'timestamp', date('c', $t0_), date('c', $t1_));
		
		$rows = $sql->FetchAllResults();
		
		$reports = array();
		foreach ($rows as $row) {
		
			$report = self::generateFromRow($row);
			if (! is_object($report))
				continue;
			
			$reports[] = $report;
		}
		
		return $reports;
	}
	
	
	private static function generateFromRow($row_) {
		if (! array_keys_exists_not_empty(array('id', 'fqdn', 'timestamp', 'external_name', 'cpu', 'ram', 'data'), $row_))
			return null;
		
		$report = new ServerReportItem($row_['timestamp'], $row_['id'], $row_['fqdn'], $row_['external_name'], $row_['cpu'], $row_['ram'], $row_['data']);
		return $report;
	}
}
