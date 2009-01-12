<?php
require_once(dirname(__FILE__).'/includes/core.inc.php');

require_once('header.php');

function my_own_callback($matches) {
	return '<span class="'.strtolower($matches[1]).'">'.trim($matches[0]).'</span>';
}

$logfiles = glob(SESSIONMANAGER_LOGS.'/*.log');
$logfiles = array_reverse($logfiles);

foreach ($logfiles as $logfile) {
	$buf = shell_exec('tail -n 25 '.$logfile);
	$buf = preg_replace_callback('/[^-]*\ -\ [^-]*\ -\ ([^-]*)\ -\ .*/', 'my_own_callback', $buf);

	echo '<h2>'.basename($logfile).'</h2>';

	echo '<div style="border: 1px solid black; background: #eee; padding: 5px;">';

	echo $buf;

	echo '</div>';
}

require_once('footer.php');
