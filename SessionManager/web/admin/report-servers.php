<?php
/**
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Gauvain Pocentek <gauvain@ulteo.com>
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

function init_fqdn($fqdn_) {
	$ret = array(
		'fqdn' => $fqdn_,
		'down_time' => 0,
		'maintenance_time' => 0,
		'sessions_count' => 0,
		'max_connections' => 0,
		'max_connections_when' => 0,
		'max_ram' => 0,
		'max_ram_when' => 0
	);
	return $ret;
}

if (isset($_REQUEST['start']) && isset($_REQUEST['end'])) {
	$sql = MySQL::getInstance();
	$ret = $sql->DoQuery('SELECT * FROM @1 WHERE @2 >= %3 and @2 <= %4',
	                     SERVERS_REPORT_TABLE,
	                     'date', $_REQUEST['start'], $_REQUEST['end']);

	$data = array();
	$days_nb = 0;
	while ($res = $sql->FetchResult()) {
		$days_nb++;
		$fqdn = $res['fqdn'];
		if (! isset($data[$fqdn]))
			$data[$fqdn] = init_fqdn($fqdn);

		$data[$fqdn]['down_time'] += $res['down_time'];
		$data[$fqdn]['maintenance_time'] += $res['maintenance_time'];
		$data[$fqdn]['sessions_count'] += $res['sessions_count'];
		if ($res['max_connections'] >= $data[$fqdn]['max_connections']) {
			$data[$fqdn]['max_connections'] = $res['max_connections'];
			$data[$fqdn]['max_connections_when'] = $res['max_connections_when'];
		}
		if ($res['max_ram'] >= $data[$fqdn]['max_ram']) {
			$data[$fqdn]['max_ram'] = $res['max_ram'];
			$data[$fqdn]['max_ram_when'] = $res['max_ram_when'];
		}
	}
}

