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

require_once(dirname(__FILE__).'/../../includes/core.inc.php');

class Event {
	private $callbacks_dir;
	private $known_callbacks;     /* list of usable callbacks */
	private $callbacks = array(); /* callbacks actually used */

	/* common attributes */
	public $time; /* when is the signal emitted */

	public final function __construct($array_ = NULL) {
		$this->callbacks_dir = CALLBACKS_DIR.'/'.get_class($this);

		/* do not fail here, the event can exist but do nothing */
		if (! is_dir($this->callbacks_dir))
			return;

		$this->known_callbacks = $this->getAvailableCallbacks();

		/* get the list of callbacks we'll handle */
		$prefs = Preferences::getInstance();
		$cb_list = $prefs->get('events', get_class($this));
		if (($cb_list != NULL) && is_array ($cb_list)) {
			foreach ($cb_list as $cb) {
				Logger::info('main', 'CALLBACK: '.$cb);
				if (in_array($cb, $this->known_callbacks))
					$this->register($cb);
			}
		}

		/* set attributes if any */
		if ($array_ != NULL && is_array($array_))
			$this->setAttributes($array_);
	}

	public final function getAvailableCallbacks() {
		$ret = array();
		$fileslist = glob($this->callbacks_dir . "/*.class.php");

		foreach ($fileslist as $file)
			$ret[] = preg_replace (
				'/\.class\.php$/', '', basename($file));

		return $ret;
	}

	private final function register($callback_) {
		if (! in_array($callback_, $this->callbacks))
		{
			Logger::debug('main', 'Event::register registering callback '.$callback_);
			$this->callbacks[] = $callback_;
		}
	}

	protected final function unregister($callback_) {
		/* FIXME: is there a better way to do this in php ? */
		foreach ($this->callbacks as $key => $value) {
			if ($callback_ == $value)
				array_slice($this->callbacks, $key);
		}
	}

	public final function emit() {
		$this->time = time();
		foreach ($this->callbacks as $callback) {
			require_once($this->callbacks_dir."/".$callback.".class.php");
			$callback_name = $callback.'Callback';

			try {
				$c = new $callback_name($this);
				Logger::debug('main', 'Event::emit Running callback '.$callback_name);
				if ($c->run($this) === false) {
					break;
				}
			} catch(Exception $e) {
				Logger::debug('main', 'Event::emit Failed to run callback '.
				              $callback_name);
			}
		}
	}

	public final function setAttribute($attrib_, $value_) {
		$this->$attrib_ = $value_;
	}

	public final function setAttributes($array_) {
		foreach ($array_ as $key => $value) {
			$this->setAttribute ($key, $value);
		}
	}
}

