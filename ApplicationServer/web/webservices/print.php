<?php
/**
 * Copyright (C) 2008 Ulteo SAS
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
require_once(dirname(__FILE__).'/../includes/core.inc.php');

Logger::debug('main', 'Starting webservices/print.php');

if (!isset($_SESSION['session'])) {
	Logger::error('main', 'Request not coming from Application Server');
	header('HTTP/1.1 400 Bad Request');
	die('ERROR - Request not coming from Application Server');
}

function isTimestamp($str) {
  if (!is_numeric($str))
    return false;
  return true;
}

function getPathFromMagic($magic) {
  $buf = SESSION_PATH.'/'.$magic.'/user_login';
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

    if (!isTimestamp($ar[0]))
      continue;

    if ($ar[0] < $basetime)
      continue;

    return array($filename,$ar[0],$ar[1]);
  }

  return false;
}

$isaget = false;

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
     $isaget = true;
}

if (!(isset($_SESSION['session']))) {
  return ret400("no magic arg");
}

if (!isset($_GET["timestamp"]))
  return ret400("pas de time");

if (!is_numeric($_GET["timestamp"]))
  return ret400("invalid arg time");

$dir = getPathFromMagic($_SESSION['session']);
if (! (isset($dir) && $dir !== ''))
  return ret400("unknown user");

$ts = $_GET["timestamp"];

$r = getNextFile($ts, $dir);
if ($r == false) {
  return ret404("no file");
}

$path = $r[0];
$nextts = $r[1]+1;
$filename = $r[2];

header("UlteoPrintTime: $nextts");
header("UlteoFileName: $filename");

if (!is_readable($path)) {
  return ret404("access err");
}

if ($isaget) {
  $file = fopen($path, 'r');
  if (! $file)
    return ret404("can't open file in read mode");

  $buffer = '';
  while (!feof($file))
    $buffer .= fgets($file, 4096);
  fclose($file);

  //header('HTTP/1.1 200 OK');
  header('Content-Type: application/pdf');
  header('Content-Disposition: attachment; filename='.$filename);

  echo $buffer;
}
