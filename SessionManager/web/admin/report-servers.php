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

function data_init() {
	$ret = array(
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

function data_init_from_row($res) {
	$ret = array(
		'down_time' => $res['down_time'],
		'maintenance_time' => $res['maintenance_time'],
		'sessions_count' => $res['sessions_count'],
		'max_connections' => $res['max_connections'],
		'max_connections_when' => $res['max_connections_when'],
		'max_ram' => $res['max_ram'],
		'max_ram_when' => $res['max_ram_when']
	);
	return $ret;
}

if (isset($_REQUEST['start']) && isset($_REQUEST['end'])) {
	$sql = MySQL::getInstance();
	$ret = $sql->DoQuery('SELECT * FROM @1 WHERE @2 >= %3 and @2 <= %4',
	                     SERVERS_REPORT_TABLE,
	                     'date', $_REQUEST['start'], $_REQUEST['end']);

	$global = data_init();
	$unit = array();
	$days_nb = 0;
	while ($res = $sql->FetchResult()) {
		$days_nb++;
		$date = (int)$res['date'];
		$fqdn = $res['fqdn'];

		if (! isset($per_server[$fqdn]))
			$per_server[$fqdn] = data_init();

		if (! isset($day[$date]))
			$per_day[$date] = array('sessions_count' => 0);

		if (! isset($unit[$fqdn]))
			$unit[$fqdn] = array();
		$unit[$fqdn][$date] = data_init_from_row($res);

		$per_server[$fqdn]['down_time'] += $res['down_time'];

		$per_server[$fqdn]['maintenance_time'] += $res['maintenance_time'];

		$per_server[$fqdn]['sessions_count'] += $res['sessions_count'];
		$per_day[$date]['sessions_count'] += $res['sessions_count'];
		$global['sessions_count'] += $res['sessions_count'];

		if ($res['max_connections'] >= $per_server[$fqdn]['max_connections']) {
			$per_server[$fqdn]['max_connections'] = $res['max_connections'];
			$per_server[$fqdn]['max_connections_when'] = $res['max_connections_when'];
		}
		if ($res['max_ram'] >= $per_server[$fqdn]['max_ram']) {
			$per_server[$fqdn]['max_ram'] = $res['max_ram'];
			$per_server[$fqdn]['max_ram_when'] = $res['max_ram_when'];
		}
	}
}

