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
require_once('header.php');

if (! isset($_REQUEST['start']))
	$start = "";
else
	$start = $_REQUEST['start'];

if (! isset($_REQUEST['end']))
	$end = "";
else
	$end = $_REQUEST['end'];

if (! isset($_REQUEST['type']))
	$type = "";
else
	$type = $_REQUEST['type'];

$select_array = array('applications', 'servers');
?>

<form action="report.php" method="get">
  Report type:
  <select name="type">
  <?php
  foreach ($select_array as $k) {
	if ($type == $k)
		$selected = ' selected="selected"';
	else
		$selected = '';
	echo '  <option value="'.$k.'"'.$selected.'>'.$k.'</option>';
  }
  ?>
  </select>
  <br />
  From:  <input type="text" name="start" maxlength="8" value="<?php echo $start ?>" />
  To: <input type="text" name="end" maxlength="8" value="<?php echo $end ?>" />
  (YYYYMMDD)
  <input type="submit" value="Report" />
</form>
<hr />

<?php
if (isset($_REQUEST['type']) && is_file('report-'.$_REQUEST['type'].'.php'))
	include_once('report-'.$_REQUEST['type'].'.php');

require_once('footer.php');
