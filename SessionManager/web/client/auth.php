<?php
/**
 * Copyright (C) 2011 Ulteo SAS
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
require_once(dirname(__FILE__).'/../includes/core.inc.php');

define('AUTH_FAILED', 'auth_failed');
define('IN_MAINTENANCE', 'in_maintenance');
define('INTERNAL_ERROR', 'internal_error');
define('INVALID_USER', 'invalid_user');
define('SERVICE_NOT_AVAILABLE', 'service_not_available');
define('UNAUTHORIZED_SESSION_MODE', 'unauthorized_session_mode');
define('USER_WITH_ACTIVE_SESSION', 'user_with_active_session');

function throw_response($response_code_) {
	Logger::error('main', '(client/start) throw_response(\''.$response_code_.'\')');

	header('Content-Type: text/xml; charset=utf-8');

	$dom = new DomDocument('1.0', 'utf-8');

	$response_node = $dom->createElement('response');
	$response_node->setAttribute('code', $response_code_);
	$dom->appendChild($response_node);

	echo $dom->saveXML();

	die();
}

$sessionManagement = SessionManagement::getInstance();
if (! $sessionManagement->initialize()) {
	Logger::error('main', '(client/auth) SessionManagement initialization failed');
	throw_response(INTERNAL_ERROR);
}

if (! $sessionManagement->parseClientRequest(@file_get_contents('php://input'))) {
	Logger::error('main', '(client/auth) Client does not send a valid XML');
	throw_response(INTERNAL_ERROR);
}

if (! $sessionManagement->authenticate()) {
	Logger::error('main', '(client/auth) Authentication failed');
	throw_response(AUTH_FAILED);
}

$_SESSION['user_login'] = $sessionManagement->user->getAttribute('login');
