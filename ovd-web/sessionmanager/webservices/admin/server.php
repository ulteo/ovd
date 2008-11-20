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
require_once(dirname(__FILE__).'/../../admin/includes/core.inc.php');

function updateserverinfo($fqdn_, $type_=NULL, $version_=NULL, $apps_xml_){
	$serv = new Server_admin($fqdn_,$type_,$version_);
	if ($type_ == NULL or $version_ == NULL)
		$serv->fromDB($fqdn_);
		
	if ($serv->isOK()){
		if ($serv->onDB() == true)
			if ($serv->register($apps_xml_))
					return 'OK';
				else
					return '(ERR#005C) register fail';
		else {
			if ($serv->insertDB())
	 			if ($serv->register($apps_xml_))
					return 'OK';
				else
					return '(ERR#005B) register fail';
		}
	}
	else
		return '(ERR#005A) register fail';
}

if (isset($_POST['action']) AND ($_POST['action'] == 'register') AND isset($_POST['fqdn']) AND  isset($_POST['type']) AND isset($_POST['version']) AND $_FILES['xml'] ){
	$apps_xml = file_get_contents($_FILES['xml']['tmp_name']);
	echo updateserverinfo($_POST['fqdn'],$_POST['type'],$_POST['version'],$apps_xml);
	die();
}

if (isset($_POST['action']) AND ($_POST['action'] == 'avalaibleapplication') AND isset($_POST['fqdn'])){
	$apps_xml =  query_url('http://'.$_POST['fqdn'].'/webservices/get_available_applications.php');
	echo updateserverinfo($_POST['fqdn'],NULL,NULL,$apps_xml);
	die();
}
