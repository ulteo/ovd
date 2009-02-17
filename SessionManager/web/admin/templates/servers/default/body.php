<b>Server: <? echo $server_data['fqdn'] ?></b>
<ul>
<?php
foreach ($server_data as $key => $value) {
	print "  <li>$key => $value <br /></li>\n";
}
?>
</ul>
