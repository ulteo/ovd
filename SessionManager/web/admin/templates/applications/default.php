<h2>Applications report</h2>
<!-- ugly! -->
<table border="1">
  <tr>
    <th>Per server</th>
	<th>Per day</th>
	<th>Per application</th>
  </tr>

  <tr>
    <td>
<?php
foreach ($per_server as $fqdn => $server_data) {
	echo "      <h4>Server: $fqdn</h4>\n";
	foreach ($server_data as $app_id => $app_data) {
		echo "      <h5>$app_id</h5>\n";
		echo "      <ul>\n";
		echo "        <li>Used {$app_data['use_count']} time(s)</li>\n";
		echo "        <li>Max simultaneous use {$app_data['max_use']}</li>\n";
		echo "      </ul>\n";
	}
}
?>
    </td>

    <td>
<?php
foreach ($per_day as $day => $day_data) {
	echo "      <h4>Day: $day</h4>\n";
	foreach ($day_data as $app_id => $app_data) {
		echo "      <h5>$app_id</h5>\n";
		echo "      <ul>\n";
		echo "        <li>Used {$app_data['use_count']} time(s)</li>\n";
		echo "      </ul>\n";
	}
}
?>
    </td>

	<td>
<?php
echo "      <ul>\n";
foreach ($per_app as $app => $data) {
	echo "        <li>$app used {$data['use_count']} time(s)</li>\n";
}
?>
    </td>
  </tr>
</table>
