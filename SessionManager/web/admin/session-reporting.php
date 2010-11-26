<?php
/**
* Copyright (C) 2010 Ulteo SAS
* http://www.ulteo.com
* Author Julien LANGLOIS <julien@ulteo.com>
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
require_once(dirname(__FILE__).'/includes/page_template.php');


if (! checkAuthorization('viewStatus'))
	redirect('index.php');


if (isset($_REQUEST['action'])) {
	if ($_REQUEST['action']=='manage') {
		if (isset($_REQUEST['id']))
			show_manage($_REQUEST['id']);
	}
}

show_default();

function get_sessions_history($from, $to, $user_login, $limit) {
	$extra = array();
	if ($from != null && $to != null)
		$extra[]= '@2>=%3';
	if ($to != null)
		$extra[]= '@4<=%5';
		
	if ($user_login != null)
		$extra[]= '@6=%7';
	
	$query = 'SELECT * ';
	$query.= 'FROM @1 ';
	if (count($extra) > 0) {
		$query.= 'WHERE';
		$query.= implode(" AND ", $extra).' ';
	}
	$query.= 'ORDER BY @2 DESC LIMIT '.$limit.';';
	
	$sql = SQL::getInstance();
	$res = $sql->DoQuery($query, SESSIONS_HISTORY_TABLE, 
				'start_stamp', (is_null($from)?null:date('c', $from)),
				'stop_stamp',  (is_null($to)?null:date('c', $to)),
				'user', (is_null($user_login)?null:$user_login));
	
	return $sql->FetchAllResults();
}

function show_default() {
	$search_by_user = false;
	$search_by_time = false;
	$user = '';
	$t1 = time();
	$t0 = strtotime('-1 Month', $t1);
	
	$prefs = Preferences::getInstance();
	$search_limit = $prefs->get('general', 'max_items_per_page');
	
	if (isset($_REQUEST['search_by']) && is_array($_REQUEST['search_by'])) {
		if (in_array('user', $_REQUEST['search_by'])) {
			$search_by_user = true;
			if (isset($_REQUEST['user']))
				$user = $_REQUEST['user'];
		}
		else
			$search_by_user = false;
		if (in_array('time', $_REQUEST['search_by'])) {
			$search_by_time = true;
			if (isset($_REQUEST['from']))
				$t0 = $_REQUEST['from'];
			if (isset($_REQUEST['to']))
				$t1 = $_REQUEST['to'];
		}
		else
			$search_by_time = false;
	}
	
	$sessions = get_sessions_history(($search_by_time?$t0:null), ($search_by_time?$t1:null), ($search_by_user?$user:null), $search_limit+1);
	$partial_result = false;
	if (count($sessions) > $search_limit) {
		$partial_result = true;
		array_pop($sessions);
	}

	page_header(array('js_files' => array('media/script/lib/calendarpopup/CalendarPopup.js')));

	echo '<script type="text/javascript" charset="utf-8">';
	echo '  function toggle_login(state) {';
	echo '    if (state == true) {';
	echo '      $(\'by_login_1\').show();';
	echo '      $(\'by_login_2\').show();';
	echo '    } else {';
	echo '      $(\'by_login_1\').hide();';
	echo '      $(\'by_login_2\').hide();';
	echo '    }';
	echo '  };';
	echo '  function toggle_time(state) {';
	echo '    if (state == true) {';
	echo '      $(\'by_time_1\').show();';
	echo '      $(\'by_time_2\').show();';
	echo '    } else {';
	echo '      $(\'by_time_1\').hide();';
	echo '      $(\'by_time_2\').hide();';
	echo '    }';
	echo '  };';
	echo '  Event.observe(window, \'load\', function() {';
	echo '    toggle_login('.($search_by_user?'true':'false').');';
	echo '    toggle_time('.($search_by_time?'true':'false').');';
	echo '  });';
	echo '</script>';



	echo '<h1>'._('Sessions Reporting').'</h1>';
	
	
	echo '<div style="margin-bottom: 15px;">';
	echo '<form action="" method="GET">';
	echo '<table>';
	echo '<tr><td colspan="5">'._('Search for archived sessions :').'</td></tr>';
	echo '<tr><td style="padding-left: 15px;">&nbsp;</td><td><input type="checkbox" name="search_by[]" value="user" onchange="toggle_login(this.checked);"';
	if ($search_by_user === true)
		echo ' checked="checked"';
	echo '/>'._('By user').'</td></tr>';
	echo '<tr id="by_login_1"><td></td><td></td><td colspan="3"><input type="text" name="user" value="'.$user.'" /></td></tr>';
	echo '<tr id="by_login_2"><td></td><td></td><td colspan="3"><em>'._('user login').'</em></td></tr>';
	
	echo '<tr><td></td><td><input type="checkbox" name="search_by[]" value="time" onchange="toggle_time(this.checked);"';
	if ($search_by_time === true)
		echo ' checked="checked"';
	echo '"/>'._('By time').'</td></tr>';
	echo '<tr id="by_time_1"><td></td><td></td><td><strong>'._('From').'</strong></td>';
	echo '<td>';
	echo '<a href="#" id="anchor_day_from" onclick="calendar_day_from.select($(\'from\'), \'anchor_day_from\'); return false;" >'.date('Y-m-d', $t0).'</a>';
	echo '&nbsp;<select id="hour_from" onchange="calendars_update();">';
	for ($i = 0; $i < 24; $i++)
		echo '<option value="'.$i.'">'.(($i<10)?'0':'').$i.':00</option>';
	echo '</select>';
	echo '<div id="calendar_day_from" style="position: absolute; visibility: hidden; background: white;"></div>';
	echo '</td>';
	echo '</tr>';
	echo '<tr id="by_time_2"><td></td><td></td><td><strong>'._('To').'</strong></td>';
	echo '<td>';
	echo '<a href="#" id="anchor_day_to" onclick="calendar_day_to.select($(\'to\'), \'anchor_day_to\'); return false;" >'.date('Y-m-d', $t1).'</a>';
	echo '&nbsp;<select id="hour_to" onchange="calendars_update();">';
	for ($i = 0; $i < 24; $i++)
		echo '<option value="'.$i.'">'.(($i<10)?'0':'').$i.':00</option>';
	echo '</select>';
	echo '<div id="calendar_day_to" style="position: absolute; visibility: hidden; background: white;"></div>';
	echo '</td>';
	echo '</tr>';
	
	echo '<tr><td></td><td></td><td><input type="submit" value="'._('Search').'" /><td><td>';
	if (count($sessions)>0)
		echo sprintf(ngettext('<strong>%d</strong> result.', '<strong>%d</strong> results.', count($sessions)), count($sessions));
	else
		echo '<span class="error"><strong>'._('No result found!').'</strong></span>';
	
	echo '</td></tr>';
	if ($partial_result === true) {
		echo '<tr><td></td>';
		echo '<td colspan="5">';
		echo '<span class="error">';
		echo sprintf(ngettext("<strong>Partial content:</strong> Only <strong>%d result</strong> displayed but there are more. Please restrict your search field.", "<strong>Partial content:</strong> Only <strong>%d results</strong> displayed but there are more. Please restrict your search field.", $search_limit), $search_limit);
		echo '</span>';
		echo '</td></tr>';
	}
 	
	echo '</table>';
	
	echo '<input id="from" name="from" value="'.$t0.'" type="hidden" />';
	echo '<input id="to" name="to" value="'.$t1.'" type="hidden" />';
	echo '</form>';
	echo '</div>';

	echo '<script type="text/javascript" charset="utf-8">';
	echo '  document.write(getCalendarStyles());';
	echo '  var calendar_day_from = new CalendarPopup("calendar_day_from");';
	echo '  var calendar_day_to   = new CalendarPopup("calendar_day_to");';
	
	echo '  function calendars_init() {';
	echo '    calendar_day_from.setReturnFunction("calendars_callback_from");';
	echo '    calendar_day_to.setReturnFunction("calendars_callback");';
	
	echo '    var from_date = new Date();';
	echo '    from_date.setTime($("from").value*1000);';
	echo '    calendar_day_from.currentDate = from_date;';
	echo '    rewrite_date(from_date, $(\'anchor_day_from\'), $(\'hour_from\'));';
	
	echo '    var to_date = new Date();';
	echo '    to_date.setTime($("to").value*1000);';
	echo '    calendar_day_to.currentDate = to_date;';
 	echo '    rewrite_date(to_date, $(\'anchor_day_to\'), $(\'hour_to\'));';
	echo '  }';
	
	echo '  function calendars_callback_from(y, m, d) {';
	echo '    calendar_day_from.currentDate.setFullYear(y);';
	echo '    calendar_day_from.currentDate.setMonth(m-1);';
	echo '    calendar_day_from.currentDate.setDate(d);';
	echo '    calendars_update();';
	echo '  }';
	
	echo '  function calendars_callback_to(y, m, d) {';
	echo '    calendar_day_to.currentDate.setFullYear(y);';
	echo '    calendar_day_to.currentDate.setMonth(m-1);';
	echo '    calendar_day_to.currentDate.setDate(d);';
	echo '    calendars_update();';
	echo '  }';
	
	echo '  function calendars_update() {';
	echo '    var from_date = calendar_day_from.currentDate;';
	echo '    var from_hour = $(\'hour_from\').options[$(\'hour_from\').selectedIndex].value;';
	echo '    from_date.setHours(from_hour);';
	echo '    rewrite_date(from_date, $(\'anchor_day_from\'), $(\'hour_from\'));';
	echo '    $("from").value = from_date.getTime()/1000;';
	
	echo '    var to_date = calendar_day_to.currentDate;';
	echo '    var to_hour = $(\'hour_to\').options[$(\'hour_to\').selectedIndex].value;';
	echo '    to_date.setHours(to_hour);';
	echo '    rewrite_date(to_date, $(\'anchor_day_to\'), $(\'hour_to\'));';
 	echo '    $("to").value = to_date.getTime()/1000;';
	echo '  }';
	
	echo '  function rewrite_date(date, ymd_node, hour_select_node) {';
	echo '    var buf = date.getFullYear()+"-";';
	echo '    if (date.getMonth()+1 < 10)';
	echo '      buf+= "0";';
	echo '    buf+=date.getMonth()+1+"-";';
	echo '    if (date.getDate() < 10)';
	echo '      buf+= "0";';
	echo '    buf+=date.getDate(); ';
	echo '    ymd_node.innerHTML = buf;';
	echo '';
	echo '    for(var i=0; i<hour_select_node.options.length; i++) {';
	echo '      if (hour_select_node.options[i].value == date.getHours())';
	echo '        hour_select_node.selectedIndex = i;';
	echo '    }';
	echo '  }';

	echo '  Event.observe(window, \'load\', function() {';
	echo '    calendars_init();';
	echo '  });';
	
	echo '</script>';
	
	if (count($sessions) > 0) {
		echo '<table class="main_sub sortable" id="main_table" border="0" cellspacing="1" cellpadding="5">';
		echo '<thead>';
		echo '<tr class="title">';
		echo '<th>'._('Session id').'</th>';
		echo '<th>'._('User').'</th>';
		echo '<th>'._('Date').'</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';
		
		$count = 0;
		foreach($sessions as $session) {
			$content = 'content'.(($count++%2==0)?1:2);
			echo '<tr class="'.$content.'">';
			echo '<td>'.$session['id'].'</td>';
			echo '<td><a href="users.php?action=manage&id='.$session['user'].'">'.$session['user'].'</a></td>';
			echo '<td>'.$session['start_stamp'].'</td>';
			echo '<td><form><input type="hidden" name="action" value="manage"/><input type="hidden" name="id" value="'.$session['id'].'"/><input type="submit" value="'._('Get more information').'"/></form></td>';
			echo '</tr>';
		}

		echo '</tbody>';
		echo '</table>';
	}
	page_footer();
	die();
}


function get_session_reporting($id_) {
	$sql = SQL::getInstance();
	$res = $sql->DoQuery('SELECT * FROM @1 WHERE @2 = %3;',
				SESSIONS_HISTORY_TABLE, 'id', $id_);
	
	$results = $sql->FetchAllResults();
	if (count($results) == 0)
		return null;
	
	return $results[0];
}


function show_manage($id_) {
// 	$session = Abstract_ReportSession::load($id_);
	$session = get_session_reporting($id_);
	if (! $session) {
		popup_error(sprintf(_('Unknown session %s'), $id_));
		redirect();
	}

	$userDB = UserDB::getInstance();
	$user = $userDB->import($session['user']);
	
	$applicationDB = ApplicationDB::getInstance();
	$applications = array();
	
	$dom = new DomDocument('1.0', 'utf-8');
	$ret = @$dom->loadXML($session['data']);
	if ($ret) {
		foreach ($dom->getElementsByTagName('application') as $node) {
			$application = array();
			foreach ($node->childNodes as $child_node) {
				$name = $child_node->nodeName;
				if ($name == '#text')
					continue;
				
				$application[$name] = $child_node->nodeValue;
			}
			
			$applications[]= $application;
		}
	}
	
	for ($i=0; $i<count($applications); $i++) {
		$app_buf = $applicationDB->import($applications[$i]['id']);
		if (is_object($app_buf)) {
			$applications[$i]["obj"] = $app_buf;
		}
	}
	
	
	page_header();

	echo '<h1>'.str_replace('%ID%', $session['id'], _('Archived session - %ID%')).'</h1>';

	echo '<ul>';
	echo '<li><strong>'._('User:').'</strong> ';
	if (is_object($user))
		echo '<a href="users.php?action=manage&id='.$user->getAttribute('login').'">'.$user->getAttribute('displayname').'</a>';
	else
		echo $session['user'].' <span><em>'._('Not existing anymore').'</em></span>';
	echo '</li>';
	
	echo '<li><strong>'._('Started:').'</strong> ';
	echo $session['start_stamp'];
	echo '</li>';
	echo '<li><strong>'._('Stopped:').'</strong> ';
	echo $session['stop_stamp'];
	if (isset($session['stop_why']) && strlen($session['stop_why'])>0)
		echo '&nbsp<em>('.$session['stop_why'].')</em>';
	echo '</li>';
	echo '</ul>';
	
	if (count($applications)>0) {
		echo '<div>';
		echo '<h2>'._('Used applications').'</h2>';
		echo '<ul>';
		foreach ($applications as $application) {
			echo '<li>';
			if (isset($application['obj'])) {
				echo '<img src="media/image/cache.php?id='.$application['obj']->getAttribute('id').'" alt="" title="" /> ';
				echo '<a href="applications.php?action=manage&id='.$application['obj']->getAttribute('id').'">'.$application['obj']->getAttribute('name').'</a>';
			}
			else
				echo $application['id'].'&nbsp;<span><em>'._('not existing anymore').'</em></span>';
			
			if (($application['start']-$application['start'])>0)
				echo '  - ('.(($application['start']-$application['start'])/60).'m)';
			echo '</li>';
		}
		echo '</ul>';
		echo '</div>';
	}
	
	page_footer();
	die();
}
