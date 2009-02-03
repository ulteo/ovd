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

Logger::warning('main', '(check) Database purge start');

$tokens = Tokens::getAll();
foreach ($tokens as $token) {
	if (! $token->isValid()) {
		Logger::warning('main', '(check) Token \''.$token->id.'\' is no longer valid, deleting');

		if ($token->type == 'start') //Token start Session
			Abstract_Session::delete($token->link_to);

		Abstract_Token::delete($token->id);
	}
}

$sessions = Sessions::getAll();
foreach ($sessions as $session) {
	$buf = $session->getStatus();

	if (! $buf || (int)$buf == 4) {
		Logger::warning('main', '(check) Session \''.$session->id.'\' is no longer existing, deleting');

		Abstract_Session::delete($session->id);
	}
}

Logger::warning('main', '(check) Database purge end');
