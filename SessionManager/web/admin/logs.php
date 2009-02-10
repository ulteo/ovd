<?php
require_once(dirname(__FILE__).'/includes/core.inc.php');

require_once('header.php');
// echo '<div class="container rounded" style="background: #fff; width: 98%; margin-left: auto; margin-right: auto;">';

	echo '<table style="width: 98.5%; margin-left: 10px; margin-right: 10px;" border="0" cellspacing="0" cellpadding="0">';
	echo '<tr>';
	echo '<td style="width: 150px; text-align: center; vertical-align: top; background: url(\'media/image/submenu_bg.png\') repeat-y right;">';
	include_once(dirname(__FILE__).'/submenu/logs.php');
	echo '</td>';
	echo '<td style="text-align: left; vertical-align: top;">';
	echo '<div class="container" style="background: #fff; border-top: 1px solid  #ccc; border-right: 1px solid  #ccc; border-bottom: 1px solid  #ccc;">';

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
	echo '</div>';
	echo '</td>';
	echo '</tr>';
	echo '</table>';
require_once('footer.php');
