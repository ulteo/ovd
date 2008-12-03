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

$prefs2 = Preferences::getInstance();
if (! $prefs2)
	$main_title = DEFAULT_PAGE_TITLE;
else
	$main_title = $prefs2->get('general', 'main_title');

header_static($main_title.' - '._('Administration'));
?>
<script type="text/javascript" src="media/script/ajax/common.js" charset="utf-8"></script>
<script type="text/javascript" src="media/script/ajax/appsgroup.js" charset="utf-8"></script>
<script type="text/javascript" src="media/script/ajax/user.js" charset="utf-8"></script>
<script type="text/javascript" src="media/script/ajax/application.js" charset="utf-8"></script>
<script type="text/javascript" src="media/script/ajax/usersgroup.js" charset="utf-8"></script>
<script type="text/javascript" src="media/script/ajax/server.js" charset="utf-8"></script>
<script type="text/javascript" src="media/script/ajax/configuration.js" charset="utf-8"></script>
<link rel="stylesheet" type="text/css" href="media/style/common.css" />

<div id="adminHeader"></div>

<table class="admin_table" border="0" cellspacing="0" cellpadding="0">
	<tr>
		<td class="menu">
			<div id="adminMenu">
			<?php
				if (isset($_SESSION['admin_login'])) {
					if (isset($prefs2)) {
						require_once(dirname(__FILE__).'/menu.php');
					}
				}
			?>
			</div>
		</td>
		<td class="content">
			<div id="adminContent">
				<?php
					if (isset($_SESSION['errormsg']) && $_SESSION['errormsg'] != '') {
						?>
						<div id="adminError">
							<span class="msg_error"><?php echo $_SESSION['errormsg']; ?></span>
						</div>
						<?php
						unset($_SESSION['errormsg']);
					}
				?>
