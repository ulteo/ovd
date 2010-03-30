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
require_once(dirname(__FILE__).'/../includes/core.inc.php');

$xml = query_url(SESSIONMANAGER_URL.'/webservices/get_news.php?fqdn='.SERVERNAME.'&login='.$_SESSION['ovd_session']['parameters']['user_login']);

if (! $xml) {
	Logger::error('main', '(get_news) Unable to fetch news from the SessionManager');
	die();
}

$dom = new DomDocument('1.0', 'utf-8');
@$dom->loadXML($xml);

if (! $dom->hasChildNodes())
	die();

$new_nodes = $dom->getElementsByTagname('new');
if (is_null($new_nodes))
	die();

echo '<table style="width: 100%; margin-left: auto; margin-right: auto;" border="0" cellspacing="0" cellpadding="3">';
foreach ($new_nodes as $new_node) {
	echo '<tr><td style="text-align: left;">';
	$color = 'black';
	if ($new_node->getAttribute('timestamp') > $_SESSION['ovd_session']['connected_since'])
		$color = 'red';
	echo '<span style="font-size: 1.1em; color: '.$color.';"><em>'.date('d/m/Y', $new_node->getAttribute('timestamp')).'</em> - <strong>'.$new_node->getAttribute('title').'</strong> - '.$new_node->firstChild->nodeValue.'</span>';
	echo '</td></tr>';
}
echo '</table>';
