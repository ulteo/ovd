<?php
/**
* Copyright (C) 2009 Ulteo SAS
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
include(SESSIONMANAGER_ROOT.'/extra/libchart/classes/libchart.php');

define('REPORT_PREFIX', 'report_');

function clean_cache() {
	foreach(glob(TMP_DIR.'/'.REPORT_PREFIX.'*') as $name) {
		if (filemtime($name)< time()-300)
		@unlink($name);
	}
}

if (isset($_GET['img'])) {
	if (! isset($_GET['file'])) {
		header('HTTP/1.1 400 Bad Request');
		die();
	}

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


function get_time_window() {
	if (isset($_GET['start']) && is_numeric($_GET['start'])) {
		$start = abs($_GET['start'])-1;
		$s = '-'.$start.' hours';
	}
	else
		$s = '+1 hour';
	
	$t = strtotime($s, mktime(date('H'), 0, 0));
	return array(strtotime('-24 hours', $t), $t);
}

function build_array($t0, $t1, $flag=false) {
	$result = array();
	
	for ($i=$t0; $i<$t1; $i=strtotime('+1 hour', $i)) {
		$key = date('Y-m-d H', $i);
		if ($flag)
			$result[$key] = array();
		else
			$result[$key] = 0;
	}
	
	return $result;
}

function get_server_history($t0, $t1) {

	$sql = MySQL::getInstance();
	$res = $sql->DoQuery('SELECT * FROM @1 WHERE @2 BETWEEN %3 AND %4 ORDER BY @2 ASC;', SERVERS_HISTORY_TABLE, 'timestamp', date('c', $t0), date('c', $t1));

	$infos = array();

	$g = $sql->FetchAllResults();
	foreach($g as $p) {
		if (! isset($infos[$p['fqdn']]))
			$infos[$p['fqdn']] = array('ram' => build_array($t0, $t1, true), 'cpu' => build_array($t0, $t1, true));
	
		$buf = explode(':', $p['timestamp'], 2);
		$buf = $buf[0];
	
		$infos[$p['fqdn']]['ram'][$buf][]= $p['ram'];
		$infos[$p['fqdn']]['cpu'][$buf][]= $p['cpu']*100;
	}
	
	return $infos;
}


clean_cache();


/* General system */
list($t0, $t1) = get_time_window();
$result = build_array($t0, $t1);
$res_server = array();

$sql = MySQL::getInstance();
$res = $sql->DoQuery('SELECT * FROM @1 WHERE @2 BETWEEN %3 AND %4;',
					SESSIONS_HISTORY_TABLE, 'start_stamp', date('c', $t0), date('c', $t1));


$g = $sql->FetchAllResults();
foreach($g as $p) {
	$buf = explode(':', $p['start_stamp'], 2);
	$buf = $buf[0];
	
	if (! isset($result[$buf]))
		continue;
	
	if (! isset($res_server[$p['server']]))
		$res_server[$p['server']] = build_array($t0, $t1);
	$res_server[$p['server']][$buf]++;
	
	
	$result[$buf]++;
}
$session_number = count($g);


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
	$chart->setTitle("Session repartition");
	$chart->setDataSet($dataSet);
	$tmpfile = tempnam(TMP_DIR, REPORT_PREFIX);
	$file_id2 = substr($tmpfile, strlen(TMP_DIR.'/'.REPORT_PREFIX));
	$chart->render($tmpfile);
}


/* Foreach server */
$servers = array();
$info2 = get_server_history($t0, $t1);
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


$choice = array(0 => _('now'), 24 => '1 '._('day'));
for($i=2; $i<10; $i++)
	$choice[24*$i] =  $i.' '._('days');


page_header();

echo '<form>';
echo '<h1>'._('History of 24 hours for');

echo ' <select name="start" onChange="this.form.submit();">';
foreach($choice as $k => $v) {
	echo '<option value="'.$k.'"';
	if (isset($_GET['start']) && $_GET['start']==$k)
		echo ' selected="selected"';
	echo '>'.$v.'</option>';
}
echo '</select>';
echo '</h1>';
echo '</form>';


echo '<div>';
echo '<table><tr><td>';
echo '<img src="?img=1&file='.$file_id.'" />';
echo '</td><td><i>';
echo _('Abscissa: number of session');
echo '<br/>';
echo _('Ordinate: hour of the day');
echo '</i></td></tr>';
echo '</table>';

if ($session_number>0)
	echo '<img src="?img=1&file='.$file_id2.'" />';


foreach($servers as $fqdn => $value) {
	echo '<h2>'._('Server').' '.$fqdn.'</h2>';
	echo '<table>';
	echo '<tr><td>';
	echo '<img src="?img=1&file='.$value['session_file'].'" />';
	echo '</td><td><i>';
	echo _('Abscissa: number of session');
	echo '<br/>';
	echo _('Ordinate: hour of the day');
	echo '</i></td></tr>';
	
	echo '<tr><td>';
	echo '<img src="?img=1&file='.$value['cpu_file'].'" />';
	echo '</td><td><i>';
	echo _('Abscissa: usage of CPU in percent');
	echo '<br/>';
	echo _('Ordinate: hour of the day');
	echo '</i></td></tr>';
	
	echo '<tr><td>';
	echo '<img src="?img=1&file='.$value['ram_file'].'" />';
	echo '</td><td><i>';
	echo _('Abscissa: usage of RAM in percent');
	echo '<br/>';
	echo _('Ordinate: hour of the day');
	echo '</i></td></tr>';
	
	echo '</table>';
}

echo '</div>';
page_footer();
die();
