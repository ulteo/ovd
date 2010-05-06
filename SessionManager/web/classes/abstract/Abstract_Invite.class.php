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

class Abstract_Invite {
	public static function init($prefs_) {
		Logger::debug('main', 'Starting Abstract_Invite::init');

		$sql_conf = $prefs_->get('general', 'sql');
		$SQL = SQL::newInstance($sql_conf);

		$invites_table_structure = array(
			'id'			=>	'varchar(255) NOT NULL',
			'session'		=>	'varchar(255) NOT NULL',
			'settings'		=>	'text NOT NULL',
			'email'			=>	'varchar(255) NOT NULL',
			'valid_until'	=>	'int(10) NOT NULL'
		);

		$ret = $SQL->buildTable($sql_conf['prefix'].'invites', $invites_table_structure, array('id'));

		if (! $ret) {
			Logger::error('main', 'Unable to create MySQL table \''.$sql_conf['prefix'].'invites\'');
			return false;
		}

		Logger::debug('main', 'MySQL table \''.$sql_conf['prefix'].'invites\' created');
		return true;
	}

	public static function load($id_) {
		Logger::debug('main', 'Starting Abstract_Invite::load for \''.$id_.'\'');

		$SQL = SQL::getInstance();

		$SQL->DoQuery('SELECT * FROM @1 WHERE @2 = %3 LIMIT 1', $SQL->prefix.'invites', 'id', $id_);
		$total = $SQL->NumRows();

		if ($total == 0) {
			Logger::error('main', "Abstract_Invite::load($id_) failed: NumRows == 0");
			return false;
		}

		$row = $SQL->FetchResult();

		$buf = self::generateFromRow($row);

		return $buf;
	}

	public static function save($invite_) {
		Logger::debug('main', 'Starting Abstract_Invite::save for \''.$invite_->id.'\'');

		$SQL = SQL::getInstance();

		$id = $invite_->id;

		if (! Abstract_Invite::load($id)) {
			Logger::info('main', "Abstract_Invite::save($invite_) unable to load invite, we must create it");

			if (! Abstract_Invite::create($invite_)) {
				Logger::error('main', "Abstract_Invite::save($invite_) create failed");
				return false;
			}
		}

		$SQL->DoQuery('UPDATE @1 SET @2=%3,@4=%5,@6=%7,@8=%9 WHERE @10 = %11 LIMIT 1', $SQL->prefix.'invites', 'session', $invite_->session, 'settings', serialize($invite_->settings), 'email', $invite_->email, 'valid_until', $invite_->valid_until, 'id', $id);

		return true;
	}

	private static function create($invite_) {
		Logger::debug('main', 'Starting Abstract_Invite::create for \''.$invite_->id.'\'');

		$SQL = SQL::getInstance();

		$id = $invite_->id;

		$SQL->DoQuery('SELECT 1 FROM @1 WHERE @2 = %3 LIMIT 1', $SQL->prefix.'invites', 'id', $id);
		$total = $SQL->NumRows();

		if ($total != 0) {
			Logger::error('main', "Abstract_Invite::create($invite_) invite already exist (NumRows == $total)");
			return false;
		}

		$SQL->DoQuery('INSERT INTO @1 (@2) VALUES (%3)', $SQL->prefix.'invites', 'id', $id);

		Abstract_Liaison::save('SessionInvite', $invite_->session, $invite_->id);

		return true;
	}

	public static function delete($id_) {
		Logger::debug('main', 'Starting Abstract_Invite::delete for \''.$id_.'\'');

		$SQL = SQL::getInstance();

		$id = $id_;

		$SQL->DoQuery('SELECT 1 FROM @1 WHERE @2 = %3 LIMIT 1', $SQL->prefix.'invites', 'id', $id);
		$total = $SQL->NumRows();

		if ($total == 0) {
			Logger::error('main', "Abstract_Invite::delete($id_) invite does not exist (NumRows == 0)");
			return false;
		}

		$SQL->DoQuery('DELETE FROM @1 WHERE @2 = %3 LIMIT 1', $SQL->prefix.'invites', 'id', $id);

		Abstract_Liaison::delete('SessionInvite', NULL, $id_);

		return true;
	}

	private static function generateFromRow($row_) {
		foreach ($row_ as $k => $v)
			$$k = $v;

		$buf = new Invite((string)$id);
		$buf->session = (string)$session;
		$buf->settings = unserialize($settings);
		$buf->email = (string)$email;
		$buf->valid_until = (int)$valid_until;

		return $buf;
	}

	public static function load_all() {
		Logger::debug('main', 'Starting Abstract_Invite::load_all');

		$SQL = SQL::getInstance();

		$SQL->DoQuery('SELECT * FROM @1', $SQL->prefix.'invites');
		$rows = $SQL->FetchAllResults();

		$invites = array();
		foreach ($rows as $row) {
			$invite = self::generateFromRow($row);
			if (! is_object($invite))
				continue;

			$invites[] = $invite;
		}

		return $invites;
	}
}
