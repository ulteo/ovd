<?php
/**
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Jeremy DESVAGES <jeremy@ulteo.com>
 * Author Laurent CLOUET <laurent@ulteo.com>
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

class Abstract_News {
	public static function init($prefs_) {
		Logger::debug('main', 'Starting Abstract_News::init');

		$mysql_conf = $prefs_->get('general', 'mysql');
		$SQL = MySQL::newInstance($mysql_conf['host'], $mysql_conf['user'], $mysql_conf['password'], $mysql_conf['database'], $mysql_conf['prefix']);

		$news_table_structure = array(
			'id'			=>	'int(8) NOT NULL auto_increment',
			'title'			=>	'varchar(64) NOT NULL',
			'content'		=>	'text NOT NULL',
			'timestamp'		=>	'int(10) NOT NULL'
		);

		$ret = $SQL->buildTable($mysql_conf['prefix'].'news', $news_table_structure, array('id'));

		if (! $ret) {
			Logger::error('main', 'Unable to create MySQL table \''.$mysql_conf['prefix'].'news\'');
			return false;
		}

		Logger::debug('main', 'MySQL table \''.$mysql_conf['prefix'].'news\' created');
		return true;
	}

	public static function load($id_) {
		Logger::debug('main', 'Starting Abstract_News::load for \''.$id_.'\'');

		$SQL = MySQL::getInstance();

		$id = $id_;

		$SQL->DoQuery('SELECT @1,@2,@3 FROM @4 WHERE @5 = %6 LIMIT 1', 'title', 'content', 'timestamp', $SQL->prefix.'news', 'id', $id);
		$total = $SQL->NumRows();

		if ($total == 0) {
			Logger::error('main', "Abstract_News::load($id_) news does not exist (NumRows == 0)");
			return false;
		}

		$row = $SQL->FetchResult();

		foreach ($row as $k => $v)
			$$k = $v;

		$buf = new News($id);
		$buf->title = (string)$title;
		$buf->content = (string)$content;
		$buf->timestamp = (int)$timestamp;

		return $buf;
	}

	public static function save($news_) {
		Logger::debug('main', 'Starting Abstract_News::save for \''.$news_->id.'\'');

		$SQL = MySQL::getInstance();

		$id = $news_->id;

		if (! Abstract_News::load($id)) {
			Logger::info('main', "Abstract_News::save($news_) unable to load news, we must create it");

			$id = Abstract_News::create($news_);
			if (! $id) {
				Logger::error('main', "Abstract_News::save($news_) Abstract_News::create failed");
				return false;
			}
		}

		$SQL->DoQuery('UPDATE @1 SET @2=%3,@4=%5,@6=%7 WHERE @8 = %9 LIMIT 1', $SQL->prefix.'news', 'title', $news_->title, 'content', $news_->content, 'timestamp', $news_->timestamp, 'id', $id);

		return true;
	}

	private static function create($news_) {
		Logger::debug('main', 'Starting Abstract_News::create for \''.$news_->id.'\'');

		$SQL = MySQL::getInstance();

		$id = $news_->id;

		$SQL->DoQuery('SELECT 1 FROM @1 WHERE @2 = %3 LIMIT 1', $SQL->prefix.'news', 'id', $id);
		$total = $SQL->NumRows();

		if ($total != 0) {
			Logger::error('main', "Abstract_News::create($news_) news already exist (NumRows == $total)");
			return false;
		}

		$SQL->DoQuery('INSERT INTO @1 (@2) VALUES (%3)', $SQL->prefix.'news', 'id', $id);

		return $SQL->InsertId();
	}

	public static function delete($id_) {
		Logger::debug('main', 'Starting Abstract_News::delete for \''.$id_.'\'');

		$SQL = MySQL::getInstance();

		$id = $id_;

		$SQL->DoQuery('SELECT 1 FROM @1 WHERE @2 = %3 LIMIT 1', $SQL->prefix.'news', 'id', $id);
		$total = $SQL->NumRows();

		if ($total == 0) {
			Logger::error('main', "Abstract_News::delete($id_) news does not exist (NumRows == 0)");
			return false;
		}

		$SQL->DoQuery('DELETE FROM @1 WHERE @2 = %3 LIMIT 1', $SQL->prefix.'news', 'id', $id);

		return true;
	}

	public static function load_all() {
		Logger::debug('main', 'Starting Abstract_News::load_all');

		$SQL = MySQL::getInstance();

		$SQL->DoQuery('SELECT @1 FROM @2 ORDER BY @3 DESC', 'id', $SQL->prefix.'news', 'timestamp');
		$rows = $SQL->FetchAllResults();

		$news = array();
		foreach ($rows as $row) {
			$id = $row['id'];

			$new = Abstract_News::load($id);
			if (! $new)
				continue;

			$news[] = $new;
		}

		return $news;
	}
}
