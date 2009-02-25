<?php
/**
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Gauvain Pocentek <gauvain@ulteo.com>
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

page_header();

$last_report = get_from_cache('reports', 'last_report');
if ($last_report == NULL)
	$last_report = array();

if (! isset($_REQUEST['start'])) {
	if (isset($last_report['start']))
		$start = $last_report['start'];
	else
		$start = date('Ymd', mktime(0, 0, 0, date('m'), date('d')-1, date('Y')));
} else {
	$start = $_REQUEST['start'];
}

if (! isset($_REQUEST['end'])) {
	if (isset($last_report['end']))
		$end = $last_report['end'];
	else
		$end = date('Ymd');
} else {
	$end = $_REQUEST['end'];
}

if ($end < $start) {
	$tmp = $end;
	$end = $start;
	$start = $tmp;
}

if (! isset($_REQUEST['type'])) {
	if (isset($last_report['type']))
		$type = $last_report['type'];
	else
		$type = 'servers';
} else {
	$type = $_REQUEST['type'];
}

if (! isset($_REQUEST['template'])) {
	if (isset($last_report['template']))
		$template = $last_report['template'];
	else
		$template = 'default';
} else {
	$template = $_REQUEST['template'];
}

$last_report['start'] = $start;
$last_report['end'] = $end;
$last_report['type'] = $type;
$last_report['template'] = $template;
set_cache($last_report, 'reports', 'last_report');

$types_array = array('applications', 'servers');
$types_html = '';
foreach ($types_array as $k) {
	if ($type == $k)
		$selected = ' selected="selected"';
	else
		$selected = '';
	$types_html .= "  <option value=\"$k\"$selected>$k</option>\n";
}

/* load servers and applications informations */
$s = Abstract_Server::load_all();
foreach ($s as $s_obj) {
	$servers_info[$s_obj->getAttribute('fqdn')] = $s_obj;
}

?>

<table>
<form action="report.php" method="get">
  <tr>
    <td>Report type:</td>
    <td>
	  <select name="type">
      <?php echo $types_html; ?>
      </select>
	</td>
  </tr>
  <tr>
    <td>From:</td>
	<td>
	  <input type="text" name="start" maxlength="8" value="<?php echo $start ?>" />
	  (YYYYMMDD)
	</td>
  </tr>
  <tr>
    <td>To:</td>
	<td>
	  <input type="text" name="end" maxlength="8" value="<?php echo $end ?>" />
      (YYYYMMDD)
	</td>
  </tr>
  <tr>
<?php
if (is_file('report-'.$type.'.php')) {
	/* this is the computing part */
	include_once('report-'.$type.'.php');

	/* list available templates */
	echo '    <td>Template:</td>';
	echo '    <td>';
	echo '      <select name="template">';
	foreach (glob('templates/'.$type.'/*.php') as $file) {
		$item = preg_replace ('/\.php$/', '', basename($file));
		if (isset($template) && ($template == $item))
			$s = ' selected="selected"';
		else
			$s = '';
		print "      <option value=\"$item\"$s>$item</option>\n";
	}
	echo '    </select>';
	echo '  </td>';

	$tpl = 'templates/'.$type.'/default.php';
	if (isset($template) &&
	  is_file('templates/'.$type.'/'.$template.'.php')) {
		$tpl = 'templates/'.$type.'/'.$template.'.php';
	}
}
?>
  </tr>
  <tr>
    <td></td>
    <td style="align: right;"><input type="submit" value="Report" /></td>
  </tr>
</form>
</table>
<hr />

<?php
if (isset($tpl)) {
	include($tpl);
}

page_footer();
