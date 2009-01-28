<?php
/**
 * Copyright (C) 2008 Ulteo SAS
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
require_once(dirname(__FILE__).'/../../includes/core.inc.php');

class Abstract_Token {
	public function init() {
// 		Logger::debug('main', 'Starting Abstract_Token::init');

		$prefs = Preferences::getInstance();
		if (! $prefs) {
			Logger::critical('get Preferences failed in '.__FILE__.' line '.__LINE__);
			return false;
		}

		$mysql_conf = $prefs->get('general', 'mysql');
		$SQL = MySQL::newInstance($mysql_conf['host'], $mysql_conf['user'], $mysql_conf['password'], $mysql_conf['database']);

		$ret = $SQL->DoQuery(
		'CREATE TABLE IF NOT EXISTS @1 (
		@2 varchar(255) NOT NULL,
		@3 varchar(255) NOT NULL,
		@4 varchar(255) NOT NULL,
		PRIMARY KEY  (`id`)
		)', $mysql_conf['prefix'].'tokens', 'id', 'type', 'session');

		if (! $ret) {
			Logger::error('main', 'Unable to create MySQL table \''.$mysql_conf['prefix'].'tokens\'');
			return false;
		}

		Logger::debug('main', 'MySQL table \''.$mysql_conf['prefix'].'tokens\' created');
		return true;
	}

	public function load($id_) {
// 		Logger::debug('main', 'Starting Abstract_Token::load for \''.$id_.'\'');

		$prefs = Preferences::getInstance();
		if (! $prefs) {
			Logger::critical('get Preferences failed in '.__FILE__.' line '.__LINE__);
			return false;
		}

		$mysql_conf = $prefs->get('general', 'mysql');
		$SQL = MySQL::newInstance($mysql_conf['host'], $mysql_conf['user'], $mysql_conf['password'], $mysql_conf['database']);

		$id = $id_;

		$SQL->DoQuery('SELECT @1,@2 FROM @3 WHERE @4 = %5 LIMIT 1', 'type', 'session', $mysql_conf['prefix'].'tokens', 'id', $id);
		$total = $SQL->NumRows();

		if ($total == 0)
			return false;

		$row = $SQL->FetchResult();

		foreach ($row as $k => $v)
			$$k = $v;
		unset($v);
		unset($k);
		unset($row);

		$buf = new Token($id);
		$buf->type = (string)$type;
		$buf->session = (string)$session;

		return $buf;
	}

	public function save($token_) {
// 		Logger::debug('main', 'Starting Abstract_Token::save for \''.$token_->id.'\'');

		$prefs = Preferences::getInstance();
		if (! $prefs) {
			Logger::critical('get Preferences failed in '.__FILE__.' line '.__LINE__);
			return false;
		}

		$mysql_conf = $prefs->get('general', 'mysql');
		$SQL = MySQL::newInstance($mysql_conf['host'], $mysql_conf['user'], $mysql_conf['password'], $mysql_conf['database']);

		$id = $token_->id;

		if (! Abstract_Token::load($id))
			if (! Abstract_Token::create($token_))
				return false;

		$SQL->DoQuery('UPDATE @1 SET @2=%3,@4=%5 WHERE @6 = %7 LIMIT 1', $mysql_conf['prefix'].'tokens', 'type', $token_->type, 'session', $token_->session, 'id', $id);

		return true;
	}

	private function create($token_) {
// 		Logger::debug('main', 'Starting Abstract_Token::create for \''.$token_->id.'\'');

		$prefs = Preferences::getInstance();
		if (! $prefs) {
			Logger::critical('get Preferences failed in '.__FILE__.' line '.__LINE__);
			return false;
		}

		$mysql_conf = $prefs->get('general', 'mysql');
		$SQL = MySQL::newInstance($mysql_conf['host'], $mysql_conf['user'], $mysql_conf['password'], $mysql_conf['database']);

		$id = $token_->id;

		$SQL->DoQuery('SELECT @1 FROM @2 WHERE @1 = %3 LIMIT 1', 'id', $mysql_conf['prefix'].'tokens', $id);
		$total = $SQL->NumRows();

		if ($total != 0)
			return false;

		$SQL->DoQuery('INSERT INTO @1 (@2) VALUES (%3)', $mysql_conf['prefix'].'tokens', 'id', $id);

		return true;
	}

	public function delete($id_) {
// 		Logger::debug('main', 'Starting Abstract_Token::delete for \''.$id_.'\'');

		$prefs = Preferences::getInstance();
		if (! $prefs) {
			Logger::critical('get Preferences failed in '.__FILE__.' line '.__LINE__);
			return false;
		}

		$mysql_conf = $prefs->get('general', 'mysql');
		$SQL = MySQL::newInstance($mysql_conf['host'], $mysql_conf['user'], $mysql_conf['password'], $mysql_conf['database']);

		$id = $id_;

		$SQL->DoQuery('SELECT @1 FROM @2 WHERE @1 = %3 LIMIT 1', 'id', $mysql_conf['prefix'].'tokens', $id);
		$total = $SQL->NumRows();

		if ($total == 0)
			return false;

		$SQL->DoQuery('DELETE FROM @1 WHERE @2 = %3 LIMIT 1', $mysql_conf['prefix'].'tokens', 'id', $id);

		return true;
	}
}
