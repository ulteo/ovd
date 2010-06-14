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
if ($tokens) {
	foreach ($tokens as $token) {
		if (! $token->isValid()) {
			Logger::warning('main', '(check) Token \''.$token->id.'\' is no longer valid, deleting');

			if ($token->type == 'start') //Token start Session
				Abstract_Session::delete($token->link_to);
			if ($token->type == 'invite') //Token invite Session
				Abstract_Invite::delete($token->link_to);

			Abstract_Token::delete($token->id);
		}
	}
}

$invites = Invites::getAll();
if ($invites) {
	foreach ($invites as $invite) {
		if (! $invite->isValid()) {
			Logger::warning('main', '(check) Invite \''.$invite->id.'\' is no longer valid, deleting');

			Abstract_Invite::delete($invite->id);
		}
	}
}

$sessions = Abstract_Session::load_all();
if ($sessions) {
	foreach ($sessions as $session) {
		$buf = $session->getStatus();

		if (! $buf || $buf == Session::SESSION_STATUS_WAIT_DESTROY || $buf == Session::SESSION_STATUS_DESTROYED || $buf == Session::SESSION_STATUS_ERROR || $buf == Session::SESSION_STATUS_UNKNOWN) {
			Logger::warning('main', '(check) Session \''.$session->id.'\' is no longer existing, deleting');

			if (! $session->orderDeletion((($buf == Session::SESSION_STATUS_WAIT_DESTROY)?true:false)))
				Logger::error('main', '(check) Unable to delete session \''.$session->id.'\'');
		}
	}
}

Logger::warning('main', '(check) Database purge end');
