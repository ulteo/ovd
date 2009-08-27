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

if (! checkAuthorization('viewStatus'))
		redirect('index.php');


function parse($str_) {
	$buf = preg_match('/[^-]*\ -\ [^-]*\ -\ ([^-]*)\ -\ .*/', $str_, $matches);
	if (! $buf)
		return false;

	return strtolower($matches[1]);
}

function get_lines_from_file($file_, $nb_lines, $allowed_types) {
	$spec_lines = array();
	// Add false to the array to get mismatch log lines
	// $allowed_types[]= false;
	$max_lines = shell_exec('wc -l '.$file_.'|cut -d " " -f1');

	for($pos=0; count($spec_lines)<$nb_lines && $pos<$max_lines; $pos+=$nb_lines) {
		$cmd = 'tail -n '.($nb_lines+$pos).' '.$file_.' |head -n '.$nb_lines;
		$buf = shell_exec($cmd);
		$lines = explode("\n", $buf);

		$lines = array_reverse($lines);
		foreach($lines as $line) {
			$type = parse($line);
			if (! in_array($type, $allowed_types))
				continue;

			$spec_lines[]= '<span class="'.$type.'">'.trim($line).'</span>';
			if (count($spec_lines)>=$nb_lines)
				break;
		}
	}
	$spec_lines = array_reverse($spec_lines);
	return $spec_lines;
}

function show_all($flags_) {
	$logfiles = glob(SESSIONMANAGER_LOGS.'/*.log');
	$logfiles = array_reverse($logfiles);

	$display = array();
	foreach ($logfiles as $logfile) {
		$lines = get_lines_from_file($logfile, 20, $flags_);
		$display[basename($logfile)] = $lines;
	}


	page_header();
	echo '<h1>'._('Logs').'</h1>';
	echo '<div>';

	foreach ($display as $name => $lines) {
		echo '<h2><a href="?show='.$name.'">'.$name.'</a></h2>';
		echo '<div style="border: 1px solid #ccc; background: #fff; padding: 5px; text-align: left;">';
		echo implode("<br />\n", $lines);
		echo '</div>';
	}

	echo '</div>';
	page_footer();
	die();
}

function show_specific($name_, $flags_) {
	$file = SESSIONMANAGER_LOGS.'/'.$name_;
	if (! file_exists($file)) {
	  popup_error(_('Log file does not exist').' ('.$name_.')');
	  redirect('logs.php');
	}

	$display = array();
	$lines = get_lines_from_file($file, 100, $flags_);

	page_header();
	echo '<h1><a href="?">'._('Logs').'</a> - '.$name_.'</h1>';
	echo '<div>';
	echo '<div style="border: 1px solid #ccc; background: #fff; padding: 5px; text-align: left;">';
	echo implode("<br />\n", $lines);
	echo '</div>';

	echo '</div>';
	page_footer();
	die();
}

$prefs = Preferences::getInstance();
if (is_object($prefs))
	$log_flags = $prefs->get('general', 'log_flags');
else
	$log_flags = array();

if (isset($_GET['show']))
  show_specific($_GET['show'], $log_flags);

show_all($log_flags);
