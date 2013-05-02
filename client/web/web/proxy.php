<?php
/**
 * Copyright (C) 2010-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Alexandre CONFIANT-LATOUR <a.confiant@ulteo.com> 2010
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

$ALLOWED_SERVICES = array('start', 'session_status', 'logout');
$SERVICE_HEADER = 'X-Ovd-Service';
$service = '';
$session_manager = NULL;

/* Get service type */
$headers = apache_request_headers();
if (is_array($headers) && array_key_exists('X-Ovd-Service', $headers)) {
	$service = $headers['X-Ovd-Service'];
	if ( ! in_array($service, $ALLOWED_SERVICES)) {
		echo 'Bad service requested';
		die();
	}
	$service = $service.'.php';
} else {
	echo 'No service requested';
	die();
}

/* Get the SessionManager object from session or create it */
if (array_key_exists('ovd-client', $_SESSION) && array_key_exists('sessionmanager', $_SESSION['ovd-client'])) {
	$session_manager = $_SESSION['ovd-client']['sessionmanager'];
} else {
	$sm_host = @SESSIONMANAGER_HOST;
  $_SESSION['ovd-client']['sessionmanager_url'] = 'https://'.$sm_host.'/ovd/client';
  $sessionmanager_url = $_SESSION['ovd-client']['sessionmanager_url'];

  $session_manager = new SessionManager($sessionmanager_url);
  $_SESSION['ovd-client']['sessionmanager'] = $session_manager;
}

/* Input data */
$input = file_get_contents('php://input');

/* Query SM */
$sessionmanager = $_SESSION['ovd-client']['sessionmanager'];
$body = $sessionmanager->query_post_xml($service, $input);

/* Print body and return */
header('Content-Type: text/xml; charset=utf-8');
echo $body;
die();
