<?php
/**
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2010
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

class Abstract_Task {
	public static function init($prefs_) {
		Logger::debug('main', 'Starting Abstract_Task::init');

		$sql_conf = $prefs_->get('general', 'sql');
		$SQL = SQL::newInstance($sql_conf);

		$tasks_table_structure = array(
			'id'				=>	'varchar(32)',
			'type'				=>	'varchar(32)',
			'job_id'			=>	'varchar(32)',
			'server'			=>	'varchar(255)',
			'status'			=>	'varchar(64)',
			't_begin'			=>	'int(10)',
			't_end'				=>	'int(10)',
			'applications_line'	=>	'varchar(255)',
			'applications'		=>	'text'
		);

		$ret = $SQL->buildTable($sql_conf['prefix'].'tasks', $tasks_table_structure, array('id'));

		if (! $ret) {
			Logger::error('main', 'Unable to create MySQL table \''.$sql_conf['prefix'].'tasks\'');
			return false;
		}

		Logger::debug('main', 'MySQL table \''.$sql_conf['prefix'].'tasks\' created');
		return true;
	}

	public static function exists($id_) {
		Logger::debug('main', 'Starting Abstract_Task::exists for \''.$id_.'\'');

		$SQL = SQL::getInstance();

		$SQL->DoQuery('SELECT 1 FROM @1 WHERE @2 = %3 LIMIT 1', $SQL->prefix.'tasks', 'id', $id_);
		$total = $SQL->NumRows();

		if ($total == 0)
			return false;

		return true;
	}

	public static function load($id_) {
		Logger::debug('main', 'Starting Abstract_Task::load for \''.$id_.'\'');

		$SQL = SQL::getInstance();

		$SQL->DoQuery('SELECT * FROM @1 WHERE @2 = %3 LIMIT 1', $SQL->prefix.'tasks', 'id', $id_);
		$total = $SQL->NumRows();

		if ($total == 0) {
			Logger::error('main', "Abstract_Task::load($id_) failed: NumRows == 0");
			return false;
		}

		$row = $SQL->FetchResult();

		$buf = self::generateFromRow($row);

		return $buf;
	}

	public static function save($task_) {
		Logger::debug('main', 'Starting Abstract_Task::save for \''.$task_->id.'\'');

		$SQL = SQL::getInstance();

		$id = $task_->id;

		if (! Abstract_Task::exists($id)) {
			Logger::debug('main', "Abstract_Task::save($task_) task does NOT exist, we must create it");

			if (! Abstract_Task::create($task_)) {
				Logger::error('main', "Abstract_Task::save($task_) Abstract_Task::create failed");
				return false;
			}
		}

		$SQL->DoQuery('UPDATE @1 SET @2=%3,@4=%5,@6=%7,@8=%9,@10=%11,@12=%13,@14=%15,@16=%17 WHERE @18 = %19 LIMIT 1', $SQL->prefix.'tasks', 'type', $task_->type, 'job_id', $task_->job_id, 'server', $task_->server, 'status', $task_->status, 't_begin', $task_->t_begin, 't_end', $task_->t_end, 'applications_line', @$task_->applications_line, 'applications', serialize(@$task_->applications), 'id', $id);

		return true;
	}

	private static function create($task_) {
		Logger::debug('main', 'Starting Abstract_Task::create for \''.$task_->id.'\'');

		if (Abstract_Task::exists($task_->id)) {
			Logger::error('main', 'Abstract_Task::create(\''.$task_->id.'\') task already exists');
			return false;
		}

		$SQL = SQL::getInstance();
		$SQL->DoQuery('INSERT INTO @1 (@2) VALUES (%3)', $SQL->prefix.'tasks', 'id', $task_->id);

		return true;
	}

	public static function delete($id_) {
		Logger::debug('main', 'Starting Abstract_Task::delete for \''.$id_.'\'');

		$SQL = SQL::getInstance();

		$id = $id_;

		$SQL->DoQuery('SELECT 1 FROM @1 WHERE @2 = %3 LIMIT 1', $SQL->prefix.'tasks', 'id', $id);
		$total = $SQL->NumRows();

		if ($total == 0) {
			Logger::error('main', "Abstract_Task::delete($id_) task does not exist (NumRows == 0)");
			return false;
		}

		$SQL->DoQuery('DELETE FROM @1 WHERE @2 = %3 LIMIT 1', $SQL->prefix.'tasks', 'id', $id);

		return true;
	}

	private static function generateFromRow($row_) {
		foreach ($row_ as $k => $v)
			$$k = $v;

		switch ($type) {
			case 'install':
				$buf = new Task_install((string)$id, (string)$server, unserialize($applications));
				break;
			case 'install_from_line':
				$buf = new Task_install_from_line((string)$id, (string)$server, (string)$applications_line);
				break;
			case 'upgrade':
				$buf = new Task_upgrade((string)$id, (string)$server);
				break;
			case 'remove':
				$buf = new Task_remove((string)$id, (string)$server, unserialize($applications));
				break;
			case 'available':
				$buf = new Task_available_applications((string)$id, (string)$server);
				break;
		}
		$buf->type = (string)$type;
		$buf->job_id = (string)$job_id;
		$buf->server = (string)$server;
		$buf->status = (string)$status;
		$buf->t_begin = (int)$t_begin;
		$buf->t_end = (int)$t_end;
		$buf->applications_line = (string)$applications_line;
		$buf->applications = unserialize($applications);

		return $buf;
	}

	public static function load_all() {
		Logger::debug('main', 'Starting Abstract_Task::load_all');

		$SQL = SQL::getInstance();

		$SQL->DoQuery('SELECT * FROM @1', $SQL->prefix.'tasks');
		$rows = $SQL->FetchAllResults();

		$tasks = array();
		foreach ($rows as $row) {
			$task = self::generateFromRow($row);
			if (! is_object($task))
				continue;

			$tasks[] = $task;
		}

		return $tasks;
	}

	public function load_by_server($fqdn_) {
		Logger::debug('main', 'Starting Abstract_Task::load_from_server('.$fqdn_.')');

		$SQL = SQL::getInstance();

		$SQL->DoQuery('SELECT * FROM @1 WHERE @2 = %3', $SQL->prefix.'tasks', 'server', $fqdn_);
		$rows = $SQL->FetchAllResults();

		$tasks = array();
		foreach ($rows as $row) {
			$task = self::generateFromRow($row);
			if (! is_object($task))
				continue;

			$tasks[] = $task;
		}

		return $tasks;
	}
}
