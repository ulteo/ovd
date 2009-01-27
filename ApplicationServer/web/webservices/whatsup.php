<?php
/**
 * Copyright (C) 2009 Ulteo SAS
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

Logger::debug('main', 'Starting webservices/session_status.php');

if (! isset($_SESSION['session']))
  die2(400, 'ERROR - No $session');

$session = $_SESSION['session'];

$dom = new DomDocument();
$session_node = $dom->createElement('session');
$dom->appendChild($session_node);

if (file_exists(SESSION2CREATE_PATH.'/'.$session)) {
	Logger::info('main', 'Session is being created : '.$session);
	die2(400, '-1');

}

if (!is_readable(SESSION_PATH.'/'.$session.'/infos/status')) {
	Logger::error('main', 'No such session : '.$session);
	die2(400, '4');
}

$status = get_from_file(SESSION_PATH.'/'.$session.'/infos/status');
if ($status === false)
	$status = -2;
$session_node->setAttribute('status', $status);

if (isset($_SESSION['owner']) && $_SESSION['owner'])
	if (file_exists(SESSION_PATH.'/'.$session.'/infos/keepmealive'))
		@touch(SESSION_PATH.'/'.$session.'/infos/keepmealive');


// Printing
function getPathFromMagic($magic) {
  $buf = SESSION_PATH.'/'.$magic.'/parameters/user_login';
  if (!is_readable($buf))
    return;
  $login = trim(file_get_contents($buf));

  return PDF_POOL_DIR.$login;
}

function getNextFile($basetime, $dir) {
  $files = glob("$dir/*.pdf");
  foreach ($files as $filename) {
    $file = basename ($filename);
    $ar = split("-", $file, 2);
    if (count($ar) == 0)
      continue;

    if (! is_numeric($ar[0]))
      continue;

    if ($ar[0] < $basetime)
      continue;

    return array($filename,$ar[0],$ar[1]);
  }

  return false;
}

function getNextPrintFile($session, $time) {
  $dir = getPathFromMagic($session);
  if (! isset($dir) || $dir === '')
    return false;

  $r = getNextFile($time, $dir);
  if ($r === false || ! is_readable($r[0]))
    return false;
    
  return $r;
}

if ($status == 2) {
  $r = getNextPrintFile($session, $_SESSION['print_timestamp']);
  if ($r !== false) {
    $item = $dom->createElement('print');
    $item->setAttribute('path', $r[2]);
    $item->setAttribute('time', $r[1]);
    $session_node->appendChild($item);
      
    $_SESSION['print_timestamp'] = $r[1] +1;
  }
}


// Sharing
function count_active_share($share_dir) {
  $p = glob($share_dir.'/*');
  foreach($p as $f) {
    if (time() - filemtime($f) > 10)
      @unlink($f);
  }

  $p = glob($share_dir.'/*');
  return count($p);
}

$session_dir = SESSION_PATH.'/'.$session.'/infos';
$share_dir = $session_dir.'/share';
if (! is_dir($share_dir)) {
  if (! mkdir($share_dir))
    die2(400, 'ERROR - unable to create dir');
}

if (isset($_SESSION['owner']) && $_SESSION['owner']) {
  $e = count_active_share($share_dir);

  $item = $dom->createElement('sharing');
  $item->setAttribute('count', $e);
  $session_node->appendChild($item);
}
else {
  if (! isset($_SESSION['current_token']))
    die2(400, 'ERROR - no current token');

  $file = $share_dir.'/'.$_SESSION['current_token'];
  @touch($file);
}


$xml = $dom->saveXML();

header('Content-Type: text/xml');
echo $xml;
