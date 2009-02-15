<?php

foreach ($data as $server) {
	print "<b>Server: ".$server['fqdn']."</b>\n";
	print "<ul>\n";
	foreach ($server as $key => $value) {
		print "  <li>$key => $value <br /></li>\n";
	}
	print "</ul>";
}

