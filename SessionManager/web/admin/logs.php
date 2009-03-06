<?php
/**
 * Copyright (C) 2008-2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com>
 * Author Jeremy DESVAGES <jeremy@ulteo.com>
 *
 * This program is free software; you can redistribute it and/or 
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; version 2
 * of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 **/
require_once(dirname(__FILE__).'/includes/core.inc.php');
require_once(dirname(__FILE__).'/includes/page_template.php');

define('NBLINES', 25);

function parse($str_) {
	$buf = preg_match('/[^-]*\ -\ [^-]*\ -\ ([^-]*)\ -\ .*/', $str_, $matches);
	if (! $buf)
		return false;

	return strtolower($matches[1]);
}

$prefs = Preferences::getInstance();
if (is_object($prefs))
	$log_flags = $prefs->get('general', 'log_flags');
else
	$log_flags = array();

$logfiles = glob(SESSIONMANAGER_LOGS.'/*.log');
$logfiles = array_reverse($logfiles);

$display = array();
foreach ($logfiles as $logfile) {
	$spec_lines = array();

	for($pos=0, $eof=false; count($spec_lines)<NBLINES && !$eof; $pos+=NBLINES) {
		$cmd = 'tail -n '.(NBLINES+$pos).' '.$logfile.' |head -n '.NBLINES;
		
		$buf = shell_exec($cmd);
		$lines = explode("\n", $buf);
		if (count($lines)<NBLINES)
			$eof = true;
    
		array_reverse($lines);
		foreach($lines as $line) {
			$type = parse($line);
			if (! in_array($type, $log_flags))
				continue;

			$spec_lines[]= '<span class="'.$type.'">'.trim($line).'</span>';
			if (count($spec_lines)>=NBLINES)
				break;
		}
	}
	array_reverse($spec_lines);
	$display[basename($logfile)] = $spec_lines;
}


page_header();
echo '<h1>'._('Logs').'</h1>';
echo '<div>';

foreach ($display as $name => $lines) {
	echo '<h2>'.$name.'</h2>';
	echo '<div style="border: 1px solid #ccc; background: #fff; padding: 5px; text-align: left;">';
	echo implode("\n", $lines);
	echo '</div>';
}

echo '</div>';
page_footer();
