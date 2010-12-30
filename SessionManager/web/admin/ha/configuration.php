<?php
/**
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Arnaud LEGRAND <arnaud@ulteo.com> 2010
 * Author Samuel BOVEE <samuel@ulteo.com> 2010
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

require_once(dirname(__FILE__).'/../includes/core.inc.php');
require_once(dirname(__FILE__).'/classes/ShellExec.class.php');
require_once(dirname(__FILE__).'/../includes/page_template.php');

if (! checkAuthorization('viewConfiguration'))
	redirect('../index.php');

define ("_URL_OVD_ADMIN_HA", "/ovd/admin/ha");

function getResponseType($response) {
	$response_type=false;
	$xml=new DomDocument();
	$xml->loadXML($response);
	$xml->documentElement;
	$n_nodes=$xml->getElementsByTagName('response');
	foreach ($n_nodes as $c) {
		if ($c->nodeType == XML_ELEMENT_NODE) {
			if ($c->hasAttribute("id")) {
				$id=intval($c->getAttribute("id"));
				if ($id == 0) {
					$response_type=true; 
				} else {
					popup_error($c->getAttribute("message"));
				}
				break;
			}
		}
	 }
	 return $response_type;
}

function validateIpAddress($ip_addr) {
	if (filter_var($ip_addr, FILTER_VALIDATE_IP)) {
		return true;
	}
	return false;
	/*if(preg_match("/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/",$ip_addr)) {
		$parts=explode(".",$ip_addr);
		foreach($parts as $ip_parts) {
			if(intval($ip_parts)>255 || intval($ip_parts)<0) {
				return false;
			}
		}
		return true;
	}
	else {
		return false;
	}*/
}

function extractVarsFromConfFile() {
	$vars= array();
	$content = ShellExec::exec_get_conf_file();
	foreach ($content as $v) {
		$tmp=explode("=",$v);
		if (isset($tmp[0]) && isset($tmp[1])) {
			if (strlen($tmp[0])>0 && strlen($tmp[1])>0) {
				$vars[$tmp[0]]=$tmp[1];
			}
		}
	}
	return $vars;
}

function writeVarsToConfFile($vars) {
	$content="";
	foreach ($vars as $k => $v) {
		$content.=$k."=".$v.":";
	}
	$ret = ShellExec::exec_set_conf_file($content);
}

function makeConfiguration($slave_ip, $slave_hostname) {
	$t=false;
	$prefs_vars=extractVarsFromConfFile();
	if (!count($prefs_vars)) {
		popup_error(_('An error occured while reading configuration file, no values found!'));
		return;
	}
	$prefs = Preferences::getInstance();
	$all_prefs = $prefs->get('HA','high_availability');
	if (isset($all_prefs["VIP"]) && isset($prefs_vars["VIP"])) {
			if ($prefs_vars["VIP"] != $all_prefs["VIP"]) {
				$prefs_vars["VIP"]=$all_prefs["VIP"];
				$t=true;
			}
	}
	if ($t) {
		writeVarsToConfFile($prefs_vars);
	}
}

function updateVIP($vip) {
	$t=false;
	$prefs_vars=extractVarsFromConfFile();
	if (!count($prefs_vars)) {
		popup_error(_('Could not extract configuration values from file!'));
		return false;
	}
	$prefs= Preferences::getInstance();
	$all_prefs = $prefs->get('HA','high_availability');
	$prefs_vars["VIP"]=$vip;
	$all_prefs["VIP"]=$vip;
	if (isset($all_prefs["VIP"]) && isset($prefs_vars["VIP"])) {
		try {
			$prefs_admin = new Preferences_admin();
			$prefs_admin->set('HA', 'high_availability', $all_prefs);
			$ret=$prefs_admin->backup();
			return true;
		}
		catch (Exception $e) {
			popup_error(_('An error occured when updating the virtual IP!'));
		}
		return false;
	 } else {
		popup_error(_('An error occured when updating the virtual IP!'));
	 }
	 return false;
}

function checkConfigurationHasChanged($apply) {
	$t=false;
	$v=false;
	$prefs_vars=extractVarsFromConfFile();
	if (!count($prefs_vars)) {
		popup_error(_('An error occured while reading configuration file, no values found!'));
		return;
	}
	$prefs = Preferences::getInstance();
	$all_prefs = $prefs->get('HA','high_availability');
	if (isset($all_prefs["VIP"]) && isset($prefs_vars["VIP"])) {
		if ($prefs_vars["VIP"] != $all_prefs["VIP"]) {
			$prefs_vars["VIP"]=$all_prefs["VIP"];
			$t=true;
		}
	}
	if ($apply && $t) {
		writeVarsToConfFile($prefs_vars);
		return true;
	}
	return $t;
}

function getRegistration($SQL, $table) {
	$query = 'SELECT * FROM `'.$table.'` ORDER BY timestamp ASC';
	$res = $SQL->DoQuery($query);
	$nb = $SQL->NumRows();
	$html="";
	if (! $nb) {
		$html.= "<p>"._("No host has been registered yet.")."</p>";
		 return $html;
	}
	$rows = $SQL->FetchAllResults($res);
	$b = false;
	foreach ($rows as $row) {
		if ($row["register"] == "yes") {
			$html.=getRegisteredServer($row["id_host"],$row["hostname"], $row["address"], $row["timestamp"]);
		} else {
			$b=true;
			$html.=getUnregisteredServer($row["id_host"],$row["hostname"], $row["address"], $row["timestamp"]);
		}
	}
	if ($b) {
		$html.= '<form action="configuration.php" method="post" enctype="multipart/form-data">';
		$html.= '<input type="hidden" name="action" value="delete" />';
		$html.= '<input type="submit" name="submit" value="'._("Delete unregistered servers").'" />';
		$html.= '</form>';
	}
	return $html;
}

function getChangeConfigurationsForm() {
	$html= '<form  onsubmit="return confirm(\''._("Your will have to change the virtual IP and then modify your url with the correct IP to reach the admin web interface!").'\');"  action="configuration.php" method="post" enctype="multipart/form-data">';
	$html.= '<input type="hidden" name="action" value="reload_vip" />';
	$html.= '<input style="background:#229630; color:#FFF; font-weight:bold" type="submit" name="submit" value="'._("Apply new virtual IP").'" />';
	$html.= '</form>';
	return $html;
}

function deleteAllUnregisteredServers($SQL, $table) {
	$query = 'DELETE FROM `'.$table.'` WHERE register = "no"';
	$res = $SQL->DoQuery($query);
	$nb = $SQL->NumRows();
	if ($nb) {
		popup_info(sprintf(_('%s server(s) has been successfully removed!'),$nb));
	} else {
		popup_error(_('An error occured, no unregistered server deleted!'));
	}
}

function isActivable($SQL, $table, $id_host) {
	$query = 'SELECT * FROM `'.$table.'` WHERE register = "yes"';
	$res = $SQL->DoQuery($query);
	$nb = $SQL->NumRows();
	if ($nb) {
		popup_error(_('Cannot enable host because one is already enabled!'));
		return false;
	}
	return true;
}

function isDeactivable($SQL, $table, $id_host) {
	$query = 'SELECT * FROM `'.$table.'` WHERE register = "yes" AND id_host='.$id_host;
	$res = $SQL->DoQuery($query);
	$nb = $SQL->NumRows();
	if (! $nb) {
		popup_error(_('Cannot disable host because it is not enabled!'));
		return false;
	}
	return true;
}

function enable_hostDB($SQL, $table, $id_host) {
	$query = 'UPDATE `'.$table.'` SET register="yes"  WHERE id_host='.$id_host;
	$res = $SQL->DoQuery($query);
	$nb = $SQL->NumRows();
	if ($nb) {
		popup_info(_('Host has been enabled!'));
		
	} else {
		 popup_error(_('An error occured during activation, can not enable host!'));
	}
}

function disable_hostDB($SQL, $table, $id_host) {
	$query = 'SELECT * FROM `'.$table.'` WHERE register="yes" AND id_host='.$id_host;
	$res = $SQL->DoQuery($query);
	$nb = $SQL->NumRows();
	if (!$nb) {
		popup_error(_('Cannot disable host because it is not enabled!'));
	}
	$query = 'DELETE FROM `'.$table.'` WHERE id_host='.$id_host;
	$res = $SQL->DoQuery($query);
	$nb = $SQL->NumRows();
	if ($nb) {
		popup_info(_('Host has been disabled!'));
		
	} else {
		popup_error(_('An error occured during disabling host, cannot disable host!'));
	}
}

function getUnregisteredServer($id_host,$hostname, $fqdn, $timestamp) {
	$html="";
	$html.= '<div id="ha_unregistered_servers'.$id_host.'">';
	$html.= '<form onsubmit="return confirm(\''._("When enabling the slave server, the master server and slave server are going to restart their services to load the new configuration. It takes less than 3 minutes. During this time the web interface will be disabled.").'\')" action="configuration.php" method="post" enctype="multipart/form-data">';
	$html.= '<input type="hidden" name="action" value="enable" />';
	$html.= '<input type="hidden" name="id_host" value="'.$id_host.'" />';
	$html.= '<table id="ha_config"  style="" class="main_sub" border="0" cellpadding="3" cellspacing="1">';
	$html.= '<tr class="title">';
	$html.= '<td colspan="2">';
	$html.= _("Unregistered Server");
	$html.= '</td>';
	$html.= '</tr>';
	$html.= '<tr class="content1"><td width="200">';
	$html.= _("Hostname");
	$html.= '</td><td>';
	$html.= '<input type="text" name="hostname" value="'.$hostname.'" disabled="disabled" />';
	$html.= '</td></tr>';
	$html.= '<tr class="content1"><td width="200">';
	$html.= _("address");
	$html.= '</td><td>';
	$html.= '<input type="text" name="hostname" value="'.$fqdn.'" disabled="disabled" />';
	$html.= '</td></tr>';
	$html.= '<tr class="content1"><td width="200">';
	$html.= _("Date");
	$html.= '</td><td>';
	$html.= '<input type="text" name="hostname" value="'.$timestamp.'" disabled="disabled" />';
	$html.= '</td></tr>';
	$html.= '<tr class="content2"><td colspan="2">';
	$html.= '<input type="submit" style="background:#05a305; font-weight:bold; color:#FFF;" name="submit" value="'._("Enable").'" size="20"/>';
	$html.= '</td></tr>';
	$html.= '</table>';
	$html.= "</form>";
	$html.= "</div>";
	$html.= '<br />';
	return $html;
}

function getRegisteredServer($id_host,$hostname, $fqdn, $timestamp) {
	$html="";
	$html.= '<div id="ha_registered_servers'.$id_host.'">';
	$html.= '<form onsubmit="return confirm(\''._("When enabling the slave server, the master server is going to restart their services to load the new configuration. It takes less than 3 minutes. During this time the web interface will be disabled.").'\');"  action="configuration.php" method="post" enctype="multipart/form-data">';
	$html.= '<input type="hidden" name="action" value="disable" />';
	$html.= '<input type="hidden" name="id_host" value="'.$id_host.'" />';
	$html.= '<table id="ha_config"  style="" class="main_sub" border="0" cellpadding="3" cellspacing="1">';
	$html.= '<tr class="title">';
	$html.= '<td colspan="2">';
	$html.= _("Registered Server");
	$html.= '</td>';
	$html.= '</tr>';
	$html.= '<tr class="content1"><td width="200">';
	$html.= _("Hostname");
	$html.= '</td><td>';
	$html.= '<input type="text" name="hostname" value="'.$hostname.'" disabled="disabled" />';
	$html.= '</td></tr>';
	$html.= '<tr class="content1"><td width="200">';
	$html.= _("address");
	$html.= '</td><td>';
	$html.= '<input type="text" name="hostname" value="'.$fqdn.'" disabled="disabled" />';
	$html.= '</td></tr>';
	$html.= '<tr class="content2"><td colspan="2">';
	$html.= '<input type="submit" name="submit" value="'._("Disable").'" size="20"/>';
	$html.= '</td></tr>';
	$html.= '</table>';
	$html.= "</form>";
	$html.= "</div>";
	$html.= '<br />';
	return $html;
}

function getVIPFormBox() {
	$vip="";
	$prefs = Preferences::getInstance();
	$all_prefs = $prefs->get('HA','high_availability');
	$vip=$all_prefs["VIP"];
	echo '<br /><div id="HA"><form name="manage_node" action="configuration.php" method="post"><table class="main_sub" border="0" cellpadding="3" cellspacing="1"><tr class="title"><th colspan="2">'._("Update HA configuration").'</th></tr><tr class="content1"><td style="width: 200px;"><span>Virtual IP</span></td><td style="padding: 3px;"><input type="hidden" name="action" value="update_vip" /><input id="ha_vip" name="ha_vip" value="'.$vip.'" size="25" type="text"></td></tr><tr class="content2"><td colspan="2"><input type="submit" value="'._("Submit").'" /></td></tr></table></form></div><br />';
}

function query_slave($action,$m_hostname,$passwd,$s_ip) {
	$req = query_url_post("https://".$s_ip._URL_OVD_ADMIN_HA."/registration.php", "action=".$action."&hostname=".$m_hostname."&passwd=".$passwd, false);
	if (getResponseType($req)) {
		   Logger::warning('ha', "Activation request from ".$m_hostname." to ".$s_ip." has been send!");
	} else{
		 Logger::error('ha', "An error occured when trying to send an HTTP request (registration.php)  from ".$m_hostname." to ".$s_ip);
		 return false;
	}
	return true;
}

function makePassword() {
	return rand().time().rand();
}

$upd_vip=false;
$upd_slave=false;
$SQL = SQL::getInstance();
$prefs = Preferences::getInstance();
$sql_conf = $prefs->get('general', 'sql');
$table = $sql_conf['prefix'].'ha';
if (isset($_POST["action"]))
	$action		= $_POST["action"];
if (isset($_POST["id_host"]))
	$id_host	= $_POST["id_host"];

unset($_POST["action"]);
$m_hostname=trim(file_get_contents("/etc/hostname"));
$html="";
$system_in_maintenance = $prefs->get('general', 'system_in_maintenance');
if ($system_in_maintenance) {
	unset($action);
	popup_error(_('The system is on maintenance mode! Cannot apply changes.'));
}

if (isset($action)) {
	switch ($action) {
		case "enable":
			if (isset($id_host)) {
				if (isActivable($SQL, $table, $id_host)) {
					$passwd = makePassword();
					if(strlen($m_hostname)>0) {
						$query = 'SELECT * FROM `'.$table.'` WHERE register="no" AND id_host='.$id_host;
						$res = $SQL->DoQuery($query);
						$nb = $SQL->NumRows();
						if (!$nb) {
							popup_error(_('Cannot enable host because no host found in DB!'));
							break;
						}
						$row = $SQL->FetchResult($res);
						$s_ip = $row["address"];
						$s_hostname = $row["hostname"];
						if (isset($s_ip) && isset($s_hostname) && query_slave($action,$m_hostname,$passwd,$s_ip)) {
							makeConfiguration($s_ip,$s_hostname);
							$ret=ShellExec::exec_shell_cmd("reload_master",$s_ip,$s_hostname,$passwd);
							enable_hostDB($SQL, $table, $id_host);
							popup_info(sprintf(_('Host %s at address %s has been notifyed succesfully!'),$s_hostname,$s_ip));	
							$upd_slave=true;
						} else {
							popup_error(sprintf(_('Cannot query slave host at address %s'),$s_ip));
							break;
						}
					}
				}
			}
			break;
		case "disable":
			if (isset($id_host)) {
				if (isDeactivable($SQL, $table, $id_host)) {
					$passwd = makePassword();
					$query = 'SELECT * FROM `'.$table.'` WHERE register = "yes" AND id_host='.$id_host;
					$res = $SQL->DoQuery($query);
					$nb = $SQL->NumRows();
					if ($nb && strlen($m_hostname)>0) {
						$row = $SQL->FetchResult($res);
						$s_ip = $row["address"];
						$s_hostname = $row["hostname"];
						if (isset($s_ip) && isset($s_hostname)) {
							disable_hostDB($SQL, $table, $id_host);
							popup_info(sprintf(_('Host %s has been set in standby and master %s will restart Heartbeat!'),$s_hostname,$m_hostname));	
							$ret=ShellExec::exec_action_to_host($s_hostname,"on");
							$ret=ShellExec::exec_shell_cmd("reload_master_excl","0","0",$passwd);
							$upd_slave=true;
							break;
						} else {
							popup_error(sprintf(_('Cannot query slave host at address %s'),$s_ip));
							break;
						}
					} else {
						popup_error(_('An error occured during disabling, can not disable host!'));
					}	
				}
			}
			break;
		case "reload_vip":
			$prefs = Preferences::getInstance();
			$all_prefs = $prefs->get('HA','high_availability');
			if (isset($all_prefs["VIP"])) {
				$ret=ShellExec::exec_shell_cmd("reload_vip",$all_prefs["VIP"],"0","0");
				checkConfigurationHasChanged(true);
				popup_info(_('Changing configuration... Please wait a few seconds'));
			}
			else {
				popup_error(_('Changing configuration failded... Verify configuration at field HA::Virtual IP'));
			}
			break;
		case "delete":
			deleteAllUnregisteredServers($SQL, $table);
			break;
		case "update_vip":
			if (isset($_POST["ha_vip"])) {
				if (validateIpAddress($_POST["ha_vip"])) {
					$t=updateVIP($_POST["ha_vip"]);
					redirect("configuration.php");
				}
				else {
					popup_error(sprintf(_('Virtual IP "%s" is not valid!'), $_POST["ha_vip"]));
				}
			}
			break;
		default:
			break;
	}
}
page_header();
echo '<div>';
echo '<h1>'._('Configuration').'</h1>';
echo '<div>';
echo $html.getRegistration($SQL, $table);
getVIPFormBox();
if (checkConfigurationHasChanged(false)) {
	echo getChangeConfigurationsForm();
}
echo  "</div>";
echo  "</div>";
page_footer();
