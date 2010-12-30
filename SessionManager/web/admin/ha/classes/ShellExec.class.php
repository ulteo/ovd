<?php
 /**
 * Copyright (C) 2008-2010 Ulteo SAS
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


class ShellExec {
	public static function exec_cmd($cmd) {
		exec('PATH=$PATH:/usr/sbin:/sbin HAshell '.$cmd, $res, $val);
		return $res;
	}

	public static function exec_action_to_host($host,$status) {
		 $cmd="crm_standby ".$host." ".$status;
		 return ShellExec::exec_cmd($cmd);
	}

	public static function exec_cleanup_resource($resource) {
		 $cmd="crm_resource_cleanup ".$resource;
		 return ShellExec::exec_cmd($cmd);
	}

	public static function exec_view_logs($severity) {
		 $cmd='view_logs "'.implode("|",$severity).'"';
		 return ShellExec::exec_cmd($cmd);
	}

	public static function exec_clean_logs() {
		 $cmd='cleanup_logs /tmp';
		 return ShellExec::exec_cmd($cmd);
	}

	public static function exec_cibadmin() {
		 $cmd='cibadmin -Q';
		 return ShellExec::exec_cmd($cmd);
	}

	public static function exec_get_hostname() {
		 $cmd='get_hostname x';
		 return ShellExec::exec_cmd($cmd);
	}

	public static function exec_shell_cmd($cmd, $other_ip, $other_hostname, $authkey) {
		 $cmd='shell_cmd '.$cmd." ".$other_ip." ".$other_hostname." ". $authkey;
		 return ShellExec::exec_cmd($cmd);
	}

	public static function exec_get_conf_file() {
		 $cmd='get_conf_file x';
		 return ShellExec::exec_cmd($cmd);
	}
	
	public static function exec_set_conf_file($content) {
		 $cmd='set_conf_file '.$content;
		 return ShellExec::exec_cmd($cmd);
	}
}
