<?php
/**
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
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
require_once(dirname(__FILE__).'/../includes/core.inc.php');

Logger::debug('main', 'Starting webservices/server_log.php');

if (! isSessionManagerRequest()) {
	Logger::error('main', 'Request not coming from Session Manager');
	header('HTTP/1.1 400 Bad Request');
	die('ERROR - Request not coming from Session Manager');
}

function get_time_from_logline($logline_, $type_) {
	if ($type_ == 'web') {
		$buf = explode(' - ', $logline_, 2);
		if (count($buf) != 2)
			return false;

		$buf = strptime($buf[0], '%b %e %T');
		if (! $buf)
			return false;

		$buf['tm_year'] = (int)((int)date('Y')-1900); //We should add the year in linux ApS web log (and in SM web log too...)
	} elseif ($type_ == 'daemon') {
		$buf = explode(' ', $logline_, 2);
		if (count($buf) != 2)
			return false;

		$buf = strptime($buf[0], '%F-%T');
		if (! $buf)
			return false;
	}

	$buf = mktime((int)$buf['tm_hour'], (int)$buf['tm_min'], (int)$buf['tm_sec'], (int)$buf['tm_mon'], (int)$buf['tm_mday'], (int)((int)$buf['tm_year']+1900));
	return $buf;
}

function parse_content($lines_, $since_, $last_, $type_) {
	if (! is_array($lines_))
		return array();

	$len = count($lines_);

	$loglines = array();
	$more = array();
	for ($i = ($len-1); $i >= 0; $i--) {
		$time = get_time_from_logline($lines_[$i], $type_);
		if (! $time) {
			$more[] = $lines_[$i];
			continue;
		}

		if ($time >= $last_) {
			$more = array();
			continue;
		}

		if ($time < $since_)
			break;

		if (count($more) > 0) {
			foreach ($more as $buf)
				$loglines[] = $buf;
			$more = array();
		}

		$loglines[] = $lines_[$i];
	}
	$loglines = array_reverse($loglines);
	$loglines = implode("\n", $loglines);

	return $loglines;
}

$log_content = @file_get_contents(APS_LOGS.'/main.log');
$lines = explode("\n", $log_content);
array_pop($lines); //last element is an empty one
$last = 0;
for ($i = (count($lines)-1); $i >= 0; $i--) {
	$last = get_time_from_logline($lines[$i], 'web');

	if ($last !== false)
		break;
}
$web_loglines = parse_content($lines, (int)$_REQUEST['since'], $last, 'web');

$log_content = @file_get_contents(CHROOT.'/var/log/ulteo-ovd.log');
$lines = explode("\n", $log_content);
array_pop($lines); //last element is an empty one
$daemon_loglines = parse_content($lines, (int)$_REQUEST['since'], $last, 'daemon');

header('Content-Type: text/xml; charset=utf-8');

$dom = new DomDocument('1.0', 'utf-8');
$log_node = $dom->createElement('log');
$log_node->setAttribute('since', (int)$_REQUEST['since']);
$log_node->setAttribute('last', $last);
$log_web_node = $dom->createElement('web');
$log_web_textnode = $dom->createCDATASection(base64_encode($web_loglines));
$log_web_node->appendChild($log_web_textnode);
$log_node->appendChild($log_web_node);
$log_daemon_node = $dom->createElement('daemon');
$log_daemon_textnode = $dom->createCDATASection(base64_encode($daemon_loglines));
$log_daemon_node->appendChild($log_daemon_textnode);
$log_node->appendChild($log_daemon_node);
$dom->appendChild($log_node);

$xml = $dom->saveXML();

echo $xml;

die();
