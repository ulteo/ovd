<?php
/**
* Copyright (C) 2009-2010 Ulteo SAS
* http://www.ulteo.com
* Author Laurent CLOUET <laurent@ulteo.com>
* Author Jeremy DESVAGES <jeremy@ulteo.com>
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


/**
 * API
 * method GET
 * mode: day|hour
 * from: 2009-03-01 12:00
 * to: 2009-03-01 12:00
 **/

require_once(dirname(__FILE__).'/includes/core.inc.php');
require_once(dirname(__FILE__).'/includes/page_template.php');
include('libchart/classes/libchart.php');

define('MAX_STEPS', 20);

if (! checkAuthorization('viewStatus'))
	redirect('index.php');


define('REPORT_PREFIX', 'rp_'); // windows tempnam prefix max length is 3

function clean_cache() {
	foreach(glob(TMP_DIR.'/'.REPORT_PREFIX.'*') as $name) {
		if (filemtime($name)< time()-300)
		@unlink($name);
	}
}

function show_img($id_) {
	$file = TMP_DIR.'/'.REPORT_PREFIX.$_GET['file'];
	if (! is_readable($file)) {
		header('HTTP/1.1 404 Not Found');
		die();
	}

	$img = imagecreatefrompng($file);
	header('Content-Type: image/png');
	imagepng($img);
#    unlink($file);
	die();
}

function get_avg_value($datas) {
	if (count($datas) == 0)
		return 0;

	$i = 0;
	foreach($datas as $d)
		$i+=$d;

	return $i/count($datas);
}

abstract class ReportMode {
	abstract public static function get_prefix();
	abstract public static function get_next();
	abstract public static function get_default_from();
	abstract public static function transform_date($date_);
	abstract public static function get_name();
	abstract public static function get_value();
}

class ReportMode_day extends ReportMode {
	public static function get_prefix() {
		return 'Y-m-d';
	}

	public static function get_next() {
		return '+1 day';
	}

	public static function get_default_from() {
		return '-7 days';
	}

	public static function transform_date($date_) {
		return mktime(0, 0, 0, date('m', $date_),
			      date('d', $date_),
			      date('Y', $date_));
	}

	public static function get_name() {
		return _('Day');
	}

	public static function get_value() {
		return 'day';
	}
}

class ReportMode_hour extends ReportMode {
	public static function get_prefix() {
		return 'Y-m-d H';
	}

	public static function get_next() {
		return '+1 hour';
	}

	public static function get_default_from() {
		return '-24 hours';
	}

	public static function transform_date($date_) {
		return mktime(date('H', $date_), 0, 0,
			      date('m', $date_),
			      date('d', $date_),
			      date('Y', $date_));
	}

	public static function get_name() {
		return _('Hour');
	}

	public static function get_value() {
		return 'hour';
	}
}

class ReportMode_minute extends ReportMode {
	public static function get_prefix() {
		return 'Y-m-d H:i';
	}
	
	public static function get_next() {
		return '+1 minute';
	}

	public static function get_default_from() {
		return '-60 minutes';
	}
	
	public static function transform_date($date_) {
		return mktime(date('H', $date_),
				date('i', $date_), 0,
				date('m', $date_),
				date('d', $date_),
				date('Y', $date_));
	}

	public static function get_name() {
		return _('Minute');
	}

	public static function get_value() {
		return 'minute';
	}
}

function get_session_history($t0, $t1, $mode_) {
	$result = build_array($t0, $t1, $mode_);
	$res_server = array();
	$end_status = array();

	$sql = SQL::getInstance();
	$res = $sql->DoQuery('SELECT * FROM @1 WHERE @2 BETWEEN %3 AND %4;',
						SESSIONS_HISTORY_TABLE, 'start_stamp', date('c', $t0), date('c', $t1));


	$g = $sql->FetchAllResults();
	foreach($g as $p) {
		if (is_null($p['stop_stamp'])) { // todo: or stop_stamp=NULL
			if (! Abstract_Session::exists($p['id'])) {
				// Before the v2.5, most of sessions report was empty ...
//				Logger::warning('main', 'Invalid reporting item session '.$p['id']);
				continue;
			}
		}

		$y = strtotime($p['start_stamp']);
		$buf = date($mode_->get_prefix(), $y);
		
		if ($p['stop_why'] == '' || is_null($p['stop_why']))
			$p['stop_why'] = 'unknown';
		
		if (! is_null($p['stop_stamp'])) {
			if (! isset($end_status[$p['stop_why']]))
				$end_status[$p['stop_why']] = 0;
			
			$end_status[$p['stop_why']] += 1;
		}
		

		if (! isset($result[$buf]))
			continue;

		if (! isset($res_server[$p['server']]))
			$res_server[$p['server']] = build_array($t0, $t1, $mode_);
		$res_server[$p['server']][$buf]++;

		$result[$buf]++;
	}
	$session_number = count($g);

	return array($session_number, $result, $res_server, $end_status);
}


function get_server_history($t0, $t1, $mode_) {
	$sql = SQL::getInstance();
	$res = $sql->DoQuery('SELECT * FROM @1 WHERE @2 BETWEEN %3 AND %4 ORDER BY @2 ASC;', SERVERS_HISTORY_TABLE, 'timestamp', date('c', $t0), date('c', $t1));

	$infos = array();

	$g = $sql->FetchAllResults();
	foreach($g as $p) {
		$y = strtotime($p['timestamp']);
		$buf = date($mode_->get_prefix(), $y);
		if (! isset($infos[$p['fqdn']]))
			$infos[$p['fqdn']] = array('ram' => build_array($t0, $t1, $mode_, true),
						   'cpu' => build_array($t0, $t1, $mode_, true));


		$infos[$p['fqdn']]['ram'][$buf][]= $p['ram'];
		$infos[$p['fqdn']]['cpu'][$buf][]= $p['cpu']*100;
	}

	return $infos;
}

function get_session_end_status_for_server($t0_, $t1_, $fqdn_) {
	$end_status = array();
	$sql = SQL::getInstance();
	$res = $sql->DoQuery('SELECT  @1, count(@1) FROM @2 WHERE @3 = %4 AND @5 IS NOT NULL AND @6 BETWEEN %7 AND %8 GROUP BY @1', 'stop_why', SESSIONS_HISTORY_TABLE, 'server', $fqdn_,  'stop_stamp','start_stamp', date('c', $t0_), date('c', $t1_));
 
	$rows = $sql->FetchAllResults();
	foreach ($rows as $row) {
		$why = $row['stop_why'];
		unset($row['stop_why']);
		$nb = array_shift($row);  // to get the $row[count(`stop_why`)]
		if ($why == '' || is_null($why))
			$why = 'unknown';
		if ($nb != 0)
			$end_status[$why] = $nb;
	}
	
	return $end_status;
}


function build_array($t0, $t1, $mode_, $flag=false) {
	$result = array();

	for ($i=$t0; $i< $t1; $i=strtotime($mode_->get_next(), $i)) {
		$key = date($mode_->get_prefix(), $i);
		if ($flag)
			$result[$key] = array();
		else
			$result[$key] = 0;
	}

	return $result;
}

function show_page($mode_) {
	if (isset($_GET['from']))
		$t0 = strtotime($_GET['from']);
	else
		$t0 = strtotime($mode_->get_default_from());

	if (isset($_GET['to']))
		$t1 = strtotime($_GET['to']);
	else
		$t1 = time();

	$t0 = $mode_->transform_date($t0);
	$t1 = $mode_->transform_date($t1);
	if ($t0>$t1) {
		popup_error(_('Error: "from" date is after "to" date, switching'));
		$buf = $t0;
		$t0 = $t1;
		$t1 = $buf;
	}
	
	if ($t1 > time()) {
		popup_error(_('Error: "to" field is in the future, switching to current time'));
		$t1 = $mode_->transform_date(time());
	}
	
	$t2 = strtotime($mode_->get_next(), $t1);

	/* General system */
	if (! is_writable(TMP_DIR)) {
		popup_error(sprintf(_("%s is not writable"), TMP_DIR));
		page_header();
		echo '<h1>'._('Reporting').'</h1>';
		page_footer();
		die();
	}

	list($session_number, $result, $res_server, $end_status) = get_session_history($t0, $t2, $mode_);
		$info2 = get_server_history($t0, $t2, $mode_);

	$chart = new LineChart();
	$chart->getPlot()->setLogoFileName('');
	$dataSet = new XYDataSet();
	
	$step = max(round(count($result)/MAX_STEPS), 1);
	$step_i = 0;
	foreach ($result as $day => $num) {
		$text = ($step_i%$step == 0?substr($day, -2):'');
		$step_i++;
		
		$dataSet->addPoint(new Point($text, $num));
	}


	$chart->setDataSet($dataSet);
	$chart->setTitle(_('Number of launched sessions'));

	$tmpfile = tempnam(TMP_DIR, REPORT_PREFIX);
	$file_id = substr($tmpfile, strlen(TMP_DIR.'/'.REPORT_PREFIX));
	$chart->render($tmpfile);

	if ($session_number>0) {
		$dataSet = new XYDataSet();
		foreach($res_server as $fqdn => $c) {
			if ($fqdn === "") {
				$fqdn = _("unknown");
			}
			$tot = 0;
			foreach($c as $k => $v)
				$tot+= $v;
			$value = ($tot*100)/$session_number;

			$dataSet->addPoint(new Point(str_replace(array('%FQDN%', '%TOTAL%'), array($fqdn, (int)($tot)), ngettext(_('%FQDN% (%TOTAL% session)'), _('%FQDN% (%TOTAL% sessions)'), $value)), $value));
		}

		$chart = new PieChart();
		$chart->getPlot()->setLogoFileName('');
		$chart->setTitle(_('Session repartition'));
		$chart->setDataSet($dataSet);
		$tmpfile = tempnam(TMP_DIR, REPORT_PREFIX);
		$file_id2 = substr($tmpfile, strlen(TMP_DIR.'/'.REPORT_PREFIX));
		$chart->render($tmpfile);
		
		if (count($end_status) > 0) {
			$dataSet = new XYDataSet();
			foreach($end_status as $status_session => $number_status) {
				$dataSet->addPoint(new Point(str_replace(array('%STATUS%', '%TOTAL%'), array(Session::textEndStatus($status_session), (int)($number_status)), ngettext(_('%STATUS% (%TOTAL% session)'), _('%STATUS% (%TOTAL% sessions)'), $number_status)), $number_status));
			}
			
			$chart = new PieChart();
			$chart->getPlot()->setLogoFileName('');
			$chart->setTitle(_('Session end status'));
			$chart->setDataSet($dataSet);
			$tmpfile = tempnam(TMP_DIR, REPORT_PREFIX);
			$file_id3 = substr($tmpfile, strlen(TMP_DIR.'/'.REPORT_PREFIX));
			$chart->render($tmpfile);
		}
	}


	// Foreach server
	$servers = array();
	foreach($res_server as $fqdn => $value) {
		$servers[$fqdn] = array();

		$chart = new LineChart();
		$chart->getPlot()->setLogoFileName('');
		$dataSet = new XYDataSet();
		
		$step = max(round(count($value)/MAX_STEPS), 1);
		$step_i = 0;
		foreach ($value as $day => $num) {
			$text = ($step_i%$step == 0?substr($day, -2):'');
			$step_i++;
			
			$dataSet->addPoint(new Point($text, $num));
		}


		$chart->setDataSet($dataSet);
		$chart->setTitle(_('Number of launched sessions'));
		$tmpfile = tempnam(TMP_DIR, REPORT_PREFIX);
		$servers[$fqdn]['session_file'] = substr($tmpfile, strlen(TMP_DIR.'/'.REPORT_PREFIX));
		$chart->render($tmpfile);
		
		$dataSet = new XYDataSet();
		$end_status2 = get_session_end_status_for_server($t0, $t2, $fqdn);
		if (count($end_status2) > 0) {
			foreach($end_status2 as $status_session => $number_status) {
				$dataSet->addPoint(new Point(str_replace(array('%STATUS%', '%TOTAL%'), array(Session::textEndStatus($status_session), (int)($number_status)), ngettext(_('%STATUS% (%TOTAL% session)'), _('%STATUS% (%TOTAL% sessions)'), $number_status)), $number_status));
			}
			
			$chart = new PieChart();
			$chart->getPlot()->setLogoFileName('');
			$chart->setTitle(_('Session end status'));
			$chart->setDataSet($dataSet);
			$tmpfile = tempnam(TMP_DIR, REPORT_PREFIX);
			$servers[$fqdn]['session_end_status'] = substr($tmpfile, strlen(TMP_DIR.'/'.REPORT_PREFIX));
			$chart->render($tmpfile);
		}

		if (! isset($info2[$fqdn]))
			continue;

		$dataSet_cpu = new XYDataSet();
		$step = max(round(count($info2[$fqdn]['cpu'])/MAX_STEPS), 1);
		$step_i = 0;
		foreach ($info2[$fqdn]['cpu'] as $day => $num) {
			$text = ($step_i%$step == 0?substr($day, -2):'');
			$step_i++;
			
			$b = get_avg_value($num);
			$dataSet_cpu->addPoint(new Point($text, $b));
		}

		$chart = new LineChart();
		$chart->getPlot()->setLogoFileName('');
		$chart->setDataSet($dataSet_cpu);
		$chart->setTitle(_('CPU usage'));
		$tmpfile = tempnam(TMP_DIR, REPORT_PREFIX);
		$servers[$fqdn]['cpu_file'] = substr($tmpfile, strlen(TMP_DIR.'/'.REPORT_PREFIX));
		$chart->render($tmpfile);


		$dataSet_ram = new XYDataSet();
		$step = max(round(count($info2[$fqdn]['ram'])/MAX_STEPS), 1);
		$step_i = 0;
		foreach ($info2[$fqdn]['ram'] as $day => $num) {
			$text = ($step_i%$step == 0?substr($day, -2):'');
			$step_i++;
			
			$b = get_avg_value($num);
			$dataSet_ram->addPoint(new Point($text, $b));
		}


		$chart = new LineChart();
		$chart->getPlot()->setLogoFileName('');
		$chart->setDataSet($dataSet_ram);
		$chart->setTitle(_('RAM usage'));
		$tmpfile = tempnam(TMP_DIR, REPORT_PREFIX);
		$servers[$fqdn]['ram_file'] = substr($tmpfile, strlen(TMP_DIR.'/'.REPORT_PREFIX));
		$chart->render($tmpfile);
	}


	page_header(array('js_files' => array('media/script/lib/calendarpopup/CalendarPopup.js')));
	echo '<h1>'._('Reporting').'</h1>';

	echo '<div id="calendar_day_from" style="position: absolute; visibility: hidden; background: white;"></div>';
	echo '<div id="calendar_day_to" style="position: absolute; visibility: hidden; background: white;"></div>';

	echo '<script type="text/javascript" charset="utf-8">';
	echo '  document.write(getCalendarStyles());';
	echo '  var calendar_day_from = new CalendarPopup("calendar_day_from");';
	echo '  calendar_day_from.setReturnFunction(\'func_day_from\');';
	echo '  function func_day_from(y,m,d) {';
	echo '    if (m < 10)';
	echo '      m = \'0\'+m;';
	echo '    if (d < 10)';
	echo '      d = \'0\'+d;';
	echo '    $(\'from\').value = y+\'-\'+m+\'-\'+d;';
	echo '    $(\'anchor_day_from\').innerHTML = $(\'from\').value;';
	echo '  }';

	echo '  var calendar_day_to = new CalendarPopup("calendar_day_to");';
	echo '  calendar_day_to.setReturnFunction(\'func_day_to\');';
	echo '  function func_day_to(y,m,d) {';
	echo '    if (m < 10)';
	echo '      m = \'0\'+m;';
	echo '    if (d < 10)';
	echo '      d = \'0\'+d;';
	echo '    $(\'to\').value = y+\'-\'+m+\'-\'+d;';
	echo '    $(\'anchor_day_to\').innerHTML = $(\'to\').value;';
	echo '    }';

	
	echo '  Event.observe(window, \'load\', function() {';
	echo '    func_day_from('.date('Y', $t0).', '.date('m', $t0).', '.date('d', $t0).');';
	echo '    func_day_to('.date('Y', $t1).', '.date('m', $t1).', '.date('d', $t1).');';
	echo '  });';
	

	echo '  function generateReport() {';
	echo '    $(\'from\').value+= \' \'+$(\'hour_from\').value+\':00\';';
	echo '    $(\'to\').value+= \' \'+$(\'hour_to\').value+\':00\';';
	echo '    return true;';
	echo '  }';
	echo '</script>';

	echo '<form onsubmit="return generateReport();">';
	echo '<input id="from" name="from" value="" type="hidden" />';
	echo '<input id="to" name="to" value="" type="hidden" />';

	echo '<strong>'._('From').'</strong> ';
	echo '<a href="#" id="anchor_day_from" onclick="calendar_day_from.select($(\'from\'), \'anchor_day_from\'); return false;" >'.date('Y-m-d', $t0).'</a> ';

	echo '<select id="hour_from">';
	for ($i = 0; $i < 24; $i++) {
		echo '<option value="'.$i.'"';
		if ((int)date('H', $t0) == $i)
			echo ' selected="selected"';
		echo '>'.$i.':00</option>';
	}
	echo '</select> ';

	echo '<strong>'._('to').'</strong> ';
	echo '<a href="#" id="anchor_day_to" onclick="calendar_day_to.select($(\'to\'), \'anchor_day_to\'); return false;" >'.date('Y-m-d', $t1).'</a> ';

	echo '<select id="hour_to">';
	for ($i = 0; $i < 24; $i++) {
		echo '<option value="'.$i.'"';
		if ((int)date('H', $t1) == $i)
			echo ' selected="selected"';
		echo '>'.$i.':00</option>';
	}
	echo '</select> ';

	echo '<strong>'._('step').'</strong>';
	echo '<select name="mode">';
	echo '<option value="minute"';
	if ($mode_->get_value() == 'minute')
		echo ' selected="selected"';
	echo '>'.ReportMode_minute::get_name().'</option>';
	echo '<option value="hour"';
	if ($mode_->get_value() == 'hour')
		echo ' selected="selected"';
	echo '>'.ReportMode_hour::get_name().'</option>';
	echo '<option value="day"';
	if ($mode_->get_value() == 'day')
		echo ' selected="selected"';
	echo '>'.ReportMode_day::get_name().'</option>';
	echo '</select> ';

	echo '<input type="submit" value="'._('Refresh').'" />';
	echo '</form>';

	echo '<div>';
	echo '<table><tr><td>';
	echo '<img src="?img=1&file='.$file_id.'" />';
	echo '</td><td><i>';
	if ($mode_->get_value() == 'day')
		echo _('Abscissa: day of the month');
	else if ($mode_->get_value() == 'hour')
		echo _('Abscissa: hour of the day');
	else if ($mode_->get_value() == 'minute')
		echo _('Abscissa: minute of the hour');
	echo '<br/>';
	echo _('Ordinate: number of sessions');
	echo '</i></td></tr>';

	echo '<tr><td>';
	echo '<img src="?img=nb_sessions&mode='.$mode_->get_value().'&from='.date('Y-m-d H:i:s', $t0).'&to='.date('Y-m-d H:i:s', $t1).'" />';
	echo '</td><td><i>';
	if ($mode_->get_value() == 'day')
		echo _('Abscissa: day of the month');
	else if ($mode_->get_value() == 'hour')
		echo _('Abscissa: hour of the day');
	else if ($mode_->get_value() == 'minute')
		echo _('Abscissa: minute of the hour');
	echo '<br/>';
	echo _('Ordinate: number of sessions');
	echo '</i></td></tr>';
	
	echo '</table>';

	if ($session_number>0) {
		if (isset($file_id2))
			echo '<img src="?img=1&file='.$file_id2.'" />';
		echo ' ';
		if (isset($file_id3))
			echo '<img src="?img=1&file='.$file_id3.'" />';
	}

	echo '<form action="session-reporting.php">';
	echo '<input type="hidden" name="search_by[]" value="time" />';
	echo '<input type="hidden" name="from" value="'.$t0.'" />';
	echo '<input type="hidden" name="to" value="'.$t1.'" />';
	echo '<input type="submit" value="'._('See archived sessions in this time range').'" />';
	echo '</form>';


	foreach($servers as $fqdn => $value) {
		echo '<hr/>';
		echo '<h2>'._('Server').' '.$fqdn.'</h2>';
		echo '<table>';
		if (isset($value['session_file'])) {
			echo '<tr><td>';
			echo '<img src="?img=1&file='.$value['session_file'].'" />';
			echo '</td><td><i>';
			if ($mode_->get_value() == 'day')
				echo _('Abscissa: day of the month');
			else if ($mode_->get_value() == 'hour')
				echo _('Abscissa: hour of the day');
			else if ($mode_->get_value() == 'minute')
				echo _('Abscissa: minute of the hour');
			echo '<br/>';
			echo _('Ordinate: number of sessions');
			echo '</i></td></tr>';
		}
		
		if (isset($value['session_end_status'])) {
			echo '<tr><td>';
			echo '<img src="?img=1&file='.$value['session_end_status'].'" />';
			echo '</td><td></td></tr>';
		}

		if (isset($value['cpu_file'])) {
			echo '<tr><td>';
			echo '<img src="?img=1&file='.$value['cpu_file'].'" />';
			echo '</td><td><i>';
			if ($mode_->get_value() == 'day')
				echo _('Abscissa: day of the month');
			else if ($mode_->get_value() == 'hour')
				echo _('Abscissa: hour of the day');
			else if ($mode_->get_value() == 'minute')
				echo _('Abscissa: minute of the hour');
			echo '<br/>';
			echo _('Ordinate: CPU usage in percent');
			echo '</i></td></tr>';
		}

		if (isset($value['ram_file'])) {
			echo '<tr><td>';
			echo '<img src="?img=1&file='.$value['ram_file'].'" />';
			echo '</td><td><i>';
			if ($mode_->get_value() == 'day')
				echo _('Abscissa: day of the month');
			else if ($mode_->get_value() == 'hour')
				echo _('Abscissa: hour of the day');
			else if ($mode_->get_value() == 'minute')
				echo _('Abscissa: minute of the hour');
			
			echo '<br/>';
			echo _('Ordinate: RAM usage in percent');
			echo '</i></td></tr>';
		}

		echo '</table>';
	}

	echo '</div>';
	page_footer();
	die();
}


function show_img_nb_session($mode_) {
	$ret =  getNB_SESSION($mode_);
	
	// Number of session chart
	$chart = new LineChart();
	$chart->getPlot()->setLogoFileName('');
	$dataSet = new XYDataSet();
	
	$step = max(round(count($ret)/MAX_STEPS), 1);
	$step_i = 0;
	foreach ($ret as $day => $num) {
		$text = ($step_i%$step == 0?substr($day, -2):'');
		$step_i++;
		
		$dataSet->addPoint(new Point($text, $num));
	}

	$chart->setDataSet($dataSet);
	$chart->setTitle(_('Number of active sessions'));

	header('Content-Type: image/png');
	$chart->render();
	die();
}

function getNB_SESSION($mode_) {
	if (isset($_GET['from']))
		$t0 = strtotime($_GET['from']);
	else
		$t0 = strtotime($mode_->get_default_from());

	if (isset($_GET['to']))
		$t1 = strtotime($_GET['to']);
	else
		$t1 = time();

	if (isset($_GET['server']))
		$server_part = 'AND server="'.$_GET['server'].'"';
	else
		$server_part = '';

	$t0 = $mode_->transform_date($t0);
	$t1 = $mode_->transform_date($t1);
	if ($t0>$t1) {
		popup_error(_('Error: "from" date is after "to" date, switching'));
		$buf = $t0;
		$t0 = $t1;
		$t1 = $buf;
	}

	if ($t1 > time()) {
		popup_error(_('Error: "to" field is in the future, switch to the current time'));
		$t1 = $mode_->transform_date(time());
	}

	$t2 = strtotime($mode_->get_next(), $t1);


	$prefs = Preferences::getInstance();
	$mysql_conf = $prefs->get('general', 'sql');

	$sql = SQL::getInstance();
	$res = $sql->DoQuery('SELECT * FROM @1 WHERE @2 BETWEEN %3 AND %4 '.$server_part.' AND ( @5 <= %4 OR ( @6 IS NULL AND id IN (SELECT id FROM @7)))',
						 SESSIONS_HISTORY_TABLE, 'start_stamp', date('c', $t0), date('c', $t2), 
						 'stop_stamp', 'stop_stamp', $mysql_conf['prefix'].'sessions');


	$result_nb_sessions = build_array($t0, $t2, $mode_);

	$g = $sql->FetchAllResults();
	foreach($g as $p) {
		$y = strtotime($p['start_stamp']);
		$buf = date($mode_->get_prefix(), $y);

		foreach($result_nb_sessions as $k => $v) {
			if ($buf > $k) {
				continue;
			}

			if (is_null($p['stop_stamp'])) {
				$result_nb_sessions[$k]+= 1;
			}
			else {
				$time_stop = strtotime($p['stop_stamp']);
				$str_stop = date($mode_->get_prefix(), $time_stop);

				if ($str_stop >= $k) {
					$result_nb_sessions [$k] += 1;
				}
				else {
					break;
				}
			}
		}
	}

	return $result_nb_sessions;
}


if (isset($_GET['img']) && isset($_GET['file']))
	show_img($_GET['file']);

clean_cache();

if (! isset($_GET['mode']))
	$_GET['mode'] = '';
	
switch($_GET['mode']) {
	case 'minute':
		$mode = new ReportMode_minute();
		break;
	case 'hour':
		$mode = new ReportMode_hour();
		break;
	case 'day':
	default:
		$mode = new ReportMode_day();
		break;
}

if (isset($_GET['img']) && $_GET['img'] == 'nb_sessions') {
	show_img_nb_session($mode);
}


show_page($mode);
