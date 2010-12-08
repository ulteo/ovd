<?php
/**
 * Copyright (C) 2009-2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2009
 * Author Julien LANGLOIS <julien@ulteo.com> 2010
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
require_once(dirname(__FILE__).'/../includes/core-minimal.inc.php');

Logger::debug('main', 'Starting ajax/installable_applications.php');

if (isset($_REQUEST['task']))
	do_refresh_task($_REQUEST['task']);
elseif (isset($_GET['fqdn']))
	do_create_task($_GET['fqdn']);
else {
	Logger::error('main', '(ajax/installable_applications) Missing parameter : fqdn or task');
	die('ERROR - NO $_GET[\'fqdn\'] or $_GET[\'task\']');
}

function do_create_task($fqdn_) {
	$server = Abstract_Server::load($fqdn_);
	if (! is_object($server)) {
		Logger::error('main', '(ajax/installable_applications) Server '.$fqdn_.' not found');

		header('Content-Type: text/xml; charset=utf-8');
		$dom = new DomDocument('1.0', 'utf-8');

		$node = $dom->createElement('usage');
		$node->setAttribute('status', 'server not found');
		$dom->appendChild($node);

		die($dom->saveXML());
	}
	
	$task = new Task_available_applications('', $fqdn_);
	$manager = new Tasks_Manager();
	$manager->add($task);
	
	header('Content-Type: text/xml; charset=utf-8');
	$dom = new DomDocument('1.0', 'utf-8');

	$node = $dom->createElement('task');
	$node->setAttribute('id', $task->id);
	$dom->appendChild($node);

	die($dom->saveXML());
}

function do_refresh_task($task_id_) {
	$task = Abstract_Task::load($task_id_);
	if (! is_object($task)) {
		Logger::error('main', '(ajax/installable_applications) Task '.$task_id_.' not found');

		header('Content-Type: text/xml; charset=utf-8');
		$dom = new DomDocument('1.0', 'utf-8');

		$node = $dom->createElement('usage');
		$node->setAttribute('status', 'task not found');
		$dom->appendChild($node);

		die($dom->saveXML());
	}
	
	$task->refresh();
	
	if (! $task->succeed()) {
		header('Content-Type: text/xml; charset=utf-8');
		$dom = new DomDocument('1.0', 'utf-8');

		$node = $dom->createElement('task');
		$node->setAttribute('status', $task->status);
		$dom->appendChild($node);

		die($dom->saveXML());
	}

	$ret = $task->get_AllInfos();

	header('Content-Type: text/xml; charset=utf-8');
	die($ret['stdout']);
}
