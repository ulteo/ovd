<?php
/**
 * Copyright (C) 2008 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com>
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

Logger::debug('main', 'Starting webservices/apt-get.php');

if (! isSessionManagerRequest()) {
	Logger::error('main', 'Request not coming from Session Manager');
	header('HTTP/1.1 400 Bad Request');
	die('ERROR - Request not coming from Session Manager');
}

function do_request() {
  if (! isset($_GET['request']))
    die2(401, 'no "request" GET argument');

  $request = $_GET['request'];
  $job = md5(time().rand());
  $job_file = SPOOL_APT.'/'.$job;

  if (! is_writable2($job_file))
    die2(500, '"'.$job_file.'" is not writable');

  if (! file_put_contents($job_file, $request))
    die2(500, 'Error when write "'.$job_file.'"');

  die($job);
}

/**
 * job=$id
 *
 * -3: server error
 * -2: error install
 * -1: not exist
 *  0: spooling
 *  1: in progress
 *  2: finished
 *
 **/
function do_status() {
  if (! isset($_GET['job']))
    die2(401, 'no job GET argument');

  $filename = SPOOL_APT.'/'.$_GET['job'];
  if (is_file($filename))
    die('0');

  if (! is_dir($filename))
    die('-1');

  $filename.= '/status';
   if (! is_readable($filename))
     die('-3');

   $content = file_get_contents($filename);
   if (strlen($content) == 0)
     die('1');

   if ($content != '0')
     die('-2');

   die('2');
}

function do_show() {
  if (! isset($_GET['job']))
    die2(401, 'no "job" GET argument');

  if (! isset($_GET['show']))
    die2(401, 'no "show" GET argument');

  switch($_GET['show']) {
  case 'status': break;
  case 'stderr': break;
  case 'stdout': break;
  default:
    die2(401, 'Invalid show='.$_GET['show'].' argument');
  }

  $filename = SPOOL_APT.'/'.$_GET['job'].'/'.$_GET['show'];
  if (! is_file($filename))
    die2(500, 'no stdout file');

  if (! is_readable($filename))
    die2(500, 'not readable stdout file');

  header('Content-Type: text/plain');
  readfile($filename);
  die();
}

if (! isset($_GET['action']))
  die2(401, 'Missing "action" argument');

$action = $_GET['action'];
switch($action) {
 case 'request':
   do_request();
   break;
 case 'status':
   do_status();
   break;
 case 'show':
   do_show();
   break;
 default:
   die2(401, 'Invalid "action='.$action.'" argument');
}

die();
