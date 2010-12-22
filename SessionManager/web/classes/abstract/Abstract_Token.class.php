<?php
/**
 * Copyright (C) 2009-2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2010
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2009
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

		$sql_conf = $prefs_->get('general', 'sql');
		$SQL = SQL::newInstance($sql_conf);

		$tokens_table_structure = array(
			'id'			=>	'varchar(255) NOT NULL',
			'type'			=>	'varchar(32) NOT NULL',
			'link_to'		=>	'varchar(255) NOT NULL',
			'valid_until'	=>	'int(10) NOT NULL'
		);

		$ret = $SQL->buildTable($sql_conf['prefix'].'tokens', $tokens_table_structure, array('id'));

		if (! $ret) {
			Logger::error('main', 'Unable to create MySQL table \''.$sql_conf['prefix'].'tokens\'');
			return false;
		}

		Logger::debug('main', 'MySQL table \''.$sql_conf['prefix'].'tokens\' created');
		return true;
	}

	public static function exists($id_) {
		Logger::debug('main', 'Starting Abstract_Token::exists for \''.$id_.'\'');

		$SQL = SQL::getInstance();

		$SQL->DoQuery('SELECT 1 FROM @1 WHERE @2 = %3 LIMIT 1', $SQL->prefix.'tokens', 'id', $id_);
		$total = $SQL->NumRows();

		if ($total == 0)
			return false;

		return true;
	}

	public static function load($id_) {
		Logger::debug('main', 'Starting Abstract_Token::load for \''.$id_.'\'');

		$SQL = SQL::getInstance();

		$SQL->DoQuery('SELECT * FROM @1 WHERE @2 = %3 LIMIT 1', $SQL->prefix.'tokens', 'id', $id_);
		$total = $SQL->NumRows();

		if ($total == 0) {
			Logger::error('main', "Abstract_Token::load($id_) failed: NumRows == 0");
			return false;
		}

		$row = $SQL->FetchResult();

		$buf = self::generateFromRow($row);

		return $buf;
	}

	public static function save($token_) {
		Logger::debug('main', 'Starting Abstract_Token::save for \''.$token_->id.'\'');

		$SQL = SQL::getInstance();

		$id = $token_->id;

		if (! Abstract_Token::exists($id)) {
			Logger::debug('main', "Abstract_Token::save($token_) token does NOT exist, we must create it");

			if (! Abstract_Token::create($token_)) {
				Logger::error('main', "Abstract_Token::save($token_) Abstract_Token::create failed");
				return false;
			}
		}

		$SQL->DoQuery('UPDATE @1 SET @2=%3,@4=%5,@6=%7 WHERE @8 = %9 LIMIT 1', $SQL->prefix.'tokens', 'type', $token_->type, 'link_to', $token_->link_to, 'valid_until', $token_->valid_until, 'id', $id);

		return true;
	}

	private static function create($token_) {
		Logger::debug('main', 'Starting Abstract_Token::create for \''.$token_->id.'\'');

		if (Abstract_Token::exists($token_->id)) {
			Logger::error('main', 'Abstract_Token::create(\''.$token_->id.'\') token already exists');
			return false;
		}

		$SQL = SQL::getInstance();
		$SQL->DoQuery('INSERT INTO @1 (@2) VALUES (%3)', $SQL->prefix.'tokens', 'id', $token_->id);

		return true;
	}

	public static function delete($id_) {
		Logger::debug('main', 'Starting Abstract_Token::delete for \''.$id_.'\'');

		$SQL = SQL::getInstance();

		$id = $id_;

		$SQL->DoQuery('SELECT 1 FROM @1 WHERE @2 = %3 LIMIT 1', $SQL->prefix.'tokens', 'id', $id);
		$total = $SQL->NumRows();

		if ($total == 0) {
			Logger::error('main', "Abstract_Token::delete($id_) token does not exist (NumRows == 0)");
			return false;
		}

		$SQL->DoQuery('DELETE FROM @1 WHERE @2 = %3 LIMIT 1', $SQL->prefix.'tokens', 'id', $id);

		return true;
	}

	private static function generateFromRow($row_) {
		foreach ($row_ as $k => $v)
			$$k = $v;

		$buf = new Token((string)$id);
		$buf->type = (string)$type;
		$buf->link_to = (string)$link_to;
		$buf->valid_until = (int)$valid_until;

		return $buf;
	}

	public static function load_all() {
		Logger::debug('main', 'Starting Abstract_Token::load_all');

		$SQL = SQL::getInstance();

		$SQL->DoQuery('SELECT * FROM @1', $SQL->prefix.'tokens');
		$rows = $SQL->FetchAllResults();

		$tokens = array();
		foreach ($rows as $row) {
			$token = self::generateFromRow($row);
			if (! is_object($token))
				continue;

			$tokens[] = $token;
		}

		return $tokens;
	}

	public static function load_by_session($ssesion_id_) {
		Logger::debug('main', 'Starting Abstract_Token::load_by_session('.$ssesion_id_.')');

		$SQL = SQL::getInstance();

		$SQL->DoQuery('SELECT * FROM @1 WHERE @2 = %3', $SQL->prefix.'tokens', 'link_to', $ssesion_id_);
		$rows = $SQL->FetchAllResults();

		$tokens = array();
		foreach ($rows as $row) {
			$token = self::generateFromRow($row);
			if (! is_object($token))
				continue;

			$tokens[] = $token;
		}

		return $tokens;
	}
}
