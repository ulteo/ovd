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
require_once(dirname(__FILE__).'/../../includes/core.inc.php');

class Abstract_Token {
	public static function init($prefs_) {
		Logger::debug('main', 'Starting Abstract_Token::init');

		$mysql_conf = $prefs_->get('general', 'mysql');
		$SQL = MySQL::newInstance($mysql_conf['host'], $mysql_conf['user'], $mysql_conf['password'], $mysql_conf['database']);

		$tokens_table_structure = array(
			'id'			=>	'varchar(255) NOT NULL',
			'type'			=>	'varchar(32) NOT NULL',
			'link_to'		=>	'varchar(255) NOT NULL',
			'valid_until'	=>	'int(10) NOT NULL'
		);

		$ret = $SQL->buildTable($mysql_conf['prefix'].'tokens', $tokens_table_structure, array('id'));

		if (! $ret) {
			Logger::error('main', 'Unable to create MySQL table \''.$mysql_conf['prefix'].'tokens\'');
			return false;
		}

		Logger::debug('main', 'MySQL table \''.$mysql_conf['prefix'].'tokens\' created');
		return true;
	}

	public static function load($id_) {
		Logger::debug('main', 'Starting Abstract_Token::load for \''.$id_.'\'');

		$prefs = Preferences::getInstance();
		if (! $prefs) {
			Logger::critical('main', 'get Preferences failed in '.__FILE__.' line '.__LINE__);
			return false;
		}

		$mysql_conf = $prefs->get('general', 'mysql');
		$SQL = MySQL::newInstance($mysql_conf['host'], $mysql_conf['user'], $mysql_conf['password'], $mysql_conf['database']);

		$id = $id_;

		$SQL->DoQuery('SELECT @1,@2,@3 FROM @4 WHERE @5 = %6 LIMIT 1', 'type', 'link_to', 'valid_until', $mysql_conf['prefix'].'tokens', 'id', $id);
		$total = $SQL->NumRows();

		if ($total == 0)
			return false;

		$row = $SQL->FetchResult();

		foreach ($row as $k => $v)
			$$k = $v;

		$buf = new Token($id);
		$buf->type = (string)$type;
		$buf->link_to = (string)$link_to;
		$buf->valid_until = (int)$valid_until;

		return $buf;
	}

	public static function save($token_) {
		Logger::debug('main', 'Starting Abstract_Token::save for \''.$token_->id.'\'');

		$prefs = Preferences::getInstance();
		if (! $prefs) {
			Logger::critical('main', 'get Preferences failed in '.__FILE__.' line '.__LINE__);
			return false;
		}

		$mysql_conf = $prefs->get('general', 'mysql');
		$SQL = MySQL::newInstance($mysql_conf['host'], $mysql_conf['user'], $mysql_conf['password'], $mysql_conf['database']);

		$id = $token_->id;

		if (! Abstract_Token::load($id))
			if (! Abstract_Token::create($token_))
				return false;

		$SQL->DoQuery('UPDATE @1 SET @2=%3,@4=%5,@6=%7 WHERE @8 = %9 LIMIT 1', $mysql_conf['prefix'].'tokens', 'type', $token_->type, 'link_to', $token_->link_to, 'valid_until', $token_->valid_until, 'id', $id);

		return true;
	}

	private static function create($token_) {
		Logger::debug('main', 'Starting Abstract_Token::create for \''.$token_->id.'\'');

		$prefs = Preferences::getInstance();
		if (! $prefs) {
			Logger::critical('main', 'get Preferences failed in '.__FILE__.' line '.__LINE__);
			return false;
		}

		$mysql_conf = $prefs->get('general', 'mysql');
		$SQL = MySQL::newInstance($mysql_conf['host'], $mysql_conf['user'], $mysql_conf['password'], $mysql_conf['database']);

		$id = $token_->id;

		$SQL->DoQuery('SELECT 1 FROM @1 WHERE @2 = %3 LIMIT 1', $mysql_conf['prefix'].'tokens', 'id', $id);
		$total = $SQL->NumRows();

		if ($total != 0)
			return false;

		$SQL->DoQuery('INSERT INTO @1 (@2) VALUES (%3)', $mysql_conf['prefix'].'tokens', 'id', $id);

		return true;
	}

	public static function delete($id_) {
		Logger::debug('main', 'Starting Abstract_Token::delete for \''.$id_.'\'');

		$prefs = Preferences::getInstance();
		if (! $prefs) {
			Logger::critical('main', 'get Preferences failed in '.__FILE__.' line '.__LINE__);
			return false;
		}

		$mysql_conf = $prefs->get('general', 'mysql');
		$SQL = MySQL::newInstance($mysql_conf['host'], $mysql_conf['user'], $mysql_conf['password'], $mysql_conf['database']);

		$id = $id_;

		$SQL->DoQuery('SELECT 1 FROM @1 WHERE @2 = %3 LIMIT 1', $mysql_conf['prefix'].'tokens', 'id', $id);
		$total = $SQL->NumRows();

		if ($total == 0)
			return false;

		$SQL->DoQuery('DELETE FROM @1 WHERE @2 = %3 LIMIT 1', $mysql_conf['prefix'].'tokens', 'id', $id);

		return true;
	}

	public static function load_all() {
		Logger::debug('main', 'Starting Abstract_Token::load_all');

		$prefs = Preferences::getInstance();
		if (! $prefs) {
			Logger::critical('main', 'get Preferences failed in '.__FILE__.' line '.__LINE__);
			return false;
		}

		$mysql_conf = $prefs->get('general', 'mysql');
		$SQL = MySQL::newInstance($mysql_conf['host'], $mysql_conf['user'], $mysql_conf['password'], $mysql_conf['database']);

		$SQL->DoQuery('SELECT @1 FROM @2', 'id', $mysql_conf['prefix'].'tokens');
		$rows = $SQL->FetchAllResults();

		$tokens = array();
		foreach ($rows as $row) {
			$id = $row['id'];

			$token = Abstract_Token::load($id);
			if (! $token)
				continue;

			$tokens[] = $token;
		}

		return $tokens;
	}
}
