<?php
/**
 * Copyright (C) 2009-2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET  <laurent@ulteo.com>
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

require_once(dirname(__FILE__).'/includes/core.inc.php');
require_once(dirname(__FILE__).'/includes/page_template.php');

if (! checkAuthorization('viewConfiguration'))
	redirect('index.php');


page_header();

$prefs = Preferences::getInstance();
if (! $prefs) {
	die_error('get Preferences failed',__FILE__,__LINE__);
}
?>

<table style="width: 100%;" border="0" cellspacing="3" cellpadding="5">
  <tr>

  <td style="padding: 20px; vertical-align: top;">
  <div class="container rounded" style="background: #eee; width: 98%; margin-left: auto; margin-right: auto;">
  <div>
  <h2><?php echo _('Last save'); ?></h2>
<?php
  if (file_exists(SESSIONMANAGER_CONFFILE_SERIALIZED))
    echo date('m/d/Y H:i:s', @filemtime(SESSIONMANAGER_CONFFILE_SERIALIZED));
  else
    echo _('Unknown');
?>
  </div>
  </div>
  </td>

  <td style="padding: 20px; vertical-align: top;">
  <div class="container rounded" style="background: #eee; width: 98%; margin-left: auto; margin-right: auto;">
  <div>
  <h2><a href="configuration-partial.php?mode=sql">Database</a></h2>
<?php
$sql_conf = $prefs->get('general', 'sql');
?>
  <ul>
  <li><strong>Host</strong>: <?php echo $sql_conf['host']; ?></li>
  <li><strong>User</strong>: <?php echo $sql_conf['user']; ?></li>
  <li><strong>Database</strong>: <?php echo $sql_conf['database']; ?></li>
  <li><strong>Prefix</strong>: <?php echo $sql_conf['prefix']; ?></li>
  </ul>
  </div>
  </div>
  </td>

  <td style="padding: 20px; vertical-align: top;">
  <div class="container rounded" style="background: #eee; width: 98%; margin-left: auto; margin-right: auto;">
  <div>
  <h2><a href="configuration-partial.php?mode=general"><?php echo _('Logs'); ?></a></h2>
<?php
$log_flags = $prefs->get('general', 'log_flags');
?>
  <ul>
<?php
if (is_array($log_flags) && count($log_flags) > 0)
	foreach ($log_flags as $log_flag)
		echo '<li>'.$log_flag.'</li>';
?>
  </ul>
  </div>
  </div>
  </td>

  </tr>

  <tr>

  <td style="padding: 20px; vertical-align: top;">
  <div class="container rounded" style="background: #eee; width: 98%; margin-left: auto; margin-right: auto;">
  <div>
  <h2><a href="configuration-partial.php?mode=slave_server_settings"><?php echo _('Slave Server'); ?></a></h2>
  <ul>
<?php
$slave_server_settings = $prefs->get('general', 'slave_server_settings');
?>
    <li><strong><?php echo _('Authorized FQDN'); ?></strong>:<ul>
<?php
if (is_array($slave_server_settings['authorized_fqdn']) && count($slave_server_settings['authorized_fqdn']) > 0)
	foreach ($slave_server_settings['authorized_fqdn'] as $authorized_fqdn)
		echo '<li>'.$authorized_fqdn.'</li>';
?>
    </ul></li>
	<li><strong><?php echo _('FQDN check'); ?></strong>:
<?php
if ($slave_server_settings['disable_fqdn_check'] == 1)
	echo 'disabled';
else
	echo 'enabled';
?>
  </ul>
  </div>
  </div>
  </td>

  <td style="padding: 20px; vertical-align: top;" colspan="2">
  <div class="container rounded" style="background: #eee; width: 98%; margin-left: auto; margin-right: auto;">
  <div>
<?php
$buf = getProfileMode($prefs);
$buf = new $buf();
?>
  <h2><a href="configuration-profile.php">Profile - <?php echo $buf->getPrettyName(); ?></a></h2>
<?php
$buf = $buf->display_sumup($prefs);
echo $buf;
?>
  </div>
  </div>
  </td>

  </tr>
</table>
<?php
page_footer();
die();
