<h2>Servers report</h2>
<!-- ugly! -->
<table>
  <tr>
    <th>Servers status</th>
	<th>Sessions per day</th>
  </tr>

  <tr>
    <td>
<?php
foreach ($per_server as $fqdn => $server_data) {
	echo "      <h4>Server: $fqdn</h4>\n";
	echo "      <ul>\n";
	foreach ($server_data as $k => $v) {
		echo "        <li>$k => $v</li>\n";
	}
	echo "      </ul>\n";
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
