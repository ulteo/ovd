<?php
/**
 * Copyright (C) 2008 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com>
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

$prefs_ = Preferences::getInstance();
if (! $prefs_) {
	$title_ = DEFAULT_PAGE_TITLE;
	$logo_url_ = DEFAULT_LOGO_URL;
} else {
	$title_ = $prefs_->get('general', 'main_title');
	$logo_url_ = $prefs_->get('general', 'logo_url');
}

$base_url = str_replace('/admin', '', dirname($_SERVER['PHP_SELF'])).'/';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>
		<?php
		if (in_admin())
			echo $title_.' - '._('Administration');
		else
			echo $title_;
		?>
		</title>

		<!-- <meta http-equiv="Content-Type" content="application/xhtml+xml; charset=utf-8" /> -->
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

		<link rel="shortcut icon" type="image/png" href="<?php echo $base_url; ?>media/image/favicon.ico" />

		<link rel="stylesheet" type="text/css" href="<?php echo $base_url; ?>media/style/common.css" />
		<link rel="stylesheet" type="text/css" href="<?php echo $base_url; ?>admin/media/style/common.css" />

		<link rel="stylesheet" type="text/css" href="<?php echo $base_url; ?>media/script/lib/nifty/niftyCorners.css" />
		<script type="text/javascript" src="<?php echo $base_url; ?>media/script/lib/nifty/niftyCorners.js" charset="utf-8"></script>
		<script type="text/javascript" charset="utf-8">
			NiftyLoad = function() {
				Nifty('div.rounded');
			}
		</script>

		<script type="text/javascript" src="<?php echo $base_url; ?>media/script/lib/prototype/prototype.js" charset="utf-8"></script>
		<script type="text/javascript" src="<?php echo $base_url; ?>media/script/lib/scriptaculous/scriptaculous.js" charset="utf-8"></script>
		<script type="text/javascript" src="<?php echo $base_url; ?>media/script/lib/scriptaculous/slider.js" charset="utf-8"></script>

		<script type="text/javascript" src="<?php echo $base_url; ?>media/script/common.js" charset="utf-8"></script>

		<script type="text/javascript" src="<?php echo $base_url; ?>media/script/sortable.js" charset="utf-8"></script>

		<script type="text/javascript" src="<?php echo $base_url; ?>admin/media/script/common.js" charset="utf-8"></script>
		<script type="text/javascript" src="<?php echo $base_url; ?>admin/media/script/ajax/configuration.js" charset="utf-8"></script>
	</head>

	<body>
		<div id="mainWrap">
			<div id="headerWrap">
				<table style="width: 100%;" border="0" cellspacing="0" cellpadding="0">
					<tr>
						<td style="width: 50%; text-align: right; padding-left: 10px;" class="menu">
						<?php
							if (isset($_SESSION['admin_login'])) {
								if (isset($prefs_)) {
									require_once(dirname(__FILE__).'/menu.php');
								}
							}
						?>
						</td>
						<!--<td style="text-align: center; width: 100%;" class="title centered">
							<h1 class="centered">
							<?php
							if (in_admin())
								echo $title_.' - '._('Administration');
							else
								echo $title_;
							?>
							</h1>
						</td>-->
						<td style="text-align: right; padding-right: 10px;" class="logo">
						<?php
							if (isset($logo_url_) && $logo_url_ != '')
								echo '<a href="index.php"><img src="'.$logo_url_.'" alt="'.$title_.'" title="'.$title_.'" /></a>';
						?>
						</td>
					</tr>
				</table>
			</div>

			<div class="spacer"></div>

			<div id="pageWrap">
				<br />
					<?php
					if (isset($_GET['error']) && $_GET['error'] != '')
						echo '<p class="msg_error">'.$_GET['error'].'</p><br /><br  />';
					?>
<script type="text/javascript" src="../media/script/lib/prototype/prototype.js" charset="utf-8"></script>
<script type="text/javascript" src="../media/script/lib/scriptaculous/scriptaculous.js" charset="utf-8"></script>
<script type="text/javascript" src="../media/script/lib/scriptaculous/slider.js" charset="utf-8"></script>
<script type="text/javascript" src="media/script/common.js" charset="utf-8"></script>
<script type="text/javascript" src="media/script/ajax/appsgroup.js" charset="utf-8"></script>
<script type="text/javascript" src="media/script/ajax/user.js" charset="utf-8"></script>
<script type="text/javascript" src="media/script/ajax/application.js" charset="utf-8"></script>
<script type="text/javascript" src="media/script/ajax/usersgroup.js" charset="utf-8"></script>
<script type="text/javascript" src="media/script/ajax/server.js" charset="utf-8"></script>
<script type="text/javascript" src="media/script/ajax/configuration.js" charset="utf-8"></script>
<link rel="stylesheet" type="text/css" href="media/style/common.css" />

<div id="adminHeader">
<?php
	if (isset($_SESSION['admin_login'])) {
		if (isset($prefs_)) {
			require_once(dirname(__FILE__).'/menu.php');
		}
	}
?>
</div>

<div style="clear: both;"></div>

<div id="adminContent">
	<?php
		if (isset($_SESSION['errormsg']) && is_array($_SESSION['errormsg'])) {
			if (count($_SESSION['errormsg']) > 0) {
			?>
			<div id="adminError">
				<span class="msg_error"><?php
					if (count($_SESSION['errormsg']) > 1) {
						echo '<ul>';
						foreach ($_SESSION['errormsg'] as $error_msg)
							echo '<li>'.$error_msg.'</li>';
						echo '</ul>';
					} else
						echo $_SESSION['errormsg'][0];
				?></span>
			</div>
			<?php
			}

			unset($_SESSION['errormsg']);
		}
	?>
