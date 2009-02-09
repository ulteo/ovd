<?php

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

require_once(dirname(__FILE__).'/includes/core.inc.php');

require_once('header.php');

?>

<form action="serverreport.php" method="get">
  From <input type="text" name="start" maxlength="8" value="<?php echo $_REQUEST['start']; ?>" />
  to <input type="text" name="end" maxlength="8" value="<?php echo $_REQUEST['end']; ?>" />
  (YYYYMMDD)<br />
  <input type="submit" value="Report" />
</form>
<hr />

<?php
if (isset($_REQUEST['start']) && isset($_REQUEST['end'])) {
	$sql = MySQL::getInstance();
	$ret = $sql->DoQuery('SELECT * FROM @1 WHERE @2 >= %3 and @2 <= %4',
	                     SERVERS_REPORT_TABLE,
	                     'date', $_REQUEST['start'], $_REQUEST['end']);


	$data = array();
	$days_nb = 0;
	while ($res = $sql->FetchResult()) {
		$days_nb++;
		if (! isset($data[$res['fqdn']]))
			$data[$res['fqdn']] = init_fqdn($res['fqdn']);

		$data[$res['fqdn']]['down_time'] += $res['down_time'];
		$data[$res['fqdn']]['maintenance_time'] += $res['maintenance_time'];
		$data[$res['fqdn']]['sessions_count'] += $res['sessions_count'];
		if ($res['max_connections'] >= $data[$res['fqdn']]['max_connections']) {
			$data[$res['fqdn']]['max_connections'] = $res['max_connections'];
			$data[$res['fqdn']]['max_connections_when'] = $res['max_connections_when'];
		}
		if ($res['max_ram'] >= $data[$res['fqdn']]['max_ram']) {
			$data[$res['fqdn']]['max_ram'] = $res['max_ram'];
			$data[$res['fqdn']]['max_ram_when'] = $res['max_ram_when'];
		}
	}

	/* TODO: output should be handled by templates */
	foreach ($data as $server) {
		print "<b>Server: ".$server['fqdn']."</b>\n";
		print "<ul>\n";
		foreach ($server as $key => $value) {
			print "  <li>$key => $value <br /></li>\n";
		}
		print "</ul>";
	}
}

require_once('footer.php');
