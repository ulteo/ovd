<?php
/**
 * Copyright (C) 2008-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2008-2010
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2008-2010
 * Author Julien LANGLOIS <julien@ulteo.com> 2008-2011
 * Author Omar AKHAM <oakham@ulteo.com> 2011
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

function header_static($title_=false) {
	$title_ = DEFAULT_PAGE_TITLE;
	$logo_url = DEFAULT_LOGO_URL;
	
	global $html_dir;
	$html_dir = get_component_orientation();
	
	echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="'.$html_dir.'">
	<head>
		<title>'.$title_.' - '._('Administration').'</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		
		<link rel="shortcut icon" type="image/png" href="media/image/favicon.ico" />
		
		<link rel="stylesheet" type="text/css" href="media/style/common.css" />
		
		<link rel="stylesheet" type="text/css" href="media/script/lib/nifty/niftyCorners.css" />
		<script type="text/javascript" src="media/script/lib/nifty/niftyCorners.js" charset="utf-8"></script>
		<script type="text/javascript" charset="utf-8">
			NiftyLoad = function() {
				Nifty(\'div.rounded\');
			}
		</script>
		
		<script type="text/javascript" src="media/script/lib/prototype/prototype.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/lib/scriptaculous/scriptaculous.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/lib/scriptaculous/slider.js" charset="utf-8"></script>
		
		<script type="text/javascript" src="media/script/common-regular.js" charset="utf-8"></script>
		
		<script type="text/javascript" src="media/script/sortable.js" charset="utf-8"></script>
		
		<script type="text/javascript" src="media/script/common.js" charset="utf-8"></script>
		<script type="text/javascript" src="media/script/ajax/configuration.js" charset="utf-8"></script>
	</head>
	
	<body>
		<div id="mainWrap">
			<div id="headerWrap" style="border-bottom: 1px solid #ccc;">
				<table style="width: 100%;" border="0" cellspacing="0" cellpadding="0">
					<tr>
						<td style="text-align: left" class="logo">';
	
	if (isset($logo_url) && $logo_url != '')
		echo '<a href="index.php"><img src="'.$logo_url.'" alt="'.$title_.'" title="'.$title_.'" /></a>';
	
	echo '					</td>
						<td style="text-align: center; width: 100%;" class="title centered">
							<h1 class="centered">'.$title_.' - '._('Administration').'</h1>
						</td>
					</tr>
				</table>
			</div>
			
			<div class="spacer"></div>
			
			<div id="pageWrap">';
	
	if (isset($_GET['error']) && $_GET['error'] != '')
		echo '<p class="msg_error">'.$_GET['error'].'</p><br /><br  />';
}


function footer_static() {
	echo '		</div>
			
			<div class="spacer"></div>
			
			<div id="footerWrap">
				'._('powered by').' <a href="http://www.ulteo.com"><img src="media/image/ulteo.png" width="22" height="22" alt="Ulteo" title="Ulteo" /> Ulteo</a>&nbsp;&nbsp;&nbsp;
			</div>
		</div>
	</body>
</html>';
}
