<?php
/**
 * Copyright (C) 2014 Ulteo SAS
 * http://www.ulteo.com
 * Author David PHAM-VAN <d.pham-van@ulteo.com> 2014
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
require_once(dirname(__FILE__).'/../includes/webservices.inc.php');


$dom = new DomDocument('1.0', 'utf-8');
$root = $dom->createElement('auth');
$dom->appendChild($root);
$prefs = Preferences::getInstance();
if ($prefs) {
	$userDB = UserDB::getInstance();
	$authMethods_enabled = $prefs->get('AuthMethod', 'enable');
	if (is_array($authMethods_enabled)) {
		foreach ($authMethods_enabled as $authMethod_name) {
			$authMethod_module = 'AuthMethod_'.$authMethod_name;
			$authMethod = new $authMethod_module($prefs, $userDB, null);
			$authMethodParams = $authMethod->getClientParameters();
			if (is_array($authMethodParams) && count($authMethodParams) > 0) {
				$snode = $dom->createElement($authMethod_name);
				$root->appendChild($snode);
				foreach($authMethodParams as $key => $value) {
					$node = $dom->createElement($key);
					$text_node = $dom->createTextNode($value);
					$node->appendChild($text_node);
					$snode->appendChild($node);
				}
			}
		}
	}
}

header('Content-Type: text/xml; charset=utf-8');
die($dom->saveXML());
