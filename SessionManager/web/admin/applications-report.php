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

require_once(dirname(__FILE__).'/includes/core.inc.php');
require_once('header.php');

function init_app_id() {
	$ret = array(
		'use_count' => 0,
		'max_use' => 0,
		'max_use_when' => 0
	);
	return $ret;
}

if (! isset($_REQUEST['start']))
    $start = "";
else
    $start = $_REQUEST['start'];

if (! isset($_REQUEST['end']))
    $end = "";
else
    $end = $_REQUEST['end'];
?>

<form action="applications-report.php" method="get">
  From <input type="text" name="start" maxlength="8" value="<?php echo $start ?>" />
  to <input type="text" name="end" maxlength="8" value="<?php echo $end ?>" />
  (YYYYMMDD)
  <input type="submit" value="Report" />
</form>
<hr />

<?php
if (isset($_REQUEST['start']) && isset($_REQUEST['end'])) {
	$sql = MySQL::getInstance();
	$ret = $sql->DoQuery('SELECT * FROM @1 WHERE @2 >= %3 and @2 <= %4',
	                     APPLICATIONS_REPORT_TABLE,
	                     'date', $_REQUEST['start'], $_REQUEST['end']);


	$data = array();
	$days_nb = 0;
	while ($res = $sql->FetchResult()) {
		$days_nb++;
		$fqdn = $res['fqdn'];
		$app_id = $res['app_id'];
		if (! isset($data[$fqdn]))
			$data[$fqdn] = array();

		if (! isset($data[$fqdn][$app_id]))
			$data[$fqdn][$app_id] = init_app_id();

		$data[$fqdn][$app_id]['use_count'] += $res['use_count'];
		if ($res['max_use'] >= $data[$fqdn][$app_id]['max_use']) {
			$data[$fqdn][$app_id]['max_use'] = $res['max_use'];
			$data[$fqdn][$app_id]['max_use_when'] = $res['max_use_when'];
		}
	}

	/* TODO: output should be handled by templates */
	foreach ($data as $fqdn => $server_data) {
		print "<h3>Server: ".$fqdn."</h3>\n";
		foreach ($server_data as $app_id => $app_data) {
			print "  <h4>Application id: $app_id</h4>\n";
			print "    <ul>\n";
			print "    <li>Used ".$app_data['use_count']." time(s)</li>\n";
			print "    <li>Max simultaneous run: ".$app_data['max_use']."</li>\n";
			print "    </ul>\n";
		}
	}
}

require_once('footer.php');
