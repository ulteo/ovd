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

Logger::debug('main', 'Starting webservices/put_sourceslist.php');

if (! isSessionManagerRequest()) {
	Logger::error('main', 'Request not coming from Session Manager');
	header('HTTP/1.1 400 Bad Request');
	die('ERROR - Request not coming from Session Manager');
}

function save_file($upload, $dest_file) {
  if($upload['error']) {
    switch ($upload['error']) {
    case 1:
      die2(500, 'Oversized file for server rules');
      break;
    case 3:
      die2(500, 'The file was corrupted while upload');
      break;
    case 4:
      die2(500, 'Null file size');
      break;
    }

    die2(500, 'Problem while uploading file');
  }

  if (! is_readable($upload['tmp_name']))
    die2(500, 'File not readable');

  if (! ((is_file($dest_file) && is_writable($dest_file))
	 ||  is_writable(dirname($dest_file))))
    die2(500, '"'.$dest_file.'" is not writable');

  if (! move_uploaded_file($upload['tmp_name'], $dest_file))
    die2(500, 'Unable to move uploaded file');
}

if (! isset($_FILES['sourceslist']))
  die2(400, 'no sourceslist file');

save_file($_FILES['sourceslist'], SPOOL_FILE.'/sources.list');
die();
