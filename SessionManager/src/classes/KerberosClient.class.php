<?php
/**
 * Copyright (C) 2008 Ulteo SAS
 * http://www.ulteo.com
 * Author Jocelyn Delalande <jocelyn@ulteo.com>
 * Author Laurent CLOUET <laurent@ulteo.com>
 * Author Jeremy DESVAGES <jeremy@ulteo.com>
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
// Quick'n'dirty kerberos client class
// TODO: re-ipmlement cleanly from C libs.

class KerberosClient {
	public function KerberosClient($principal, $password, $ccache=false) {
		Logger::debug('main', "KerberosClient::kinit principal '$principal'");
		if ($ccache) {
			if ($ccache == 'random')
				$ccache = '/tmp/krb5cc_'.getmyuid().'-'.rand();
		} else {
			// Standard default value.
			$ccache = '/tmp/krb5cc_'.getmyuid();
		}

		$this->cmd_opts .= ' -c '.escapeshellarg($ccache);

		$this->kinit($principal, $password, $ccache);
	}

	public function kinit($principal, $password, $ccache) {
		Logger::debug('main', 'Starting KerberosClient::kinit for principal '.$principal);

		//Principal should have caps on domain.
		$this->principal = $principal;
		$this->ccache = $ccache;

		$descriptors = array(
			 0	=>	array('pipe', 'r'),
			 1	=>	array('pipe', 'w'),
		);

		$kinit_launch = '/usr/bin/kinit'.$this->cmd_opts.' '.escapeshellarg($this->principal);
		$processus = proc_open($kinit_launch, $descriptors, $pipes);

		if (is_resource($processus)) {
			fread($pipes[1], 128);
			fwrite($pipes[0], $password);
			fclose($pipes[0]);
			fread($pipes[1], 128);
			fclose($pipes[1]);

			if (proc_close($processus) == 0) {
				Logger::info('main', 'kinit for principal '.$principal.' done !');
				return true;
			}
		}

		Logger::error('main', 'Kerberos does not seem to be installed');
		return false;
	}

	public function klist() {
		Logger::debug('main', 'Starting KerberosClient::klist');

		$cmd = 'klist'.$this->cmd_opts;
		exec($cmd, $out, $returncode);

		if ($returncode == 0) {
			Logger::info('main', 'klist done !');
			return $out;
		}

		Logger::error('main', 'Command failed: '.$cmd.' with return code '.$returncode);
		return false;
	}

	public function isAuthenticated() {
		Logger::debug('main', 'Starting KerberosClient::isAuthenticated');

		if ($this->klist()) {
			Logger::info('main', 'User is authenticated');
			return true;
		}

		Logger::error('main', 'User is NOT authenticated');
		return false;
	}
}
