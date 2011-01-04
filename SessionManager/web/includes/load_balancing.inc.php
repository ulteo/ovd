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
	public function applyOnRole($role_) {
		return false;
	}
}

class DecisionCriterion_ram extends DecisionCriterion {
	public function get() {
		if (!($this->server->hasAttribute('ram_used') && $this->server->hasAttribute('ram_total'))) {
			Logger::error('main','DecisionCriterion_ram::get error hasAttribute failed');
			return 0;
		}

		$ram_total = $this->server->getAttribute('ram_total');
		$ram_used = $this->server->getAttribute('ram_used');
		if ((float)($ram_total) == 0.0)
			return 0;
		else
			return 1.0 - (float)($ram_used)/(float)($ram_total);
	}
	public function applyOnRole($role_) {
		// it can be applied on any role
		return true;
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
	public function applyOnRole($role_) {
		// it can be applied on any role
		return true;
	}
}

class DecisionCriterion_session extends DecisionCriterion {
	public function get() {
		if (! $this->server->hasAttribute('max_sessions')) {
			Logger::error('main','DecisionCriterion_session::get error hasAttribute failed');
			return 0;
		}

		$nbsessions_max = $this->server->getNbMaxSessions();
		$nbsessions_used = $this->server->getNbUsedSessions();
		if ((float)($nbsessions_max) == 0.0)
			return 0;
		else
			return 1.0 - (float)($nbsessions_used)/(float)($nbsessions_max);
	}
	public function default_value() {
		return 100;
	}
	public function applyOnRole($role_) {
		return (Server::SERVER_ROLE_APS === $role_);
	}
}

class DecisionCriterion_random extends DecisionCriterion {
	public function get() {
		return (float)(rand(0, 100)) / 100.0;
	}
	public function default_value() {
		return 20;
	}
	public function applyOnRole($role_) {
		// it can be applied on any role
		return true;
	}
}

class DecisionCriterion_disk extends DecisionCriterion {
	public function get() {
		if ($this->server->hasAttribute('disk_total') == false || $this->server->hasAttribute('disk_free') == false) {
			return 0;
		}
		
		$max = (float)($this->server->getAttribute('disk_total'));
		$free = (float)($this->server->getAttribute('disk_free'));
		if ($max == 0.0) {
			return 0;
		}
		else {
			return $free / $max;
		}
	}
	public function default_value() {
		return 60;
	}
	public function applyOnRole($role_) {
		// it can be applied on any role
		return (Server::SERVER_ROLE_FS === $role_);
	}
}

class DecisionCriterion_networkfolder extends DecisionCriterion {
	public function get() {
		$used = 0;
		$total = 0;
		
		if (Preferences::moduleIsEnabled('ProfileDB')) {
			$profiledb = ProfileDB::getInstance();
			$total += $profiledb->count();
			$used += $profiledb->countOnServer($this->server->fqdn);
		}
		
		if (Preferences::moduleIsEnabled('SharedFolderDB')) {
			$sharedfolderdb = SharedFolderDB::getInstance();
			$total += $sharedfolderdb->count();
			$used += $sharedfolderdb->countOnServer($this->server->fqdn);
		}
		
		if ($total == 0) {
			return 0;
		}
		else {
			return 1.0 - ((float)($used) / (float)($total));
		}
	}
	public function default_value() {
		return 60;
	}
	public function applyOnRole($role_) {
		// it can be applied on any role
		return (Server::SERVER_ROLE_FS === $role_);
	}
}
