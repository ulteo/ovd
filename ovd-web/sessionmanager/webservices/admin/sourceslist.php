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

function send_sourceslist($fqdn_){
	$l = new Liaison(NULL,$fqdn_);
	$l->table = SOURCES_LIST_TABLE;
	$mirrors = $l->elements();
	$temp = "";
	foreach ($mirrors as $mirror){
		$temp .= $mirror;
		$temp .= "\n";
	}
	$tmpname = tempnam("/tmp", "sourceslist_");
	file_put_contents($tmpname,$temp);
	$data = array('sourceslist' => '@'.$tmpname);
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $fqdn_.'/webservices/put_sourceslist.php');
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_VERBOSE,0);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	$response = curl_exec($ch);
	if (curl_getinfo($ch,CURLINFO_HTTP_CODE) == 200)
		$ret = true;
	else 
		$ret = false;
	curl_close($ch);
	unlink($tmpname);
	return $ret;
}

if (isset($_POST['action']) AND ($_POST['action'] == "send" ) AND isset($_POST['fqdn'])){
	if (send_sourceslist($_POST['fqdn']))
		echo 'OK';
	else
		echo '(ERR#049) sourceslist send fail';
}
else if (isset($_POST['action']) AND (($_POST['action'] == "add") OR ($_POST['action'] == "del" ))AND isset($_POST['mirror'])AND isset($_POST['fqdn'])){
	$l = new Liaison($_POST['mirror'],$_POST['fqdn']);
	$l->table = SOURCES_LIST_TABLE;
	if ($_POST['action'] == "add"){
		if ($l->insertDB() === true){
			echo 'OK';
		}
		else
			echo '(ERR#041) sourceslist add fail';
	}
	else if ($_POST['action'] == "del"){
		if ($l->removeDB() === true){
			echo 'OK';
			}
		else
			echo '(ERR#042) sourceslist add fail';
	}
	else {
		echo '(ERR#043) sourceslist';
	}
}
else if (isset($_POST['action']) AND ($_POST['action'] == "duplicate" ) AND isset($_POST['ori'])AND isset($_POST['dest'])){
	$l1 = new Liaison(NULL,$_POST['dest']);
	$l1->table = SOURCES_LIST_TABLE;
	$elements = $l1->elements();
	if (is_null($elements) === false) {
		foreach ($elements as $ele){
			$l2 = new Liaison($ele,$_POST['dest']);
			$l2->table = SOURCES_LIST_TABLE;
			if ($l2->removeDB() === false){
				echo '(ERR#048) sourceslist duplicate fail';
			}
		}
	}
	$l3 = new Liaison(NULL,$_POST['ori']);
	$l3->table = SOURCES_LIST_TABLE;
	$elements = $l3->elements();
	if (is_null($elements) === false) {
		foreach ($elements as $ele){
			$l4 = new Liaison($ele,$_POST['dest']);
			$l4->table = SOURCES_LIST_TABLE;
			if ($l4->insertDB() === false){
				echo '(ERR#047) sourceslist duplicate fail';
				die();
			}
		}
	}
	echo 'OK';
}
