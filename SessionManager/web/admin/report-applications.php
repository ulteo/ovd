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

function init_app_id() {
	$ret = array(
		'use_count' => 0,
		'max_use' => 0,
		'max_use_when' => 0
	);
	return $ret;
}

if (isset($_REQUEST['start']) && isset($_REQUEST['end'])) {
	$sql = MySQL::getInstance();
	$ret = $sql->DoQuery('SELECT * FROM @1 WHERE @2 >= %3 and @2 <= %4',
	                     APPLICATIONS_REPORT_TABLE,
	                     'date', $_REQUEST['start'], $_REQUEST['end']);


	$per_server = array();
	$per_day = array();
	$per_app = array();

	$days_nb = 0;
	while ($res = $sql->FetchResult()) {
		$days_nb++;
		$date = (int)$res['date'];
		$fqdn = $res['fqdn'];
		$app_id = $res['app_id'];

		if (! isset($per_server[$fqdn]))
			$per_server[$fqdn] = array();
		if (! isset($per_day[$date]))
			$per_day[$date] = array();
		if (! isset($per_app[$app_id]))
			$per_app[$app_id] = array('use_count' => 0);

		if (! array_key_exists($app_id,$per_server[$fqdn]))
			$per_server[$fqdn][$app_id] = init_app_id();
		if (! array_key_exists($app_id,$per_day[$date]))
			$per_day[$date][$app_id] = array('use_count' => 0);

		$per_server[$fqdn][$app_id]['use_count'] += $res['use_count'];
		$per_day[$date][$app_id]['use_count'] += $res['use_count'];
		$per_app[$app_id]['use_count'] += $res['use_count'];

		if ($res['max_use'] >= $per_server[$fqdn][$app_id]['max_use']) {
			$per_server[$fqdn][$app_id]['max_use'] = $res['max_use'];
			$per_server[$fqdn][$app_id]['max_use_when'] = $res['max_use_when'];
		}
	}
}

