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

define('TOKENS_DIR', SESSIONMANAGER_SPOOL.'/tokens');
if (! check_folder(TOKENS_DIR)) {
	Logger::critical('main', TOKENS_DIR.' does not exist and cannot be created !');
	die_error(TOKENS_DIR.' does not exist and cannot be created !', __FILE__, __LINE__);
}

class Abstract_Token extends Abstract_DB {
	public function load($id_) {
// 		Logger::debug('main', 'Starting Abstract_Token::load for \''.$id_.'\'');

		$id = $id_;
		$folder = TOKENS_DIR.'/'.$id;

		if (! is_readable($folder))
			return false;

		$attributes = array('type', 'session');
		foreach ($attributes as $attribute)
			if (($$attribute = @file_get_contents($folder.'/'.$attribute)) === false)
				return false;
		unset($attribute);
		unset($attributes);

		$buf = new Token($id);
		$buf->type = (string)$type;
		$buf->session = (string)$session;

		return $buf;
	}

	public function save($token_) {
// 		Logger::debug('main', 'Starting Abstract_Token::save for \''.$token_->id.'\'');

		$id = $token_->id;
		$folder = TOKENS_DIR.'/'.$id;

		if (! Abstract_Token::load($id))
			if (! Abstract_Token::create($token_))
				return false;

		if (! is_writeable($folder))
			return false;

		@file_put_contents($folder.'/type', (string)$token_->type);
		@file_put_contents($folder.'/session', (string)$token_->session);

		return true;
	}

	private function create($token_) {
// 		Logger::debug('main', 'Starting Abstract_Token::create for \''.$token_->id.'\'');

		$id = $token_->id;
		$folder = TOKENS_DIR.'/'.$id;

		if (! is_writeable(TOKENS_DIR))
			return false;

		if (! @mkdir($folder, 0750))
			return false;

		return true;
	}

	public function delete($id_) {
// 		Logger::debug('main', 'Starting Abstract_Token::delete for \''.$id_.'\'');

		$id = $id_;
		$folder = TOKENS_DIR.'/'.$fqdn;

		if (! file_exists($folder))
			return false;

		$remove_files = glob($folder.'/*');
		foreach ($remove_files as $remove_file)
			if (! @unlink($remove_file))
				return false;
		unset($remove_file);
		unset($remove_files);

		if (! @rmdir($folder))
			return false;

		return true;
	}
}
