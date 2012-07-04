<?php
/**
 * Copyright (C) 2012 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com>
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

class ErrorManager {
	private static $instance = null;

	final public static function setInstance($instance_) {
		self::$instance = $instance_;
	}

	final public static function getInstance() {
		if (self::$instance === null) {
			self::$instance = new ErrorManager();
		}
		
		return self::$instance;
	}
	
	public function perform($error_=false, $file_=NULL, $line_=NULL, $display_=false) {
		$display_ = true; //always display the real error message instead of a generic one
		
		$file_ = substr(str_replace(SESSIONMANAGER_ROOT, '', $file_), 1);
		
		Logger::debug('main', 'die_error() called with message \''.$error_.'\' in '.$file_.':'.$line_);
		Logger::critical('main', $error_);
		
		header('Content-Type: text/xml; charset=utf-8');
		
		$dom = new DomDocument('1.0', 'utf-8');
		$node = $dom->createElement('error');
		$node->setAttribute('id', 0);
		if ($display_ === true)
			$node->setAttribute('message', $error_);
		else
			$node->setAttribute('message', 'The service is not available, please try again later');
		$dom->appendChild($node);
		
		echo $dom->saveXML();
		
		die();
	}
}
