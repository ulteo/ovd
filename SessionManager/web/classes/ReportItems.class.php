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

class ReportCountItem {
	private $count;
	private $time;

	public function __construct($initvalue_ = 0) {
		$this->count = $initvalue_;
	}

	public function inc($nb_ = 1) {
		$this->count += $nb_;
		$this->time = time();
	}

	public function dec($nb = 1) {
		$this->count -= $nb_;
		$this->time = time();
	}

	public function get() {
		return $this->count;
	}

	public function getLastUpdate() {
		return $this->time;
	}
}

/* handle min/max items */
abstract class ReportMinMaxItem {
	public $current;
	public $time;

	public function __construct($initvalue_ = 0) {
		$this->current = $initvalue_;
		$this->time = time();
	}

	abstract public function set($value_);

	public function get() {
		return $this->current;
	}

	public function getLastUpdate() {
		return $this->time;
	}
}

class ReportMinItem extends ReportMinMaxItem {
	public function set($value_) {
		if ($value_ < $this->current) {
			$this->current = $value_;
			$this->time = time();
		}
		return $this->current;
	}
}

class ReportMaxItem extends ReportMinMaxItem {
	public function set($value_) {
		if ($value_ > $this->current) {
			$this->current = $value_;
			$this->time = time();
		}
		return $this->current;
	}
}

/* time intervals */
class ReportIntervalItem {
	public $start;
	public $end;
	public $elapsed;

	public function __construct($start_ = NULL) {
		if ($start_ == NULL)
			$start_ = time();
		$this->start = $start_;
	}

	public function end($end_ = NULL) {
		if ($end_ == NULL)
			$end_ = time();

		if ($end_ < $this->start)
			return false;

		$this->end = $end_;

		/* return elapsed time */
		$this->elapsed = $this->end - $this->start;
	}

	public function isDone() {
		if (isset ($this->elapsed) && $this->elapsed >= 0)
			return $this->elapsed;
		return false;
	}
}

class ReportRunningItem {
	public $running;
	public $start;
	public $elapsed;

	public function __construct() {
		$this->running = true;
		$this->start = time();
	}

	public function stop() {
		$this->running = false;
		$this->end = time();
		$this->elapsed = $this->end - $this->start;
	}

	public function isDone() {
		if (isset ($this->elapsed) && $this->elapsed >= 0)
			return $this->elapsed;
		return false;
	}
}

