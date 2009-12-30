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


function parse_linux($str_) {
	$buf = preg_match('/[^-]*\ -\ [^-]*\ -\ ([^-]*)\ -\ .*/', $str_, $matches);
	if (! $buf)
		return false;

	return strtolower($matches[1]);
}

function parse_windows($str_) {
	$buf = preg_match('/[^\ ]*\ [^\ ]*\ \[([^\ ]*)\]:\ .*/', $str_, $matches);
	if (! $buf)
		return false;

	return strtolower($matches[1]);
}

function parse_daemon($str_) {
	$buf = preg_match('/[^\ ]*\ \[([^\ ]*)\]\ .*/', $str_, $matches);
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
			$type = parse_linux($line);
			if (! in_array($type, $allowed_types))
				continue;

			$spec_lines[]= '<span class="'.$type.'">'.trim($line).'</span>';
			if (count($spec_lines)>$nb_lines)
				break;
		}
	}
	$spec_lines = array_reverse($spec_lines);
	return $spec_lines;
}

function get_lines_from_string($server_type_, $type_, $string_, $nb_lines, $allowed_types) {
	$spec_lines = array();

	$lines = explode("\n", $string_);
	$lines = array_reverse($lines);
	foreach($lines as $line) {
		if ($type_ == 'web') {
			if ($server_type_ == 'linux')
				$type = parse_linux($line);
			elseif ($server_type_ == 'windows')
				$type = parse_windows($line);
		} elseif ($type_ == 'daemon')
			$type = parse_daemon($line);

		$spec_lines[]= '<span class="'.$type.'">'.trim($line).'</span>';
		if (count($spec_lines)>$nb_lines)
			break;
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

	$servers = Servers::getAll();
	$display2 = array();
	foreach ($servers as $server) {
		$buf = new Server_Logs($server);
		$buf->process();

		$lines = $buf->getWebLog(20);
		$lines2 = false;
		if ($server->getAttribute('type') != 'windows')
			$lines2 = $buf->getDaemonLog(20);
		if ($lines !== false || $lines2 !== false)
			$display2[$server->getAttribute('fqdn')] = array();

		if ($lines !== false)
			$display2[$server->getAttribute('fqdn')]['web'] = get_lines_from_string($server->getAttribute('type'), 'web', $lines, 20, $flags_);

		if ($lines2 !== false)
			$display2[$server->getAttribute('fqdn')]['daemon'] = get_lines_from_string($server->getAttribute('type'), 'daemon', $lines2, 20, $flags_);
	}

	page_header();
	echo '<h1>'._('Logs').'</h1>';
	echo '<div>';

	echo '<div class="section">';
	echo '<h1>'._('Session Manager').'</h1>';

	foreach ($display as $name => $lines) {
		echo '<h3>'.$name;
		echo ' <a href="?show=1&amp;where=sm&amp;name='.$name.'"><img src="media/image/view.png" width="22" height="22" alt="view" onmouseover="showInfoBulle(\''._('View full log file online').'\'); return false;" onmouseout="hideInfoBulle(); return false;" /></a>';
		echo ' <a href="?download=1&amp;where=sm&amp;name='.$name.'"><img src="media/image/download.png" width="22" height="22" alt="download" onmouseover="showInfoBulle(\''._('Download full log file').'\'); return false;" onmouseout="hideInfoBulle(); return false;" /></a>';
		echo '</h3>';
		echo '<div style="border: 1px solid #ccc; background: #fff; padding: 5px; text-align: left;" class="section">';
		echo implode("<br />\n", $lines);
		echo '</div>';
	}
	echo '</div>';

	echo '<div class="section">';
	echo '<h1>'._('Application Servers').'</h1>';

	foreach ($display2 as $fqdn => $logs) {
		$server = Abstract_Server::load($fqdn);

		echo '<h2><img src="media/image/server-'.$server->stringType().'.png" alt="'.$server->stringType().'" title="'.$server->stringType().'" /> <a href="servers.php?action=manage&amp;fqdn='.$fqdn.'">'.$fqdn.'</a></h2>';
		echo '<div class="section">';
		if (array_key_exists('web', $logs)) {
			echo '<h4>Web log';
			echo ' <a href="?show=1&amp;where=aps&amp;name=web&amp;server='.$fqdn.'"><img src="media/image/view.png" width="22" height="22" alt="view" onmouseover="showInfoBulle(\''._('View full log file online').'\'); return false;" onmouseout="hideInfoBulle(); return false;" /></a>';
			echo ' <a href="?download=1&amp;where=aps&amp;name=web&amp;server='.$fqdn.'"><img src="media/image/download.png" width="22" height="22" alt="download" onmouseover="showInfoBulle(\''._('Download full log file').'\'); return false;" onmouseout="hideInfoBulle(); return false;" /></a>';
			echo '</h4>';
			echo '<div style="border: 1px solid #ccc; background: #fff; padding: 5px; text-align: left;" class="section">';
			echo implode("<br />\n", $logs['web']);
			echo '</div>';
		}

		if (array_key_exists('daemon', $logs)) {
			echo '<h4>Daemon log';
			echo ' <a href="?show=1&amp;where=aps&amp;name=daemon&amp;server='.$fqdn.'"><img src="media/image/view.png" width="22" height="22" alt="view" onmouseover="showInfoBulle(\''._('View full log file online').'\'); return false;" onmouseout="hideInfoBulle(); return false;" /></a>';
			echo ' <a href="?download=1&amp;where=aps&amp;name=daemon&amp;server='.$fqdn.'"><img src="media/image/download.png" width="22" height="22" alt="download" onmouseover="showInfoBulle(\''._('Download full log file').'\'); return false;" onmouseout="hideInfoBulle(); return false;" /></a>';
			echo '</h4>';
			echo '<div style="border: 1px solid #ccc; background: #fff; padding: 5px; text-align: left;" class="section">';
			echo implode("<br />\n", $logs['daemon']);
			echo '</div>';
		}
		echo '</div>';
	}
	echo '</div>';

	echo '</div>';
	page_footer();
	die();
}

function show_specific($where_, $name_, $server_=NULL, $flags_) {
	if ($where_ == 'sm')
		$filename = SESSIONMANAGER_LOGS.'/main.log';
	elseif ($where_ == 'aps') {
		$server = Abstract_Server::load($server_);

		if (! $server) {
			Logger::error('main', '(admin/logs) download_log() - cannot load Server \''.$server_.'\'');
			redirect();
		}

		$buf = new Server_Logs($server);
		$buf->process();
	}

	page_header();

	if ($where_ == 'sm') {
		echo '<h1><a href="?">'._('Logs').'</a> - '.$name_;
		echo ' <a href="?download=1&amp;where='.$where_.'&amp;name='.$name_.'&amp;server='.$server_.'"><img src="media/image/download.png" width="22" height="22" alt="download" onmouseover="showInfoBulle(\''._('Download full log file').'\'); return false;" onmouseout="hideInfoBulle(); return false;" /></a>';
		echo '</h1>';
	} elseif ($where_ == 'aps') {
		echo '<h1><a href="?">'._('Logs').'</a> - '.$server_.' - '.$name_;
		echo ' <a href="?download=1&amp;where=aps&amp;name='.$name_.'&amp;server='.$server_.'"><img src="media/image/download.png" width="22" height="22" alt="download" onmouseover="showInfoBulle(\''._('Download full log file').'\'); return false;" onmouseout="hideInfoBulle(); return false;" /></a>';
		echo '</h1>';
	}

	echo '<div style="border: 1px solid #ccc; background: #fff; padding: 5px; text-align: left;" class="section">';
	if ($where_ == 'sm') {
		$fp = @fopen($filename, 'r');
		if ($fp !== false) {
			while ($str = fgets($fp, 4096))
				echo $str.'<br />'."\n";
			fclose($fp);
		}
	} elseif ($where_ == 'aps') {
		while ($str = $buf->getContent($name_))
			echo $str.'<br />'."\n";
	}
	echo '</div>';

	page_footer();

	die();
}

function download_log($where_, $name_, $server_=NULL) {
	if ($where_ == 'sm') {
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename=sm_'.$name_);

		$filename = SESSIONMANAGER_LOGS.'/main.log';
	} elseif ($where_ == 'aps') {
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename=aps_'.$server_.'_'.$name_.'.log');

		$server = Abstract_Server::load($server_);

		if (! $server) {
			Logger::error('main', '(admin/logs) download_log() - cannot load Server \''.$server_.'\'');
			redirect();
		}

		$buf = new Server_Logs($server);
		$buf->process();
	}
	
	while ($str = $buf->getContent($name_))
		echo $str;

	die();
}

$prefs = Preferences::getInstance();
if (is_object($prefs))
	$log_flags = $prefs->get('general', 'log_flags');
else
	$log_flags = array();

if (isset($_GET['show']))
	show_specific($_GET['where'], $_GET['name'], (isset($_GET['server'])?$_GET['server']:NULL), $log_flags);

if (isset($_GET['download']))
	download_log($_GET['where'], $_GET['name'], (isset($_GET['server'])?$_GET['server']:NULL));

show_all($log_flags);
