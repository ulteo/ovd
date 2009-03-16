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

require_once(dirname(__FILE__).'/includes/core.inc.php');

Logger::debug('main', 'Starting webservices/session_status.php');

function getSessionStatus($session) {
  if (file_exists(SESSION2CREATE_PATH.'/'.$session))
    return -1;

  if (! is_readable(SESSION_PATH.'/'.$session.'/infos/status'))
    return false;

  $status = get_from_file(SESSION_PATH.'/'.$session.'/infos/status');
  if ($status === false)
    return false;

  return $status;
}

// Begin Printing functions
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
// End Printing functions


// Begin Sharing function
function share_parse_actives($dir_) {
  $p = glob($dir_.'/*');
  $shares = array();

  foreach($p as $d) {
    if (! is_dir($d))
      continue;

    $info = array();
    $info['token'] = basename($d);
    $info['created'] = filemtime($d);
    $info['email'] = file_get_contents($d.'/email');
    $info['mode']  = file_get_contents($d.'/mode');
    $info['joined'] = (is_file($d.'/alive'))?1:0;
    $info['alive'] = 0;
    if (! file_exists($d.'/left')
     && file_exists($d.'/alive')
     && (time() - filemtime($d.'/alive')) < 15)
      $info['alive'] = 1;

    if (file_exists($d.'/alive') || (filemtime($d.'/email') > (time()-(60*30))))
      $shares[] = $info;
  }
  usort($shares, "share_cmp");
  return $shares;
}

function share_cmp($o1, $o2) {
	if ($o1['created'] < $o2['created'])
		return -1;
	if ($o1['created'] > $o2['created'])
		return 1;
	return 0;
}

function share_refresh_alive($dir_) {
  if (! is_dir($dir_))
    return false;

  @touch($dir_.'/alive');
  return true;
}
// End Sharing function



if (! isset($_SESSION['session']))
  die2(400, 'ERROR - No $session');

$session = $_SESSION['session'];
$session_owner = (isset($_SESSION['owner']) && $_SESSION['owner']);


$dom = new DomDocument();
$session_node = $dom->createElement('session');
$dom->appendChild($session_node);


$status = getSessionStatus($session);
if ($status === false)
  die2(400, 'ERROR - Unknown session');

$session_node->setAttribute('status', $status);

// Only if session is running
if ($status == 2) {
  $session_dir = SESSION_PATH.'/'.$session;

  // KMA
  if ($session_owner)
    if (file_exists($session_dir.'/infos/keepmealive'))
      @touch($session_dir.'/infos/keepmealive');


  // Check print file
  $r = getNextPrintFile($session, $_SESSION['print_timestamp']);
  if ($r !== false) {
    $item = $dom->createElement('print');
    $item->setAttribute('path', $r[2]);
    $item->setAttribute('time', $r[1]);
    $session_node->appendChild($item);

    $_SESSION['print_timestamp'] = $r[1] +1;
  }

  // Check Sharing
  $share_dir = $session_dir.'/infos/share';
  if (! is_dir($share_dir)) {
    if (! mkdir($share_dir))
      Logger::error('main', 'Unable to create direcotry '.$share_dir);
  }

  if ($session_owner) {
    $shares = share_parse_actives($share_dir);

    $item = $dom->createElement('sharing');
    $item->setAttribute('count', count($shares));

    foreach($shares as $share) {
      $t = $dom->createElement('share');
      $t->setAttribute('email',  $share['email']);
      $t->setAttribute('mode',   $share['mode']);
      $t->setAttribute('joined', $share['joined']);
      $t->setAttribute('alive',  $share['alive']);
      $item->appendChild($t);
    }

    $session_node->appendChild($item);
  }
  else {
    if (! isset($_SESSION['current_token']))
      die2(400, 'ERROR - no current token');

    $file = $share_dir.'/'.$_SESSION['current_token'];
    share_refresh_alive($file);
  }
}

$xml = $dom->saveXML();

header('Content-Type: text/xml');
echo $xml;
