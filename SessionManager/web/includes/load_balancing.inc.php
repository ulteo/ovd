<?php
/**
 * Copyright (C) 2008 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com>
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

class DecisionCriterion {
	public function __construct($server_) {
		$this->server = $server_;
	}
	//public function get();
	public function default_value() {
		return 0;
	}
}

class DecisionCriterion_ram extends DecisionCriterion {
	public function get() {
		if (!($this->server->hasAttribute('ram_used') && $this->server->hasAttribute('ram'))) {
			Logger::error('main','DecisionCriterion_ram::get error hasAttribute failed');
			return 0;
		}
		
		$ram_max = $this->server->getAttribute('ram');
		$ram_used = $this->server->getAttribute('ram_used');
		return 1.0 - (float)($ram_used)/(float)($ram_max);
	}
}

class DecisionCriterion_cpu extends DecisionCriterion {
	public function get() {
		if (!$this->server->hasAttribute('cpu_load')) {
			Logger::error('main','DecisionCriterion_cpu::get error hasAttribute failed');
			return 0;
		}
		return 1.0 -$this->server->getAttribute('cpu_load');
	}
}

class DecisionCriterion_session extends DecisionCriterion {
	public function get() {
		if (!($this->server->hasAttribute('ram_used') && $this->server->hasAttribute('nb_sessions'))) {
			Logger::error('main','DecisionCriterion_session::get error hasAttribute failed');
			return 0;
		}
		
		$nbsessions_max = $this->server->getAttribute('nb_sessions');
		$nbsessions_used = $this->server->getNbAvailableSessions();
		return (float)($nbsessions_used)/(float)($nbsessions_max);
	}
	public function default_value() {
		return 100;
	}
}

class DecisionCriterion_random extends DecisionCriterion {
	public function get() {
		return (float)(rand(0, 100)) / 100.0;
	}
	public function default_value() {
		return 20;
	}
}
