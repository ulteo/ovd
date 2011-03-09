<?php
/**
 * Copyright (C) 2008-2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com>
 * Author Laurent CLOUET <laurent@ulteo.com>
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

function parse_ovdserver($str_) {
	$buf = preg_match('/[^\ ]*\ [^\ ]*\ \[(.*)\]:\ .*/', $str_, $matches);
	if (! $buf)
		return false;

	return strtolower($matches[1]);
}

function get_lines_from_file($file_, $nb_lines, $allowed_types) {
	// Add false to the array to get mismatch log lines
	// $allowed_types[]= false;
	$lines = array();

	$obj = new FileTailer($file_);

	$qty = 0;
	while ($obj->hasLines()) {
		$ret = $obj->tail(1);
		$line = $ret[0];

		$type = parse_linux($line);
		if (! in_array($type, $allowed_types))
			continue;

		array_unshift($lines, '<span class="'.$type.'">'.trim($line).'</span>');
		$qty++;

		if ($qty >= $nb_lines)
			break;
	}

	return $lines;
}

function get_lines_from_string($string_, $nb_lines, $allowed_types) {
	$spec_lines = array();

	$lines = explode("\n", $string_);
	$lines = array_reverse($lines);
	foreach($lines as $line) {
		$type = parse_ovdserver($line);

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

	$servers = Abstract_Server::load_all();
	$display2 = array();
	foreach ($servers as $server) {
		$buf = new Server_Logs($server);
		$buf->process();

		$lines = $buf->getLog(20);
		if ($lines !== false)
			$display2[$server->getAttribute('fqdn')] = array();

		if ($lines !== false)
			$display2[$server->getAttribute('fqdn')]['web'] = get_lines_from_string($lines, 20, $flags_);
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
	echo '<h1>'._('Slave Servers').'</h1>';

	foreach ($display2 as $fqdn => $logs) {
		$server = Abstract_Server::load($fqdn);

		echo '<h2><img src="media/image/server-'.$server->stringType().'.png" alt="'.$server->stringType().'" title="'.$server->stringType().'" /> <a href="servers.php?action=manage&amp;fqdn='.$fqdn.'">'.$fqdn.'</a></h2>';
		echo '<div class="section">';
		echo '<h4>'._('Log');
		echo ' <a href="?show=1&amp;where=aps&amp;server='.$fqdn.'"><img src="media/image/view.png" width="22" height="22" alt="view" onmouseover="showInfoBulle(\''._('View full log file online').'\'); return false;" onmouseout="hideInfoBulle(); return false;" /></a>';
		echo ' <a href="?download=1&amp;where=aps&amp;server='.$fqdn.'"><img src="media/image/download.png" width="22" height="22" alt="download" onmouseover="showInfoBulle(\''._('Download full log file').'\'); return false;" onmouseout="hideInfoBulle(); return false;" /></a>';
		echo '</h4>';
		echo '<div style="border: 1px solid #ccc; background: #fff; padding: 5px; text-align: left;" class="section">';
		echo implode("<br />\n", $logs['web']);
		echo '</div>';
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
		echo '<h1><a href="?">'._('Logs').'</a> - '.$server_;
		echo ' <a href="?download=1&amp;where=aps&amp;server='.$server_.'"><img src="media/image/download.png" width="22" height="22" alt="download" onmouseover="showInfoBulle(\''._('Download full log file').'\'); return false;" onmouseout="hideInfoBulle(); return false;" /></a>';
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
		while ($str = $buf->getContent())
			echo $str.'<br />'."\n";
	}
	echo '</div>';

	page_footer();

	die();
}

function download_log($where_, $name_, $server_=NULL) {
	if ($where_ == 'sm') {
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename=sessionmanager_'.$name_);

		$filename = SESSIONMANAGER_LOGS.'/main.log';

		$fp = @fopen($filename, 'r');

		if ($fp !== false)
			while ($str = fgets($fp, 4096))
				echo $str;

		@fclose($fp);
	} elseif ($where_ == 'aps') {
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename=slaveserver_'.$server_.'.log');

		$server = Abstract_Server::load($server_);

		if (! $server) {
			Logger::error('main', '(admin/logs) download_log() - cannot load Server \''.$server_.'\'');
			redirect();
		}

		$buf = new Server_Logs($server);
		$buf->process();

		while ($str = $buf->getContent())
			echo $str;
	}
	
	die();
}

$prefs = Preferences::getInstance();
if (is_object($prefs))
	$log_flags = $prefs->get('general', 'log_flags');
else
	$log_flags = array();

if (isset($_GET['show']))
	show_specific($_GET['where'], (isset($_GET['name'])?$_GET['name']:NULL), (isset($_GET['server'])?$_GET['server']:NULL), $log_flags);

if (isset($_GET['download']))
	download_log($_GET['where'], (isset($_GET['name'])?$_GET['name']:NULL), (isset($_GET['server'])?$_GET['server']:NULL));

show_all($log_flags);
