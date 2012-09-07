<?php
/**
 * Copyright (C) 2010-2012 Ulteo SAS
 * http://www.ulteo.com
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2010
 * Author Julien LANGLOIS <julien@ulteo.com> 2011
 * Author Omar AKHAM <oakham@ulteo.com> 2011
 * Author David PHAM-VAN <d.pham-van@ulteo.com> 2012
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

$seconds_to_cache = 86400*15;
$ts = gmdate("D, d M Y H:i:s", time() + $seconds_to_cache) . " GMT";
header("Expires: $ts");
header("Pragma: cache");
header("Cache-Control: max-age=$seconds_to_cache");

$lang = OPTION_LANGUAGE_DEFAULT;
if (array_key_exists('lang', $_REQUEST))
	$lang = $_REQUEST['lang'];

list($translations, $js_translations) = get_available_translations($lang);

header('Content-Type: text/xml; charset=utf-8');

$dom = new DomDocument('1.0', 'utf-8');

$root = $dom->createElement('translations');
$dom->appendChild($root);


foreach ($translations as $id => $string) {
	$node = $dom->createElement('translation');
	$node->setAttribute('id', $id);
	$node->setAttribute('string', $string);
	$root->appendChild($node);
}

foreach ($js_translations as $id => $string) {
	$node = $dom->createElement('js_translation');
	$node->setAttribute('id', $id);
	$node->setAttribute('string', $string);
	$root->appendChild($node);
}

echo $dom->saveXML();
die();
