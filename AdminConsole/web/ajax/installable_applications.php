<?php
/**
 * Copyright (C) 2009-2012 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2009
 * Author Julien LANGLOIS <julien@ulteo.com> 2010, 2012
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
require_once(dirname(dirname(__FILE__)).'/../includes/core.inc.php');

if (isset($_REQUEST['task']))
	do_refresh_task($_REQUEST['task']);
elseif (isset($_GET['server']))
	do_create_task($_GET['server']);
else {
	die('ERROR - NO $_GET[\'server\'] or $_GET[\'task\']');
}

function do_create_task($server_id_) {
	$ret = $_SESSION['service']->task_debian_installable_application($server_id_);
	if (! $ret) {
		header('Content-Type: text/xml; charset=utf-8');
		$dom = new DomDocument('1.0', 'utf-8');

		$node = $dom->createElement('usage');
		$node->setAttribute('status', 'server not found');
		$dom->appendChild($node);

		die($dom->saveXML());
	}
	
	header('Content-Type: text/xml; charset=utf-8');
	$dom = new DomDocument('1.0', 'utf-8');

	$node = $dom->createElement('task');
	$node->setAttribute('id', $ret);
	$dom->appendChild($node);

	die($dom->saveXML());
}

function do_refresh_task($task_id_) {
	$task = $_SESSION['service']->task_info($task_id_);
	if (! is_object($task)) {
		header('Content-Type: text/xml; charset=utf-8');
		$dom = new DomDocument('1.0', 'utf-8');

		$node = $dom->createElement('usage');
		$node->setAttribute('status', 'task not found');
		$dom->appendChild($node);

		die($dom->saveXML());
	}
	
	if (! $task->succeed()) {
		header('Content-Type: text/xml; charset=utf-8');
		$dom = new DomDocument('1.0', 'utf-8');

		$node = $dom->createElement('task');
		$node->setAttribute('status', $task->status);
		$dom->appendChild($node);

		die($dom->saveXML());
	}

	$ret = $task->getAttribute('infos');

	header('Content-Type: text/xml; charset=utf-8');
	die($ret['stdout']);
}
