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
require_once(dirname(__FILE__).'/server.php');

if ( isset($_POST['fqdn'])  AND isset($_POST['action']) AND ($_POST['action'] == 'show' ) AND  isset($_POST['show'])   AND isset($_POST['job'])) {
	echo query_url('http://'.$_POST['fqdn'].'/webservices/apt-get.php?action=show&job='.$_POST['job'].'&show='.$_POST['show']);
	die();
}
else if ( isset($_POST['fqdn']) AND isset($_POST['action']) AND ($_POST['action'] != 'show') AND isset($_POST['app']) ){
	echo query_url('http://'.$_POST['fqdn'].'/webservices/apt-get.php?action=request&request='.urlencode($_POST['action'].' '.$_POST['app']));
	die();
}
else if ( isset($_GET['fqdn_dest']) AND isset($_GET['fqdn_ori'])AND isset($_GET['action']) AND ($_GET['action'] == 'duplicate') AND isset($_GET['param']) ){
	$serv_dest = new Server_admin();
	$serv_dest->fromDB($_GET['fqdn_dest']);
	$serv_ori = new Server_admin();
	$serv_ori->fromDB($_GET['fqdn_ori']);
	echo 'ori ';var_dump($serv_ori);echo '<br>';
	echo 'dest ';var_dump($serv_dest);echo '<br>';


	$prefs = Preferences::getInstance();
	if (! $prefs)
		die_error('get Preferences failed',__FILE__,__LINE__);
	$mods_enable = $prefs->get('general','module_enable');
	if (!in_array('ApplicationDB',$mods_enable)){
		die_error('Module ApplicationDB must be enabled',__FILE__,__LINE__);
	}
	$mod_app_name = 'admin_ApplicationDB_'.$prefs->get('ApplicationDB','enable');
	$applicationDB = new $mod_app_name();


	if ($serv_dest->hasAttribute('type') && ($serv_dest->getAttribute('type') == $serv_ori->getAttribute('type')) ){


	}
	else{
		echo '(ERR#105) servers not of the same type';
		die();
	}

	echo '<br>-------- get app list ORI ---------------- '.'http://'.$_GET['fqdn_ori'].'/webservices/applications.php'.'<br>';

	// first we update the dest and ori
	$apps_xml =  query_url('http://'.$_GET['fqdn_ori'].'/webservices/applications.php');
	if (updateserverinfo($_GET['fqdn_ori'], NULL, NULL, $apps_xml) != 'OK'){
		echo '(ERR#106) fail to get application from ori';
		die();
	}
	echo '<br>-------- get app list DEST ---------------- '.'http://'.$_GET['fqdn_dest'].'/webservices/applications.php'.'<br>';
	$apps_xml =  query_url('http://'.$_GET['fqdn_dest'].'/webservices/applications.php');
	echo $apps_xml;
	echo '<br>';
	if (updateserverinfo($_GET['fqdn_dest'], NULL, NULL, $apps_xml) != 'OK'){
		echo '(ERR#107) fail to get application from dest';
		die();
	}
	echo '<br>-------- get app list done ----------------<br>';
	$app_l_dest = new ApplicationServerLiaison(NULL,$serv_dest->fqdn);
	$apps_dest = $app_l_dest->elements();
	$app_l_ori = new ApplicationServerLiaison(NULL,$serv_ori->fqdn);
	$apps_ori = $app_l_ori->elements();

	if (is_null($apps_dest) || is_null($apps_ori)){
		echo '(ERR#108) fail to get application';
		die();
	}

	echo 'dest ';var_dump($apps_dest);echo '<br>';
	echo 'ori ';var_dump($apps_ori);echo '<br>';

	if ($_GET['param'] == 0){
		echo '---------- only add ---------- <br>';

	}
	else {
		echo '---------- duplicate ---------- <br>';
		$to_add = "";
		$to_del = "";
		echo '-------- remove ------------<br>';
		foreach($apps_dest as $a2){
			if (in_array($a2,$apps_ori) == false){
				$a = $applicationDB->import($a2);
				$to_del .= " ".$a->getAttribute('package');
			}
		}
		echo 'to del ';var_dump($to_del);echo '<br>';
// 		die();
		echo 'http://'.$_GET['fqdn_dest'].'/webservices/apt-get.php?action=request&request='.urlencode('--purge remove  '.$to_del).'<br>';
		echo query_url('http://'.$_GET['fqdn_dest'].'/webservices/apt-get.php?action=request&request='.urlencode('--purge remove  '.$to_del));
		echo '<br>';
		sleep(3);

	}
	echo '------- add -------<br>';
	$apps_to_add2 = "";
	foreach($apps_ori as $a2){
		$a = new Application();
		$a = $applicationDB->import($a2);
		$to_del .= " ".$a->getAttribute('package');
	}
	echo 'apps to add ';echo $apps_to_add2;
	echo '<br>';
	echo 'query to install package '.'http://'.$_GET['fqdn_dest'].'/webservices/apt-get.php?action=request&request='.urlencode('install '.$apps_to_add2).'<br>';
	echo query_url('http://'.$_GET['fqdn_dest'].'/webservices/apt-get.php?action=request&request='.urlencode('install '.$apps_to_add2));

}
