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

class Abstract_DAV_User {
	public static function init($prefs_) {
		Logger::debug('main', 'Starting Abstract_DAV_User::init');

		$mysql_conf = $prefs_->get('general', 'mysql');
		$SQL = MySQL::newInstance($mysql_conf['host'], $mysql_conf['user'], $mysql_conf['password'], $mysql_conf['database']);

		$dav_users_table_structure = array(
			'login'			=>	'varchar(255) NOT NULL',
			'password'		=>	'varchar(255) NOT NULL'
		);

		$ret = $SQL->buildTable($mysql_conf['prefix'].'dav_users', $dav_users_table_structure, array('login'));

		if (! $ret) {
			Logger::error('main', 'Unable to create MySQL table \''.$mysql_conf['prefix'].'dav_users\'');
			return false;
		}

		Logger::debug('main', 'MySQL table \''.$mysql_conf['prefix'].'dav_users\' created');
		return true;
	}

	public static function load($login_) {
		Logger::debug('main', 'Starting Abstract_DAV_User::load for \''.$login_.'\'');

		$prefs = Preferences::getInstance();
		if (! $prefs) {
			Logger::critical('main', 'get Preferences failed in '.__FILE__.' line '.__LINE__);
			return false;
		}

		$mysql_conf = $prefs->get('general', 'mysql');
		$SQL = MySQL::newInstance($mysql_conf['host'], $mysql_conf['user'], $mysql_conf['password'], $mysql_conf['database']);

		$login = $login_;

		$SQL->DoQuery('SELECT @1,@2 FROM @3 WHERE @4 = %5 LIMIT 1', 'login', 'password', $mysql_conf['prefix'].'dav_users', 'login', $login);
		$total = $SQL->NumRows();

		if ($total == 0)
			return false;

		$row = $SQL->FetchResult();

		foreach ($row as $k => $v)
			$$k = $v;

		$buf = new DAV_User($login);
		$buf->login = (string)$login;
		$buf->password = (string)$password;

		return $buf;
	}

	public static function save($dav_user_) {
		Logger::debug('main', 'Starting Abstract_DAV_User::save for \''.$dav_user_->login.'\'');

		$prefs = Preferences::getInstance();
		if (! $prefs) {
			Logger::critical('main', 'get Preferences failed in '.__FILE__.' line '.__LINE__);
			return false;
		}

		$mysql_conf = $prefs->get('general', 'mysql');
		$SQL = MySQL::newInstance($mysql_conf['host'], $mysql_conf['user'], $mysql_conf['password'], $mysql_conf['database']);

		$login = $dav_user_->login;

		if (! Abstract_DAV_User::load($login))
			if (! Abstract_DAV_User::create($dav_user_))
				return false;

		$SQL->DoQuery('UPDATE @1 SET @2=%3 WHERE @4 = %5 LIMIT 1', $mysql_conf['prefix'].'dav_users', 'password', $dav_user_->password, 'login', $dav_user_->login);

		return true;
	}

	private static function create($dav_user_) {
		Logger::debug('main', 'Starting Abstract_DAV_User::create for \''.$dav_user_->login.'\'');

		$prefs = Preferences::getInstance();
		if (! $prefs) {
			Logger::critical('main', 'get Preferences failed in '.__FILE__.' line '.__LINE__);
			return false;
		}

		$mysql_conf = $prefs->get('general', 'mysql');
		$SQL = MySQL::newInstance($mysql_conf['host'], $mysql_conf['user'], $mysql_conf['password'], $mysql_conf['database']);

		$login = $dav_user_->login;

		$SQL->DoQuery('SELECT 1 FROM @1 WHERE @2 = %3 LIMIT 1', $mysql_conf['prefix'].'dav_users', 'login', $login);
		$total = $SQL->NumRows();

		if ($total != 0) {
			Logger::error('main', 'Abstract_DAV_User::create user login \''.$login.'\' already exists');
			return false;
		}

		$SQL->DoQuery('INSERT INTO @1 (@2) VALUES (%3)', $mysql_conf['prefix'].'dav_users', 'login', $login);

		return true;
	}

	public static function delete($login_) {
		Logger::debug('main', 'Starting Abstract_DAV_User::delete for \''.$login_.'\'');

		$prefs = Preferences::getInstance();
		if (! $prefs) {
			Logger::critical('main', 'get Preferences failed in '.__FILE__.' line '.__LINE__);
			return false;
		}

		$mysql_conf = $prefs->get('general', 'mysql');
		$SQL = MySQL::newInstance($mysql_conf['host'], $mysql_conf['user'], $mysql_conf['password'], $mysql_conf['database']);

		$login = $login_;

		$SQL->DoQuery('SELECT 1 FROM @1 WHERE @2 = %3 LIMIT 1', $mysql_conf['prefix'].'dav_users', 'login', $login);
		$total = $SQL->NumRows();

		if ($total == 0)
			return false;

		$SQL->DoQuery('DELETE FROM @1 WHERE @2 = %3 LIMIT 1', $mysql_conf['prefix'].'dav_users', 'login', $login);

		return true;
	}
}
