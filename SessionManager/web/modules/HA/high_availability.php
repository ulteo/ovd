<?php
/**
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com>
 * Author Arnaud LEGRAND <arnaud@ulteo.com>
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
class HA_high_availability  extends HA {
	public function __construct () {}
	public static function getConfiguration() {
		exec("which HAshell", $out, $ret);
		if ($ret != 0)
			return array();
		exec("HAshell get_conf_file x", $res);
		return $res;
	}
	public static function extractVarsFromConfFile() {
		$vars= array("VIP"=>"");
		$content =  HA_high_availability::getConfiguration();
		foreach ($content as $v){
			$tmp=explode("=",$v);
			if (isset($tmp[0]) && isset($tmp[1])){
				if ($tmp[0]=="VIP" && strlen($tmp[1])>0){
					$vars["VIP"]="".$tmp[1];
				}
			}
		}
		return $vars;
	}

	public static function configuration() {
		$ret = array();
		$vars=HA_high_availability::extractVarsFromConfFile();
		$c = new ConfigElement_input('VIP', _('Virtual IP'), _('Virtual IP'), _('Virtual IP'),$vars["VIP"]);
		$ret[]= $c;
		return $ret;
	}
 
	public static function prefsIsValid($prefs_, &$log=array()) {
		return true;
	}

	public static function prettyName() {
		return _('HA configuration');
	}
	
	public static function isDefault() {
		return true;
	}

	public static function init($prefs_) {
		$sql_conf = $prefs_->get('general', 'sql');
		if (!is_array($sql_conf)) {
			return false;
		}
		$table = $sql_conf['prefix'].'ha';
		$sql2 = SQL::newInstance($sql_conf);
		$ha_table_structure = array(
			'id_host' => 'int(5) NOT NULL AUTO_INCREMENT',
			'hostname' => 'VARCHAR(255) NOT NULL',
			'address' => 'VARCHAR(255) NOT NULL',
			'timestamp' => 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
			'register' => 'ENUM( "yes", "no" ) NOT NULL');
		$ret = $sql2->buildTable($table, $ha_table_structure, array('id_host'));
		if ( $ret === false) {
			return false;
		}
		else {
			return true;
		}
		return false;
	}

	public static function enable() {
		return true;
	}
	
}
