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
include(SESSIONMANAGER_ROOT.'/extra/libchart/classes/libchart.php');

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


function get_session_history($t0, $t1, $mode_) {
	$result = build_array($t0, $t1, $mode_);
	$res_server = array();

	$sql = SQL::getInstance();
	$res = $sql->DoQuery('SELECT * FROM @1 WHERE @2 BETWEEN %3 AND %4;',
						SESSIONS_HISTORY_TABLE, 'start_stamp', date('c', $t0), date('c', $t1));


	$g = $sql->FetchAllResults();
	foreach($g as $p) {
		$y = strtotime($p['start_stamp']);
		$buf = date($mode_->get_prefix(), $y);

		if (! isset($result[$buf]))
			continue;

		if (! isset($res_server[$p['server']]))
			$res_server[$p['server']] = build_array($t0, $t1, $mode_);
		$res_server[$p['server']][$buf]++;

		$result[$buf]++;
	}
	$session_number = count($g);

	return array($session_number, $result, $res_server);
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
	$t2 = strtotime($mode_->get_next(), $t1);

	/* General system */
	list($session_number, $result, $res_server) = get_session_history($t0, $t2, $mode_);
		$info2 = get_server_history($t0, $t2, $mode_);


	$chart = new LineChart();
	$chart->getPlot()->setLogoFileName('');
	$dataSet = new XYDataSet();
	foreach ($result as $day => $num)
		$dataSet->addPoint(new Point(substr($day, -2), $num));


	$chart->setDataSet($dataSet);
	$chart->setTitle(_('Number of sessions'));

	$tmpfile = tempnam(TMP_DIR, REPORT_PREFIX);
	$file_id = substr($tmpfile, strlen(TMP_DIR.'/'.REPORT_PREFIX));
	$chart->render($tmpfile);

	if ($session_number>0) {
		$dataSet = new XYDataSet();
		foreach($res_server as $fqdn => $c) {
			$tot = 0;
			foreach($c as $k => $v)
				$tot+= $v;
			$value = ($tot*100)/$session_number;

			$dataSet->addPoint(new Point($fqdn, $value));
		}

		$chart = new PieChart();
		$chart->getPlot()->setLogoFileName('');
		$chart->setTitle(_('Session repartition'));
		$chart->setDataSet($dataSet);
		$tmpfile = tempnam(TMP_DIR, REPORT_PREFIX);
		$file_id2 = substr($tmpfile, strlen(TMP_DIR.'/'.REPORT_PREFIX));
		$chart->render($tmpfile);
	}


	// Foreach server
	$servers = array();
	foreach($res_server as $fqdn => $value) {
		$servers[$fqdn] = array();

		$chart = new LineChart();
		$chart->getPlot()->setLogoFileName('');
		$dataSet = new XYDataSet();
		foreach ($value as $day => $num)
			$dataSet->addPoint(new Point(substr($day, -2), $num));


		$chart->setDataSet($dataSet);
		$chart->setTitle(_('Number of sessions'));
		$tmpfile = tempnam(TMP_DIR, REPORT_PREFIX);
		$servers[$fqdn]['session_file'] = substr($tmpfile, strlen(TMP_DIR.'/'.REPORT_PREFIX));
		$chart->render($tmpfile);

		if (! isset($info2[$fqdn]))
			continue;

		$dataSet_cpu = new XYDataSet();
		foreach ($info2[$fqdn]['cpu'] as $day => $num) {
			$b = get_avg_value($num);
			$dataSet_cpu->addPoint(new Point(substr($day, -2), $b));
		}

		$chart = new LineChart();
		$chart->getPlot()->setLogoFileName('');
		$chart->setDataSet($dataSet_cpu);
		$chart->setTitle(_('CPU usage'));
		$tmpfile = tempnam(TMP_DIR, REPORT_PREFIX);
		$servers[$fqdn]['cpu_file'] = substr($tmpfile, strlen(TMP_DIR.'/'.REPORT_PREFIX));
		$chart->render($tmpfile);


		$dataSet_ram = new XYDataSet();
		foreach ($info2[$fqdn]['ram'] as $day => $num) {
			$b = get_avg_value($num);
			$dataSet_ram->addPoint(new Point(substr($day, -2), $b));
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
	echo '<option value="hour"';
	if ($mode_->get_value() == 'hour')
		echo ' selected="selected"';
	echo '>hour</option>';
	echo '<option value="day"';
	if ($mode_->get_value() == 'day')
		echo ' selected="selected"';
	echo '>day</option>';
	echo '</select> ';

	echo '<input type="submit" value="'._('Refresh').'" />';
	echo '</form>';

	echo '<div>';
	echo '<table><tr><td>';
	echo '<img src="?img=1&file='.$file_id.'" />';
	echo '</td><td><i>';
	if ($mode_->get_value() == 'day')
		echo _('Abscissa: day of the month');
	else
		echo _('Abscissa: hour of the day');
	echo '<br/>';
	echo _('Ordinate: number of session');
	echo '</i></td></tr>';
	echo '</table>';

	if ($session_number>0)
		echo '<img src="?img=1&file='.$file_id2.'" />';


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
			else
				echo _('Abscissa: hour of the day');
			echo '<br/>';
			echo _('Ordinate: number of session');
			echo '</i></td></tr>';
		}

		if (isset($value['cpu_file'])) {
			echo '<tr><td>';
			echo '<img src="?img=1&file='.$value['cpu_file'].'" />';
			echo '</td><td><i>';
			if ($mode_->get_value() == 'day')
				echo _('Abscissa: day of the month');
			else
				echo _('Abscissa: hour of the day');
			echo '<br/>';
			echo _('Ordinate: usage of CPU in percent');
			echo '</i></td></tr>';
		}

		if (isset($value['ram_file'])) {
			echo '<tr><td>';
			echo '<img src="?img=1&file='.$value['ram_file'].'" />';
			echo '</td><td><i>';
			if ($mode_->get_value() == 'day')
				echo _('Abscissa: day of the month');
			else
				echo _('Abscissa: hour of the day');
			echo '<br/>';
			echo _('Ordinate: usage of RAM in percent');
			echo '</i></td></tr>';
		}

		echo '</table>';
	}

	echo '</div>';
	page_footer();
	die();
}

if (isset($_GET['img']) && isset($_GET['file']))
	show_img($_GET['file']);

clean_cache();

if (! isset($_GET['mode']) ||
    ! in_array($_GET['mode'], array('hour', 'day')) ||
    $_GET['mode'] == 'day')
	$mode = new ReportMode_day();
else
	$mode = new ReportMode_hour();

show_page($mode);
