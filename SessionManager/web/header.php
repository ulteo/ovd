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
require_once(dirname(__FILE__).'/includes/core.inc.php');

$prefs = Preferences::getInstance();
if (! $prefs)
	die_error('get Preferences failed',__FILE__,__LINE__);

if (isset($_SESSION['login'])) {
	$mods_enable = $prefs->get('general', 'module_enable');
	if (!in_array('UserDB', $mods_enable))
		die_error(_('Module UserDB must be enabled'),__FILE__,__LINE__);

	$mod_user_name = 'UserDB_'.$prefs->get('UserDB', 'enable');
	$userDB = new $mod_user_name();
	$user = $userDB->import($_SESSION['login']);

	if (is_object($user))
		$user_displayname = $user->getAttribute('displayname');
	else
		$user_displayname = $_SESSION['login'];
}

$web_interface_settings = $prefs->get('general', 'web_interface_settings');
$main_title = $web_interface_settings['main_title'];
$logo_url = $web_interface_settings['logo_url'];

if ($base_url == '//')
	$base_url = '/';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title><?php echo $main_title; ?></title>

		<?php //<meta http-equiv="Content-Type" content="application/xhtml+xml; charset=utf-8" /> ?>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

		<link rel="shortcut icon" type="image/png" href="<?php echo $base_url; ?>media/image/favicon.ico" />

		<link rel="stylesheet" type="text/css" href="<?php echo $base_url; ?>media/style/common.css" />

		<link rel="stylesheet" type="text/css" href="<?php echo $base_url; ?>media/script/lib/nifty/niftyCorners.css" />
		<script type="text/javascript" src="<?php echo $base_url; ?>media/script/lib/nifty/niftyCorners.js" charset="utf-8"></script>
		<script type="text/javascript" charset="utf-8">
			NiftyLoad = function() {
				Nifty('div.rounded');
			}
		</script>

		<script type="text/javascript" src="<?php echo $base_url; ?>media/script/lib/prototype/prototype.js" charset="utf-8"></script>
		<script type="text/javascript" src="<?php echo $base_url; ?>media/script/common.js" charset="utf-8"></script>

		<script type="text/javascript" src="<?php echo $base_url; ?>media/script/sortable.js" charset="utf-8"></script>
	</head>

	<body>
		<div id="mainWrap">
			<div id="headerWrap" style="border-bottom: 1px solid #ccc;">
				<table style="width: 100%;" border="0" cellspacing="0" cellpadding="0">
					<tr>
						<td style="text-align: left;" class="logo">
							<?php
								if (isset($logo_url) && $logo_url != '')
									echo '<a href="index.php"><img src="'.$logo_url.'" alt="'.$main_title.'" title="'.$main_title.'" /></a>';
							?>
						</td>
						<td style="text-align: center; width: 100%;" class="title centered">
							<h1 class="centered"><?php echo $main_title; ?></h1>
						</td>
					</tr>
				</table>
			</div>

			<div class="spacer"></div>

			<div id="pageWrap">
				<?php
					if (isset($_GET['error']) && $_GET['error'] != '')
						echo '<p class="msg_error">'.$_GET['error'].'</p><br /><br  />';
