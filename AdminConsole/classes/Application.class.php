<?php
/**
 * Copyright (C) 2012 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2012
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

class Application extends AbstractObject {
	protected function required_attributes() {
		return array('id', 'name', 'description', 'type', 'executable_path', 'package', 'published', 'desktopfile');
	}
	
	public function isOrphan() {
		return (! $this->hasAttribute('servers') || count($this->getAttribute('servers')) == 0);
	}
	
	public function getMimeTypes() {
		if (! $this->hasAttribute('mimetypes')) {
			return array();
		}
		
		return $this->getAttribute('mimetypes');
	}
	
	public function getAttributesList() {
		return array_keys($this->attributes);
	}
}
