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

require_once(dirname(__FILE__).'/../includes/core-minimal.inc.php');

class Event {
	private $callbacks_dir;
	protected $known_callbacks = array(); /* list of usable callbacks */
	protected $callbacks = array();       /* callbacks actually used */

	/* common attributes */
	public $time; /* when is the signal emitted */

	public final function __construct () {
		$this->callbacks_dir = CALLBACKS_DIR.'/'.get_class($this);

		/* do not fail here, the event can exist but do nothing */
		if (! is_dir ($this->callbacks_dir))
			return

		Logger::debug('main', 'Event::__construct callbacks_dir = '.
		              $this->callbacks_dir);
		$list = glob($this->callbacks_dir . "/*.class.php");
		Logger::debug('main', 'Event::__construct creating event '.get_class($this));

		foreach ($list as $file) {
			$this->known_callbacks[] = preg_replace (
				'/\.class\.php$/', '', basename($file));
			Logger::debug('main',
			              'Event::__construct adding callback from file '.$file);
		}
	}

	/*
	 * TODO: we should not register callbacks manually but define what is used
	 * in a config item, and load them automatically
	 */
	public final function register ($callback_) {
		if (! in_array ($callback_, $this->callbacks)
			&& in_array ($callback_, $this->known_callbacks))
		{
			Logger::debug('main', 'Event::register registering callback '.$callback_);
			$this->callbacks[] = $callback_;
		}
	}

	public final function unregister ($callback_) {
		/* is there a better way to do this in php ? */
		foreach ($this->callbacks as $key => $value) {
			if ($callback_ == $value)
				array_slice ($this->callbacks, $key);
		}
	}

	public final function emit () {
		$this->time = time ();
		foreach ($this->callbacks as $callback) {
			require_once ($this->callbacks_dir."/".$callback.".class.php");
			$callback_name = $callback.'Callback';

			try {
				$c = new $callback_name ($this);
				Logger::debug('main', 'Event::emit Running callback '.$callback_name);
				if ($c->run ($this) === false) {
					break;
				}
			} catch (Exception $e) {
				Logger::debug('main', 'Event::emit Failed to run callback '.
				              $callback_name);
			}
		}
	}

	public final function setAttribute ($attrib_, $value_) {
		$this->$attrib_ = $value_;
	}

	public final function setAttributes ($array_) {
		foreach ($array_ as $key => $value) {
			$this->setAttribute ($key, $value);
		}
	}
}

