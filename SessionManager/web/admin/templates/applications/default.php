<?php

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

