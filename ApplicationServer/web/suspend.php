<?php
/**
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
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

Logger::debug('main', 'Starting suspend.php');

if (! isset($_SESSION['ovd_session']['session']))
  die2(400, 'ERROR - No $session');

if (! isset($_SESSION['ovd_session']['current_token']))
  die2(400, 'ERROR - no current token');

$session = $_SESSION['ovd_session']['session'];
$session_owner = (isset($_SESSION['ovd_session']['owner']) && $_SESSION['ovd_session']['owner']);
$token = $_SESSION['ovd_session']['current_token'];

$session_dir = SESSION_PATH.'/'.$session;

if ($session_owner) {
  $exit_file = $session_dir.'/infos/owner_exit';
  if (! is_file($exit_file))
    @file_put_contents($exit_file, 'suspend');
}

unset($_SESSION['ovd_session']);

die();
