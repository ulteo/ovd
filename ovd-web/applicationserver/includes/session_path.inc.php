<?php
/**
 * Copyright (C) 2008 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com>
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
require_once(dirname(__FILE__).'/core.inc.php');

if (!is_readable('/etc/ulteo-ovd.conf'))
	die('Error reading config file 1a : /etc/ulteo-ovd.conf');

$buf = shell_exec('. /etc/ulteo-ovd.conf && echo $CHROOT');
$buf = trim($buf);

if (!$buf || $buf == '')
	die('Error parsing config file 1b : /etc/ulteo-ovd.conf');

define('CHROOT', $buf);
unset($buf);

$buf = shell_exec('. /etc/ulteo-ovd.conf && echo $LOG_WWW');
$buf = trim($buf);

if (!$buf || $buf == '')
	die('Error parsing config file 1c : /etc/ulteo-ovd.conf');

define('CONNECTME_LOGS', $buf);
unset($buf);

if (!is_readable(CHROOT.'/etc/ulteo-ovd.conf'))
	die('Error reading config file 2 : '.CHROOT.'/etc/ulteo-ovd.conf');

define('PDF_POOL_DIR', CHROOT.'/var/spool/cups2all/');

$buf = shell_exec('SPOOL="/var/spool/applicationserver" && . '.CHROOT.'/etc/ulteo-ovd.conf && echo $SPOOL');
$buf = trim($buf);

if (!$buf || $buf == '')
	die('Error parsing config file 2 : '.CHROOT.'/etc/ulteo-ovd.conf');

define('CHROOT_SPOOL', CHROOT.'/'.$buf);
define('SESSION_PATH', CHROOT_SPOOL.'/sessions');
define('SESSION2CREATE_PATH', CHROOT_SPOOL.'/sessions2create');
define('SPOOL_APT',  CHROOT_SPOOL.'/apt');
define('SPOOL_FILE', CHROOT_SPOOL.'/files');
unset($buf);

$buf = shell_exec('. '.CHROOT.'/etc/ulteo-ovd.conf && echo $SESSION_MANAGER_URL');
$buf = trim($buf);

if (!$buf || $buf == '')
	die('Error parsing config file 3 : '.CHROOT.'/etc/ulteo-ovd.conf');

define('SESSIONMANAGER_URL', $buf);
unset($buf);
