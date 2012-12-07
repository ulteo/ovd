<?php
/**
 * Copyright (C) 2008-2012 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2008-2010, 2012
 * Author Laurent CLOUET <laurent@ulteo.com> 2008-2010
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2008-2010
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

function show_all($flags_) {
	$display = $_SESSION['service']->log_preview();
	if (is_null($display)) {
		$display = array();
	}
	
	if (array_key_exists('servers', $display)) {
		$display2 = $display['servers'];
		unset($display['servers']);
	}

	page_header();
	echo '<h1>'._('Logs').'</h1>';
	echo '<div>';

	echo '<div class="section">';
	echo '<h1>'._('Session Manager').'</h1>';

	foreach ($display as $name => $lines) {
		if ($name == 'servers') {
			continue;
		}
		
		echo '<h3>'.$name;
		echo ' <a href="?show=1&amp;where=sm&amp;name='.$name.'"><img src="media/image/view.png" width="22" height="22" alt="view" onmouseover="showInfoBulle(\''._('View full log file online').'\'); return false;" onmouseout="hideInfoBulle(); return false;" /></a>';
		echo ' <a href="?download=1&amp;where=sm&amp;name='.$name.'"><img src="media/image/download.png" width="22" height="22" alt="download" onmouseover="showInfoBulle(\''._('Download full log file').'\'); return false;" onmouseout="hideInfoBulle(); return false;" /></a>';
		echo '</h3>';
		echo '<div style="border: 1px solid #ccc; background: #fff; padding: 5px; text-align: left;" class="section">';
		foreach($lines as $line) {
			$type = parse_linux($line);
			echo '<span class="'.$type.'">'.trim($line).'</span>'."<br />\n";
		}
		echo '</div>';
	}
	echo '</div>';

	echo '<div class="section">';
	echo '<h1>'._('Slave Servers').'</h1>';

	foreach ($display2 as $server_id => $logs) {
		$server = $_SESSION['service']->server_info($server_id);
		if (is_null($server)) {
			continue;
		}

		echo '<h2><img src="media/image/server-'.$server->stringType().'.png" alt="'.$server->stringType().'" title="'.$server->stringType().'" /> <a href="servers.php?action=manage&amp;id='.$server_id.'">'.$server->getDisplayName().'</a></h2>';
		echo '<div class="section">';
		echo '<h4>'._('Log');
		echo ' <a href="?show=1&amp;where=aps&amp;server='.$server_id.'"><img src="media/image/view.png" width="22" height="22" alt="view" onmouseover="showInfoBulle(\''._('View full log file online').'\'); return false;" onmouseout="hideInfoBulle(); return false;" /></a>';
		echo ' <a href="?download=1&amp;where=aps&amp;server='.$server_id.'"><img src="media/image/download.png" width="22" height="22" alt="download" onmouseover="showInfoBulle(\''._('Download full log file').'\'); return false;" onmouseout="hideInfoBulle(); return false;" /></a>';
		echo '</h4>';
		echo '<div style="border: 1px solid #ccc; background: #fff; padding: 5px; text-align: left;" class="section">';
		foreach($logs as $line) {
			$type = parse_ovdserver($line);
			echo '<span class="'.$type.'">'.trim($line).'</span>'."<br />\n";
		}
		echo '</div>';
		echo '</div>';
	}
	echo '</div>';

	echo '</div>';
	page_footer();
	die();
}

function show_specific($where_, $name_, $server_=NULL, $flags_) {
	$log = null;
	if ($where_ == 'sm') {
		$log = $name_;
		if (str_endswith($log, '.log')) {
			$log = substr($log, 0, -4);
		}
	}
	elseif ($where_ == 'aps') {
		$log = $server_;
	}
	
	$content = $_SESSION['service']->log_download($log);
	if (is_null($content)) {
		popup_error(sprintf(_('Unable to get log %s'), $log));
		redirect();
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
	echo str_replace("\n", "<br/>\n", $content);
	echo '</div>';

	page_footer();

	die();
}

function download_log($where_, $name_, $server_=NULL) {
	$log = null;
	if ($where_ == 'sm') {
		$download_name = 'sessionmanager_'.$name_;
		
		$log = $name_;
		if (str_endswith($log, '.log')) {
			$log = substr($log, 0, -4);
		}
	}
	elseif ($where_ == 'aps') {
		$download_name = 'slaveserver_'.$server_.'.log';
		$log = $server_;
	}
	
	$content = $_SESSION['service']->log_download($log);
	if (is_null($content)) {
		popup_error(sprintf(_('Unable to get log %s'), $log));
		redirect();
	}
	
	header('Content-Type: application/octet-stream');
	header('Content-Disposition: attachment; filename='.$download_name);
	echo $content;
	die();
}

$log_flags = array();

if (isset($_GET['show']))
	show_specific($_GET['where'], (isset($_GET['name'])?$_GET['name']:NULL), (isset($_GET['server'])?$_GET['server']:NULL), $log_flags);

if (isset($_GET['download']))
	download_log($_GET['where'], (isset($_GET['name'])?$_GET['name']:NULL), (isset($_GET['server'])?$_GET['server']:NULL));

show_all($log_flags);
