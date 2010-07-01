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

class Task_install extends Task {
	public $type = 'install';

	public $applications = NULL;

	public function __construct($task_id_, $server_, $applications_) {
		Logger::debug('main', 'Starting TASK_remove::__construct for task '.$task_id_);

		parent::__construct($task_id_, $server_);
		$this->applications = $applications_;
	}
	
	public function getRequest() {
		return 'install';
	}

	public function getPackages() {
		$packages = array();
		foreach ($this->applications as $app) {
			if (! in_array($app->getAttribute('package'), $packages))
				$packages []= $app->getAttribute('package');
		}

		return $packages;
	}
}
