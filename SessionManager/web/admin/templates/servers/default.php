<h2>Servers report</h2>
<table border="1">
  <tr>
    <th>Servers status</th>
	<th>Sessions per day</th>
  </tr>

  <tr>
    <td>
<?php
foreach ($per_server as $fqdn => $server_data) {
	$down_time = (int)($server_data['down_time'] / 60);
	$type = '';
	$type_string = '';
	if (isset($servers_info[$fqdn])) {
		$type = $servers_info[$fqdn]->getAttribute('type');
		$type_string = "($type)";
	}

	echo "    <h4>Server: $fqdn $type_string</h4>\n";
	echo "    <ul>\n";
	echo "      <li>"._('Down time: ').$down_time._(' minute(s)').'</li>';
	if ($type != 'windows') {
		echo "      <li>"._('Sessions count: ').$server_data['sessions_count'].'</li>';
		echo "      <li>"._('Max simultaneous sessions: ');
		echo $server_data['max_connections'].'</li>';
	}
	echo "    </ul>\n";
}
?>
    </td>

    <td>
<?php
foreach ($per_day as $day => $day_data) {
	echo "      $day => {$day_data['sessions_count']} sessions <br />\n";
}
?>
    </td>
  </tr>
</table>
