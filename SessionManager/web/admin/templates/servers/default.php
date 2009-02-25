<h2>Servers report</h2>
<table cellspacing="10">
  <tr>
    <th><?php print _('Servers status'); ?></th>
	<th><?php print _('Sessions per day'); ?></th>
  </tr>

  <tr>
    <td style="vertical-align: top">
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
if (init_libchart()) {
	$chart = new LineChart();
	$dataSet = new XYDataSet();
	foreach ($per_day as $day => $day_data) {
		$dataSet->addPoint(new Point($day, $day_data['sessions_count']));
	}
	$chart->setDataSet($dataSet);
	$chart->setTitle(_('Sessions per day'));
	$tmpfile = tempnam('/tmp', 'serverchart');
	$file_id = preg_replace('/\/tmp\/serverchart/', '', $tmpfile);
	$chart->render($tmpfile);

	$src = $base_url.'admin/templates/getchart.php?file='.$file_id;
	echo '   <img src="'.$src.'" />';

} else {
	foreach ($per_day as $day => $day_data) {
		echo "      $day => {$day_data['sessions_count']} sessions <br />\n";
	}
}
?>
    </td>
  </tr>
</table>
