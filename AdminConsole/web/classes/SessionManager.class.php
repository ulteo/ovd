<?php
/**
 * Copyright (C) 2012 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2012
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

class SessionManager {
	private $service = NULL;

	public function __construct($login_, $password_, $ssl_=true) {
		if (defined('DEBUG_MODE') && DEBUG_MODE === true) {
			ini_set('soap.wsdl_cache_enabled', 0);
		}
		
		try {
			$this->service = new SoapClient(ADMIN_ROOT.'/includes/ovd-admin.wsdl', array(
				'login' => $login_,
				'password' => $password_,
				'location' => (($ssl_)?'https':'http').'://'.SESSIONMANAGER_HOST.'/ovd/service/admin',
				// 'classmap' =>  ... ToDo: check if we can create the basics object using that
			));
		} catch (Exception $e) {
			die_error(self::format_soap_error_message($e));
			throw($e);
		}
	}
	
	public function test_link_connected() {
		$res = false;
		try {
			$res = @$this->service->test_link_connected();
			// PHP show an error even in the try catch ....
			// So must hide this error using '@' ... at least for this call
		} catch (Exception $e) {
			throw($e);
		}
		
		return $res;
	}
	
	public static function format_soap_error_message($exception_) {
		return _('Communication error with Session Manager').'<br/>'.
				_('Soap exception: ').'<hr/>'.
				str_replace("\n", '<br/>', $exception_).'<hr/>';
	}
	
	public function __call($name, $arguments) {
		try {
			$res = $this->service->__call($name, $arguments);
		} catch (Exception $e) {
			if ($e->faultcode == 'not_authorized') {
				popup_error(_('You are not allowed to perform this action'));
			}
			else {
				popup_error(self::format_soap_error_message($e));
			}
			
			return null;
		}
		
		if (defined('DEBUG_MODE') && DEBUG_MODE === true) {
			popup_info('soap Call '.$name);
		}
		
		return $res;
	}
	
	public function getInitialConfiguration() {
		try {
			$res = $this->service->getInitialConfiguration();
		} catch (Exception $e) {
			throw($e);
		}
		
		return $res;
	}
	
	public function servers_list($filter_ = null) {
		$args = func_get_args(); // func_get_args(): Can't be used as a function parameter before PHP 5.3.0
		$res = $this->__call('servers_list', $args);
		if ($res === null) {
			return null;
		}
		
		$servers = array();
		foreach($res as $item) {
			$server = new Server($item['id']);
			
			foreach($item as $k => $v) {
				$server->setAttribute($k, $v);
			}
			
			$servers[]= $server;
		}
		
		return $servers;
	}
	
	
	public function getUnregisteredServersList() {
		return $this->servers_list('unregistered');
	}
	
	public function getOnlineServersList() {
		return $this->servers_list('online');
	}
	
	public function server_info($id_) {
		$args = func_get_args(); // func_get_args(): Can't be used as a function parameter before PHP 5.3.0
		$res = $this->__call('server_info', $args);
		if ($res === null) {
			return null;
		}
		
		$server = new Server($res['id']);
		foreach($res as $k => $v) {
			$server->setAttribute($k, $v);
		}
		
		return $server;
	}
	
	public function tasks_list() {
		$args = func_get_args(); // func_get_args(): Can't be used as a function parameter before PHP 5.3.0
		$res = $this->__call('tasks_list', $args);
		if ($res === null) {
			return null;
		}
		
		$tasks = array();
		foreach($res as $item) {
			$task = new Task($item);
			if (! $task->is_valid())
				continue;
			
			$tasks[]= $task;
		}
		
		return $tasks;
	}
	
	public function task_info($id_) {
		$args = func_get_args(); // func_get_args(): Can't be used as a function parameter before PHP 5.3.0
		$res = $this->__call('task_info', $args);
		if ($res === null) {
			return null;
		}
		
		$task = new Task($res);
		if (! $task->is_valid())
			continue;
		
		return $task;
	}
	
	
	public function applications_list($type_ = null) {
		$args = func_get_args(); // func_get_args(): Can't be used as a function parameter before PHP 5.3.0
		$res = $this->__call('applications_list', $args);
		if ($res === null) {
			return null;
		}
		
		$applications = array();
		foreach($res as $item) {
			$application = new Application($item);
			if (! $application->is_valid()) {
				continue;
			}
			
			$applications[$application->getAttribute('id')]= $application;
		}
		
		return $applications;
	}
	
	public function application_info($id_) {
		$args = func_get_args(); // func_get_args(): Can't be used as a function parameter before PHP 5.3.0
		$res = $this->__call('application_info', $args);
		if ($res === null) {
			return null;
		}
		
		$application = new Application($res);
		if (! $application->is_valid()) {
			return null;
		}
		
		if (array_key_exists('tasks', $res)) {
			$tasks = array();
			foreach($res['tasks'] as $item) {
				$task = new Task($item);
				if (! $task->is_valid())
					continue;
				
				$tasks[]= $task;
			}
			
			$application->setAttribute('tasks', $tasks);
		}
		
		
		return $application;
	}
	
	public function application_icon_get($id_) {
		$args = func_get_args(); // func_get_args(): Can't be used as a function parameter before PHP 5.3.0
		$res = $this->__call('application_icon_get', $args);
		if ($res === null) {
			return null;
		}
		
		return $res;
	}
	
	public function applications_groups_list() {
		$args = func_get_args(); // func_get_args(): Can't be used as a function parameter before PHP 5.3.0
		$res = $this->__call('applications_groups_list', $args);
		if ($res === null) {
			return null;
		}
		
		$groups = array();
		foreach($res as $item) {
			$g = new ApplicationsGroup($item);
			if (! $g->is_valid()) {
				continue;
			}
			
			$groups[]= $g;
		}
		
		return $groups;
	}
	
	public function applications_group_info($id_) {
		$args = func_get_args(); // func_get_args(): Can't be used as a function parameter before PHP 5.3.0
		$res = $this->__call('applications_group_info', $args);
		if ($res === null) {
			return null;
		}
		
		$g = new ApplicationsGroup($res);
		if (! $g->is_valid()) {
			continue;
		}
		
		return $g;
	}
	
	public function users_list($filter_) {
		$args = func_get_args(); // func_get_args(): Can't be used as a function parameter before PHP 5.3.0
		$res = $this->__call('users_list', $args);
		if ($res === null) {
			return null;
		}
		
		$users = array();
		foreach($res as $item) {
			$user = new User($item);
			if (! $user->is_valid()) {
				continue;
			}
			
			$users[]= $user;
		}
		
		return $users;
	}
	
	public function user_info($id_) {
		$args = func_get_args(); // func_get_args(): Can't be used as a function parameter before PHP 5.3.0
		$res = $this->__call('user_info', $args);
		if ($res === null) {
			return null;
		}
		
		$user = new User($res);
		if (! $user->is_valid()) {
			return null;
		}
		
		if (! $user->hasAttribute('groups'))
			$user->setAttribute('groups', array());
		
		return $user;
	}
	
	public function users_groups_list_partial($search_item_, $search_fields_) {
		$args = func_get_args(); // func_get_args(): Can't be used as a function parameter before PHP 5.3.0
		$res = $this->__call('users_groups_list_partial', $args);
		if ($res === null) {
			return null;
		}
		
		return $res;
	}
	
	public function users_group_info($id_) {
		$args = func_get_args(); // func_get_args(): Can't be used as a function parameter before PHP 5.3.0
		$res = $this->__call('users_group_info', $args);
		if ($res === null) {
			return null;
		}
		
		$group = new UsersGroup($res);
		if (! $group->is_valid()) {
			return null;
		}
		
		return $group;
	}
	
	public function shared_folders_list() {
		$args = func_get_args(); // func_get_args(): Can't be used as a function parameter before PHP 5.3.0
		$res = $this->__call('shared_folders_list', $args);
		if ($res === null) {
			return null;
		}
		
		$shares = array();
		foreach($res as $item) {
			$s = new SharedFolder($item);
			if (! $s->is_valid())
				continue;
			
			$shares[]= $s;
		}
		
		return $shares;
	}
	
	public function shared_folder_info($id_) {
		$args = func_get_args(); // func_get_args(): Can't be used as a function parameter before PHP 5.3.0
		$res = $this->__call('shared_folder_info', $args);
		if ($res === null) {
			return null;
		}
		
		$s = new SharedFolder($res);
		if (! $s->is_valid()) {
			return null;
		}
		
		return $s;
	}
	
	public function users_profiles_list() {
		$args = func_get_args(); // func_get_args(): Can't be used as a function parameter before PHP 5.3.0
		$res = $this->__call('users_profiles_list', $args);
		if ($res === null) {
			return null;
		}
		
		$shares = array();
		foreach($res as $item) {
			$s = new UserProfile($item);
			if (! $s->is_valid())
				continue;
			
			$shares[]= $s;
		}
		
		return $shares;
	}
	
	public function user_profile_info($id_) {
		$args = func_get_args(); // func_get_args(): Can't be used as a function parameter before PHP 5.3.0
		$res = $this->__call('user_profile_info', $args);
		if ($res === null) {
			return null;
		}
		
		$s = new UserProfile($res);
		if (! $s->is_valid()) {
			return null;
		}
		
		return $s;
	}
	
	public function sessions_list($start_ = 0) {
		$args = func_get_args(); // func_get_args(): Can't be used as a function parameter before PHP 5.3.0
		$res = $this->__call('sessions_list', $args);
		if ($res === null) {
			return null;
		}
		
		$sessions = array();
		foreach($res as $item) {
			$s = new Session($item);
			if (! $s->is_valid())
				continue;
			
			$sessions[]= $s;
		}
		
		return $sessions;
	}
	
	public function sessions_list_by_server($server_, $start_ = 0) {
		$args = func_get_args(); // func_get_args(): Can't be used as a function parameter before PHP 5.3.0
		$res = $this->__call('sessions_list_by_server', $args);
		if ($res === null) {
			return null;
		}
		
		$sessions = array();
		foreach($res as $item) {
			$s = new Session($item);
			if (! $s->is_valid())
				continue;
			
			$sessions[]= $s;
		}
		
		return $sessions;
	}
	
	public function session_info($id_) {
		$args = func_get_args(); // func_get_args(): Can't be used as a function parameter before PHP 5.3.0
		$res = $this->__call('session_info', $args);
		if ($res === null) {
			return null;
		}
		
		$s = new Session($res);
		if (! $s->is_valid())
			return null;
		
		return $s;
	}
	
	public function sessions_reports_list($start_, $stop_) {
		$args = func_get_args(); // func_get_args(): Can't be used as a function parameter before PHP 5.3.0
		$res = $this->__call('sessions_reports_list', $args);
		if ($res === null) {
			return null;
		}
		
		$reports = array();
		foreach($res as $item) {
			$s = new SessionReport($item);
			if (! $s->is_valid())
				continue;
			
			$reports[]= $s;
		}
		
		return $reports;
	}
	
	public function sessions_reports_list2($start_, $stop_, $server_ = null) {
		$args = func_get_args(); // func_get_args(): Can't be used as a function parameter before PHP 5.3.0
		$res = $this->__call('sessions_reports_list2', $args);
		if ($res === null) {
			return null;
		}
		
		$reports = array();
		foreach($res as $item) {
			$s = new SessionReport($item);
			if (! $s->is_valid())
				continue;
			
			$reports[]= $s;
		}
		
		return $reports;
	}
	
	public function sessions_reports_list3($from_, $to_, $user_login_, $limit_) {
		$args = func_get_args(); // func_get_args(): Can't be used as a function parameter before PHP 5.3.0
		$res = $this->__call('sessions_reports_list3', $args);
		if ($res === null) {
			return null;
		}
		
		$reports = array();
		foreach($res as $item) {
			$s = new SessionReport($item);
			if (! $s->is_valid())
				continue;
			
			$reports[]= $s;
		}
		
		return $reports;
	}
	
	public function session_report_info($id_) {
		$args = func_get_args(); // func_get_args(): Can't be used as a function parameter before PHP 5.3.0
		$res = $this->__call('session_report_info', $args);
		if ($res === null) {
			return null;
		}
		
		$r = new SessionReport($res);
		if (! $r->is_valid()) {
			return null;
		}
		
		return $r;
	}
	
	public function servers_reports_list($start_, $stop_) {
		$args = func_get_args(); // func_get_args(): Can't be used as a function parameter before PHP 5.3.0
		$res = $this->__call('servers_reports_list', $args);
		if ($res === null) {
			return null;
		}
		
		$reports = array();
		foreach($res as $item) {
			$r = new ServerReport($item);
			if (! $r->is_valid()) {
				continue;
			}
			
			$reports[]= $r;
		}
		
		return $reports;
	}
	
	public function news_list() {
		$args = func_get_args(); // func_get_args(): Can't be used as a function parameter before PHP 5.3.0
		$res = $this->__call('news_list', $args);
		if ($res === null) {
			return null;
		}
		
		$news = array();
		foreach($res as $item) {
			$n = new News($item);
			if (! $n->is_valid()) {
				continue;
			}
			
			$news[]= $n;
		}
		
		return $news;
	}
	
	public function news_info($id_) {
		$args = func_get_args(); // func_get_args(): Can't be used as a function parameter before PHP 5.3.0
		$res = $this->__call('news_info', $args);
		if ($res === null) {
			return null;
		}
		$n = new News($res);
		if (! $n->is_valid()) {
			continue;
		}
		
		return $n;
	}
}
