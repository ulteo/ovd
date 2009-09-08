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
require_once(dirname(__FILE__).'/../includes/core-minimal.inc.php');

Logger::debug('main', 'Starting webservices/get_news.php');

if (! isset($_GET['fqdn'])) {
	Logger::error('main', '(webservices/get_news) Missing parameter : fqdn');
	die('ERROR - NO $_GET[\'fqdn\']');
}

header('Content-Type: text/xml; charset=utf-8');

$dom = new DomDocument('1.0', 'utf-8');
$news_node = $dom->createElement('news');
$dom->appendChild($news_node);

$news = Abstract_News::load_all();
foreach ($news as $new) {
	$new_node = $dom->createElement('new');
	$new_node->setAttribute('title', $new->getAttribute('title'));
	$new_node->setAttribute('timestamp', (int)$new->getAttribute('timestamp'));
	$new_textnode = $dom->createTextNode($new->getAttribute('content'));
	$new_node->appendChild($new_textnode);
	$news_node->appendChild($new_node);
}

$xml = $dom->saveXML();

echo $xml;
