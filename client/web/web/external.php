<?php
/**
 * Copyright (C) 2011 Ulteo SAS
 * http://www.ulteo.com
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
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>Ulteo Open Virtual Desktop - external mode</title>

		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

		<script type="text/javascript" src="media/script/lib/prototype/prototype.js" charset="utf-8"></script>

		<script type="text/javascript" src="media/script/lib/scriptaculous/scriptaculous.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/lib/scriptaculous/extensions.js" charset="utf-8"></script>

		<link rel="stylesheet" type="text/css" href="media/script/lib/nifty/niftyCorners.css" />
		<script type="text/javascript" src="media/script/lib/nifty/niftyCorners.js" charset="utf-8"></script>
		<script type="text/javascript" charset="utf-8">
			NiftyLoad = function() {
				Nifty('div.rounded');
			}
		</script>

		<script type="text/javascript">
			var i18n = new Hash();
		</script>

		<link rel="shortcut icon" type="image/png" href="media/image/favicon.ico" />
		<link rel="stylesheet" type="text/css" href="media/style/common.css" />
		<script type="text/javascript" src="media/script/common.js?<?php echo time(); ?>" charset="utf-8"></script>

		<script type="text/javascript" src="media/script/daemon.js?<?php echo time(); ?>" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/daemon_desktop.js?<?php echo time(); ?>" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/daemon_applications.js?<?php echo time(); ?>" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/server.js?<?php echo time(); ?>" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/application.js?<?php echo time(); ?>" charset="utf-8"></script>

		<script type="text/javascript" src="media/script/timezones.js" charset="utf-8"></script>
	</head>

	<body style="margin: 10px; background: #ddd; color: #333;">
		<div id="splashContainer" class="rounded">
			<table style="width: 100%; padding: 10px;" border="0" cellspacing="0" cellpadding="0">
				<tr>
					<td style="text-align: center;" colspan="3">
						<img src="media/image/ulteo.png" alt="" title="" />
					</td>
				</tr>
				<tr>
					<td style="text-align: left; vertical-align: middle; margin-top: 15px;">
						<span style="font-size: 1.35em; font-weight: bold; color: #686868;"><?php echo _('Do not close this Ulteo OVD window!'); ?></span>
					</td>
					<td style="width: 20px"></td>
					<td style="text-align: left; vertical-align: middle;">
						<img src="media/image/rotate.gif" width="32" height="32" alt="" title="" />
					</td>
				</tr>
			</table>
		</div>
	</body>
</html>
