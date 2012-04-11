<?php
/**
 * Copyright (C) 2009-2012 Ulteo SAS
 * http://www.ulteo.com
 * Author Gauvain Pocentek <gauvain@ulteo.com> 2009
 * Author Laurent CLOUET <laurent@ulteo.com> 2009-2011
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
 * Abstraction layer between the ReportSession instances and the SQL backend.
 */
class Abstract_ReportSession {
	static $TYPE_SERVER = 0;
	static $TYPE_APPLICATION = 1;
	
	const table = 'sessions_history';

	public static function init($prefs_) {
		Logger::debug('main', 'Starting Abstract_ReportSession::init');

		$sql_conf = $prefs_->get('general', 'sql');
		$SQL = SQL::newInstance($sql_conf);

		$sessions_history_table_structure = array(
			'id' => 'VARCHAR(255) NOT NULL', // same as session id,
			'start_stamp' => 'TIMESTAMP NOT NULL default CURRENT_TIMESTAMP',
			'stop_stamp' => 'TIMESTAMP NULL default NULL',
			'stop_why' => 'VARCHAR(16) default NULL',
			'user' => 'VARCHAR(255) NOT NULL',
			'server' => 'VARCHAR(255) NOT NULL',
			'data' => 'LONGTEXT NOT NULL');
		
		$ret = $SQL->buildTable($sql_conf['prefix'].self::table, $sessions_history_table_structure, array('id'));

		if (! $ret) {
			Logger::error('main', 'Unable to create SQL table \''.$sql_conf['prefix'].self::table.'\'');
			return false;
		}

		return true;
	}
	
	public static function load($id_) {
		Logger::debug('main', "Abstract_ReportSession::load($id_)");
		
		$SQL = SQL::getInstance();

		$SQL->DoQuery('SELECT * FROM @1 WHERE @2 = %3 LIMIT 1', $SQL->prefix.self::table, 'id', $id_);
		$total = $SQL->NumRows();

		if ($total == 0) {
			Logger::error('main', "Abstract_ReportSession::load($id_) failed: NumRows == 0");
			return false;
		}

		$row = $SQL->FetchResult();
		
		return self::generateFromRow($row);
	}
	
	
	private static function generateFromRow($row_) {
		if (! array_keys_exists_not_empty(array('id', 'user', 'server', 'start_stamp'), $row_))
			return null;
		
		$report = new SessionReportItem();
		$report->id = $row_['id'];
		$report->user = $row_['user'];
		$report->server = $row_['server'];
		$report->start_time = $row_['start_stamp'];
		if (array_key_exists('stop_stamp', $row_) and $row_['stop_stamp'] !== '')
			$report->stop_time = $row_['stop_why'];
		
		if (array_key_exists('stop_why', $row_) and $row_['stop_why'] !== '')
			$report->stop_why = $row_['stop_why'];
		
		return $report;
	}
	
	
	public static function exists($id_) {
		Logger::debug('main', "Abstract_ReportSession::exists($id_)");
		$SQL = SQL::getInstance();
		
		$SQL->DoQuery('SELECT 1 FROM @1 WHERE @2 = %3 LIMIT 1', $SQL->prefix.self::table, 'id', $id_);
		$total = $SQL->NumRows();
		return ($total == 1);
	}
	
	public static function create($report_) {
		Logger::debug('main', 'Abstract_ReportSession::create');
		
		if (self::exists($report_->getID())) {
			Logger::error('main', 'Abstract_ReportSession::create(\''.$report_->getID().'\') report already exists');
			return false;
		}

		$sql = SQL::getInstance();
		$res = $sql->DoQuery('INSERT INTO @1 (@2,@3,@4,@5) VALUES (%6,%7,%8,%9)', $sql->prefix.self::table, 'id', 'user', 'server', 'data',  $report_->getID(), $report_->user, $report_->server, '');
		return $res;
	}
	
	public static function update($report_) {
		Logger::debug('main', "Abstract_ReportSession::update");
		
		$SQL = SQL::getInstance();
		$report = self::load($report_->getID());
		if (! is_object($report)) {
			Logger::debug('main', "Abstract_ReportSession::updateSession failed to load report ".$report_->getID());
			return false;
		}
		
		$ret = $SQL->DoQuery('UPDATE @1 SET @2=%3,@4=%5,@6=%7 WHERE @8 = %9 LIMIT 1', $SQL->prefix.self::table,
// 			'start_stamp',
// 			'stop_stamp',
			'stop_why',  $report_->stop_why,
			'user', $report_->user,
			'server', $report_->server,
// 			'data',
			'id', $report_->getID());
		return $ret;
	}
	
	
	public static function update_on_session_end($report_) {
		Logger::debug('main', "Abstract_ReportSession::update_on_session_end");
		
		$sql = SQL::getInstance();
		$report = Abstract_ReportSession::load($report_->getID());
		if (! is_object($report)) {
			Logger::debug('main', "Abstract_ReportSession::update_on_session_end failed to load report ".$report_->getID());
			return false;
		}
		
		$ret = $sql->DoQuery('UPDATE @1 SET @2=NOW(), @3=%4 WHERE @5=%6 LIMIT 1', $sql->prefix.self::table,
			'stop_stamp',
			'data',  $report_->toXml(),
			'id', $report_->getID());
		
		return $ret;
	}
	
	
	public static function load_partial($from_ = null, $to_ = null, $user_login_ = null, $limit_ = 50) {
		$extra = array();
		if ($from_ != null && $to_ != null)
			$extra[]= '@2>=%3';
		if ($to_ != null)
			$extra[]= '@4<=%5';
			
		if ($user_login_ != null)
			$extra[]= '@6=%7';
		
		$query = 'SELECT * ';
		$query.= 'FROM @1 ';
		if (count($extra) > 0) {
			$query.= 'WHERE';
			$query.= implode(" AND ", $extra).' ';
		}
		$query.= 'ORDER BY @2 DESC LIMIT '.$limit_.';';
		
		$sql = SQL::getInstance();
		$res = $sql->DoQuery($query, $sql->prefix.self::table, 
					'start_stamp', (is_null($from_)?null:date('c', $from_)),
					'stop_stamp',  (is_null($to_)?null:date('c', $to_)),
					'user', (is_null($user_login_)?null:$user_login_));
		
		
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
	
	
	public static function load_by_start_time_range($t0_, $t1_) {
		$sql = SQL::getInstance();
		$res = $sql->DoQuery('SELECT * FROM @1 WHERE @2 BETWEEN %3 AND %4;',
					$sql->prefix.self::table, 'start_stamp', date('c', $t0_), date('c', $t1_));
		
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
	
	
	public static function load_by_start_and_stop_time_range($t0_, $t1_, $server_ = null) {
		if ($server_ !== NULL)
			$server_part = 'AND server="'.$server_.'"';
		else
			$server_part = '';
		
		$sql = SQL::getInstance();
		$res = $sql->DoQuery('SELECT * FROM @1 WHERE @2 BETWEEN %3 AND %4 '.$server_part.' AND ( @5 <= %4 OR ( @6 IS NULL AND id IN (SELECT id FROM @7)))',
							$sql->prefix.self::table, 'start_stamp', date('c', $t0_), date('c', $t1_), 
							'stop_stamp', 'stop_stamp', $sql->prefix.Abstract_Session::table);
		
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
	
	
	public static function get_end_status_for_server($t0_, $t1_, $fqdn_) {
		$sql = SQL::getInstance();
		$res = $sql->DoQuery('SELECT  @1, count(@1) AS @2 FROM @3 WHERE @4 = %5 AND @6 IS NOT NULL AND @7 BETWEEN %8 AND %9 GROUP BY @1', 'stop_why', 'nb', $sql->prefix.self::table, 'server', $fqdn_,  'stop_stamp','start_stamp', date('c', $t0_), date('c', $t1_));
		
		$stop_why = array();
		
		$rows = $sql->FetchAllResults();
		foreach ($rows as $row) {
			$why = $row['stop_why'];
			$nb = $row['nb'];
			
			if ($why == '' || is_null($why))
				$why = 'unknown';
			if ($nb != 0)
				$stop_why[$why] = $nb;
		}
		
		return $stop_why;
	}
 }