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
require_once(dirname(__FILE__).'/../../includes/core.inc.php');

class Tasks_Manager {
	public $tasks = array();

	public function load_all() {
		$this->tasks = Abstract_Task::load_all();
	}

	public function load_from_server($fqdn_) {
		$this->tasks = Abstract_Task::load_by_server($fqdn_);
	}

	public function load_from_application($app_id_) {
		$this->tasks = Abstract_Task::load_all();
		$tasks = array();
		foreach ($this->tasks as $task) {
			if (get_class($task) == 'Task_install_from_line')
				continue;

			if (! isset($task->applications))
				continue;

			if (! is_array($task->applications))
 				continue;

			$found = false;
			foreach ($task->applications as $application) {
				if ($application->getAttribute('id') == $app_id_) {
					$found = true;
					break;
				}
			}
			if ($found)
				$tasks[]= $task;
		}
		$this->tasks = $tasks;
	}

	public function add($task_) {
		$task_->id = gen_string(8);
		$task_->init();

		$this->tasks []= $task_;
		$this->save($task_);
	}

	public function save($task_) {
		Abstract_Task::save($task_);
	}

	public function save_all() {
		foreach ($this->tasks as $task)
			$this->save($task);
	}

	public static function remove($task_id_) {
		Abstract_Task::delete($task_id_);
	}

	public function refresh_all() {
		foreach ($this->tasks as $task) {
			/*if ($task->succeed())
				continue;
			if ($task->failed())
				continue;*/

			$task->refresh();
			Abstract_Task::save($task);
		}
	}
}
