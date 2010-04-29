<?php
/**
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2010
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

/* Tail a file as GNU Tail */
class FileTailer {
	private $filename;
	private $fd;
	private $buffer_line;
	private $buffer_lines;

	private $size;
	private $pos;
	private $bufsize;
	private $end;

	public function __construct($filename_) {
		$this->filename = $filename_;
		$this->fd = false;
		$this->buffer_line = '';
		$this->buffer_lines = array();

		$this->pos = null;
		$this->bufsize = 2048;
		$this->end = false;
	}

	public function __destruct() {
		if ($this->fd != null)
			@fclose($this->fd);
	}

	private function init() {
		$this->fd = @fopen($this->filename, "r");
		if ($this->fd === false)
			return false;

		fseek($this->fd, -1, SEEK_END);
		$this->size = ftell($this->fd) + 1;

		$last = fgetc($this->fd);
		if ($last == "\n")
			$this->size--;

		if ($this->size < $this->bufsize) {
			$this->pos = 0;
			$this->bufsize = $this->size;
		} else
			$this->pos = $this->size - $this->bufsize;

		return true;
	}

	public function hasLines() {
		return (count($this->buffer_lines) > 0 || strlen($this->buffer_line)>0 || $this->end === false);
	}

	public function tail($nb_lines_ = 10) {
		if ($this->fd == null) {
			$ret = $this->init();
			if ($ret === false)
				return array();
		}

		$ret = array();

		while ($nb_lines_ > 0 && count($this->buffer_lines)>0) {
			$buf = array_pop($this->buffer_lines);
			array_unshift($ret, $buf);
			$nb_lines_ --;
		}

		while ($nb_lines_ > 0 && ! $this->end) {
			fseek($this->fd, $this->pos);
			$buf = fread($this->fd, $this->bufsize).$this->buffer_line;

			$lines = explode("\n", $buf);
			$this->buffer_line = array_shift($lines);

			while ($nb_lines_ > 0 && count($lines)>0) {
				$buf = array_pop($lines);
				array_unshift($ret, $buf);
				$nb_lines_ --;
			}

			$this->buffer_lines = $lines;

			if ($this->pos == 0)
				$this->end = true;
			elseif ($this->pos < $this->bufsize) {
				$this->bufsize = $this->pos;
				$this->pos = 0;
			} else
				$this->pos-= $this->bufsize;
		}

		if ($nb_lines_ > 0 && $this->end) {
			array_unshift($ret, $this->buffer_line);
			$this->buffer_line = '';
		}

		return $ret;
	}

	public function tail_str($nb_lines_) {
		return implode("\n", $this->tail($nb_lines_));
	}
}
