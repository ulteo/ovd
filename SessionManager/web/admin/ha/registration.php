<?php
/**
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Arnaud LEGRAND <arnaud@ulteo.com> 2010
 * Author Samuel BOVEE <samuel@ulteo.com> 2010
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
require_once(dirname(__FILE__).'/../includes/page_template.php');
require_once(dirname(__FILE__).'/classes/ShellExec.class.php');


if (! checkAuthorization('viewConfiguration'))
	redirect('../index.php');

function set_response_xml($id,$msg) {
	header('Content-Type: text/xml; charset=utf-8');
	$dom = new DomDocument('1.0', 'utf-8');
	$response_node = $dom->createElement('response');
	$dom->appendChild($response_node);
	$response_node->setAttribute('id', $id);
	$response_node->setAttribute('message', $msg);
	echo $dom->saveXML();
}

function secure_insert($SQL, $table,$hostname, $fqdn) {
	$query = 'DELETE FROM `'.$table.'` WHERE register="no" AND (hostname LIKE "'.$hostname.'" OR address LIKE "'.$fqdn.'")';
	$ret = $SQL->DoQuery($query);
	$query = 'SELECT * FROM `'.$table.'` WHERE hostname LIKE "'.$hostname.'" OR address LIKE "'.$fqdn.'"';
	$ret = $SQL->DoQuery($query);
	$rows = $SQL->FetchAllResults($res);
	foreach ($rows as $row) {
		if ($row["register"] == "yes"){
			set_response_xml(4,_("Host has been already registered!"));
			return false;
		}
	}
	$query = 'INSERT INTO `'.$table.'` VALUES (NULL,"'.$hostname.'","'.$fqdn.'",NULL,"no")';
	$ret = $SQL->DoQuery($query);
	$nb = $SQL->NumRows();
	if ($nb) return true;
	return false;
}

$SQL = SQL::getInstance();
$prefs = Preferences::getInstance();
$sql_conf = $prefs->get('general', 'sql');
$table = $sql_conf['prefix'].'ha';	
$action	= $_POST["action"];
$hostname = $_POST["hostname"];
$id_host = $_POST["id_host"];
$passwd	= $_POST["passwd"];
$client_ip = $_SERVER['REMOTE_ADDR'];
unset($_POST["action"]);
unset($_POST["hostname"]);

if (isset($action)) {
	switch ($action) {
		case "register":
			if (isset($hostname) && isset($client_ip) &&
				secure_insert($SQL,$table,$hostname,$client_ip)) {
				echo "ok";
			} else {
				echo "error";
			}
			break;
		case "enable":
			if (isset($hostname) && isset($client_ip) && isset($passwd)) {
				Logger::warning('ha', "Configuration files has been written!");
				set_response_xml(0,_("Host activation has been done successfully!"));
				$ret=ShellExec::exec_shell_cmd("register",$client_ip,$hostname,$passwd);
			} else {
				set_response_xml(1,_("An error occured in POST request activation!"));
			}
			break;
		case "disable":
			$ret=ShellExec::exec_shell_cmd("unregister","0","0","0");
			set_response_xml(0,_("Deleting host has been done successfully!"));
			break;
		default:
			set_response_xml(1,_("Request is not well formatted!"));
			break;
	}
} else {
    set_response_xml(1,_("Request is not well formatted!"));
}
die();
