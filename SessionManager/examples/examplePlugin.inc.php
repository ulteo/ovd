<?php
/**
 * Copyright (C) 2013 Ulteo SAS
 * http://www.ulteo.com
 * Author David PHAM-VAN <d.pham-van@ulteo.com> 2013
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

class examplePlugin extends Plugin {

	/**
	 * Mount shared folders at the startup of the session
	 * 
	 * this function is called on each server the user connects
	 * and returns an array containing the shares to be mounted.
	 * 
	 * @param Server $server
	 *   actual server
	 * 
	 * @return array
	 *   list of the shares to be mounted as a dictionary containing
	 *   - uri : cifs://host/share or webdav://host
	 *   - rid : resource identifier that can be used in OVD App Channel 
	 *           to open Application with file stored on this resource
	 *           format: [AZaz09-_]{1,32}
	 *   - name : the name of the share seen by the user
	 *   - login : the login part of the authentication credentials
	 *   - password : the password part of the authentication credentials
	 */
	function getSharedFolders($server) {
		$ret = array();

		$user = $_POST['login'];
		$passwd = $_POST['password'];

		$ret[] = array(
			'name' => 'Public',
			'rid' => 'Public',
			'uri' => 'webdav://nas.example.com/Public',
		);

		$ret[] = array(
			'name' => 'Home ('.$user.')',
			'rid' => 'Home',
			'uri' => 'cifs://nas.example.com/'.$user,
			'login' => $user,
			'password' => $passwd
		);

		return $ret;
	}
}
