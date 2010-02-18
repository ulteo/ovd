<?php
/**
 * Copyright (C) 2008-2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com>
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
function redirect($url_=NULL) {
	if (is_null($url_)) {
		if (! isset($_SERVER['HTTP_REFERER'])) {
			global $base_url;
			$url_ = $base_url;
		} else
			$url_ = $_SERVER['HTTP_REFERER'];
	}

	header('Location: '.$url_);
	die();
}

function die_error($error_=false, $file_=NULL, $line_=NULL, $display_=false) {
	$file_ = substr(str_replace(SESSIONMANAGER_ROOT, '', $file_), 1);

	Logger::error('main', 'die_error() called with message \''.$error_.'\' in '.$file_.':'.$line_);

	header('Content-Type: text/xml; charset=utf-8');

	$dom = new DomDocument('1.0', 'utf-8');
	$node = $dom->createElement('error');
	$node->setAttribute('id', 0);
	if (in_admin() || $display_ === true)
		$node->setAttribute('message', $error_);
	else
		$node->setAttribute('message', 'The service is not available, please try again later');
	$dom->appendChild($node);

	echo $dom->saveXML();

	die();
}

function header_static($title_=false) {
	global $base_url;
	if ($base_url == '//')
		$base_url = '/';

	$prefs = Preferences::getInstance();
	if (! $prefs) {
		$title_ = DEFAULT_PAGE_TITLE;
		$logo_url = DEFAULT_LOGO_URL;
	} else {
		$web_interface_settings = $prefs->get('general', 'web_interface_settings');
		$title_ = $web_interface_settings['main_title'];
		$logo_url = $web_interface_settings['logo_url'];
	}

echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>';
		if (in_admin())
			echo $title_.' - '._('Administration');
		else
			echo $title_;
		echo '</title>

		';/*<meta http-equiv="Content-Type" content="application/xhtml+xml; charset=utf-8" />*/echo '
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

		<link rel="shortcut icon" type="image/png" href="'.$base_url.'admin/media/image/favicon.ico" />

		<link rel="stylesheet" type="text/css" href="'.$base_url.'admin/media/style/common.css" />

		<link rel="stylesheet" type="text/css" href="'.$base_url.'admin/media/script/lib/nifty/niftyCorners.css" />
		<script type="text/javascript" src="'.$base_url.'admin/media/script/lib/nifty/niftyCorners.js" charset="utf-8"></script>
		<script type="text/javascript" charset="utf-8">
			NiftyLoad = function() {
				Nifty(\'div.rounded\');
			}
		</script>

		<script type="text/javascript" src="'.$base_url.'admin/media/script/lib/prototype/prototype.js" charset="utf-8"></script>
		<script type="text/javascript" src="'.$base_url.'admin/media/script/lib/scriptaculous/scriptaculous.js" charset="utf-8"></script>
		<script type="text/javascript" src="'.$base_url.'admin/media/script/lib/scriptaculous/slider.js" charset="utf-8"></script>

		<script type="text/javascript" src="'.$base_url.'admin/media/script/common-regular.js" charset="utf-8"></script>

		<script type="text/javascript" src="'.$base_url.'admin/media/script/sortable.js" charset="utf-8"></script>

		<script type="text/javascript" src="'.$base_url.'admin/media/script/common.js" charset="utf-8"></script>
		<script type="text/javascript" src="'.$base_url.'admin/media/script/ajax/configuration.js" charset="utf-8"></script>
	</head>

	<body>
		<div id="mainWrap">
			<div id="headerWrap" style="border-bottom: 1px solid #ccc;">
				<table style="width: 100%;" border="0" cellspacing="0" cellpadding="0">
					<tr>
						<td style="text-align: left" class="logo">';
							if (isset($logo_url) && $logo_url != '')
								echo '<a href="index.php"><img src="'.$logo_url.'" alt="'.$title_.'" title="'.$title_.'" /></a>';
						echo '</td>
						<td style="text-align: center; width: 100%;" class="title centered">
							<h1 class="centered">';
							if (in_admin())
								echo $title_.' - '._('Administration');
							else
								echo $title_;
							echo '</h1>
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
	global $base_url;
	if ($base_url == '//')
		$base_url = '/';

echo '		</div>

			<div class="spacer"></div>

			<div id="footerWrap">
				'._('powered by').' <a href="http://www.ulteo.com"><img src="'.$base_url.'admin/media/image/ulteo.png" width="22" height="22" alt="Ulteo" title="Ulteo" /> Ulteo</a>&nbsp;&nbsp;&nbsp;
			</div>
		</div>
	</body>
</html>';
}

function popup_error($msg_) {
	if (! isset($_SESSION['errormsg']))
		$_SESSION['errormsg'] = array();

	if (is_array($msg_))
		foreach ($msg_ as $errormsg)
			$_SESSION['errormsg'][] = $errormsg;
	else
		$_SESSION['errormsg'][] = $msg_;

	return true;
}

function popup_info($msg_) {
	if (! isset($_SESSION['infomsg']))
		$_SESSION['infomsg'] = array();

	if (is_array($msg_))
		foreach ($msg_ as $infomsg)
			$_SESSION['infomsg'][] = $infomsg;
	else
		$_SESSION['infomsg'][] = $msg_;

	return true;
}
