<?php
require_once(dirname(__FILE__).'/includes/core.inc.php');
require_once(dirname(__FILE__).'/includes/page_template.php');

page_header();

	echo '<h1>'._('Logs').'</h1>';

	echo '<div>';

function my_own_callback($matches) {
	return '<span class="'.strtolower($matches[1]).'">'.trim($matches[0]).'</span>';
}

$logfiles = glob(SESSIONMANAGER_LOGS.'/*.log');
$logfiles = array_reverse($logfiles);

foreach ($logfiles as $logfile) {
	$buf = shell_exec('tail -n 25 '.$logfile);
	$buf = preg_replace_callback('/[^-]*\ -\ [^-]*\ -\ ([^-]*)\ -\ .*/', 'my_own_callback', $buf);

	echo '<h2>'.basename($logfile).'</h2>';

	echo '<div style="border: 1px solid #ccc; background: #fff; padding: 5px; text-align: left;">';

	echo $buf;

	echo '</div>';
}

echo '</div>';
page_footer();
