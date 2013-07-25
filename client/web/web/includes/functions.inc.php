<?php
/**
 * Copyright (C) 2010-2012 Ulteo SAS
 * http://www.ulteo.com
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2010-2011
 * Author Julien LANGLOIS <julien@ulteo.com> 2011, 2012
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

require_once(dirname(__FILE__).'/core.inc.php');

function query_sm($url_) {
	if (! array_key_exists('sessionmanager', $_SESSION['ovd-client']))
		$_SESSION['ovd-client']['sessionmanager'] = array();

	$socket = curl_init($url_);
	curl_setopt($socket, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($socket, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($socket, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($socket, CURLOPT_CONNECTTIMEOUT, 10);
	curl_setopt($socket, CURLOPT_TIMEOUT, (10+5));

	if (array_key_exists('session_var', $_SESSION['ovd-client']['sessionmanager']) && array_key_exists('session_id', $_SESSION['ovd-client']['sessionmanager']))
		curl_setopt($socket, CURLOPT_COOKIE, $_SESSION['ovd-client']['sessionmanager']['session_var'].'='.$_SESSION['ovd-client']['sessionmanager']['session_id']);

	$string = curl_exec($socket);
	$buf = curl_getinfo($socket, CURLINFO_HTTP_CODE);
	curl_close($socket);

	if ($buf != 200)
		return false;

	return $string;
}

function query_sm_post_xml($url_, $xml_) {
	if (! array_key_exists('sessionmanager', $_SESSION['ovd-client']))
		$_SESSION['ovd-client']['sessionmanager'] = array();

	$socket = curl_init($url_);
	curl_setopt($socket, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($socket, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($socket, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($socket, CURLOPT_CONNECTTIMEOUT, 10);
	curl_setopt($socket, CURLOPT_TIMEOUT, (10+5));

	curl_setopt($socket, CURLOPT_HEADER, 1);

	if (array_key_exists('session_var', $_SESSION['ovd-client']['sessionmanager']) && array_key_exists('session_id', $_SESSION['ovd-client']['sessionmanager']))
		curl_setopt($socket, CURLOPT_COOKIE, $_SESSION['ovd-client']['sessionmanager']['session_var'].'='.$_SESSION['ovd-client']['sessionmanager']['session_id']);

	curl_setopt($socket, CURLOPT_POSTFIELDS, $xml_);
	curl_setopt($socket, CURLOPT_HTTPHEADER, array('Connection: close', 'Content-Type: text/xml'));

	$string = curl_exec($socket);
	$http_code = curl_getinfo($socket, CURLINFO_HTTP_CODE);
	$headers_size = curl_getinfo($socket, CURLINFO_HEADER_SIZE);
	curl_close($socket);

	if ($http_code != 200)
		return false;

	$headers = substr($string, 0, $headers_size);
	$body = substr($string, $headers_size);

	if (! array_key_exists('session_var', $_SESSION['ovd-client']['sessionmanager']) || ! array_key_exists('session_id', $_SESSION['ovd-client']['sessionmanager'])) {
		preg_match('@Set-Cookie: (.*)=(.*);@', $headers, $matches);
		if (count($matches) == 3) {
			$_SESSION['ovd-client']['sessionmanager']['session_var'] = $matches[1];
			$_SESSION['ovd-client']['sessionmanager']['session_id'] = $matches[2];
		}
	}

	return $body;
}

function get_available_languages() {
	return array(
// 		array('id' => 'af', 'english_name' => 'Afrikaans'),
// 		array('id' => 'sq', 'english_name' => 'Albanian'),
		array('id' => 'ar-ae', 'english_name' => 'Arabic', 'local_name' => 'العربية'),
 		array('id' => 'bg', 'english_name' => 'Bulgarian', 'local_name' => 'Български'),
// 		array('id' => 'be', 'english_name' => 'Belarusian'),
		array('id' => 'zh-cn', 'english_name' => 'Chinese', 'local_name' => '中文'),
		array('id' => 'ca-es', 'english_name' => 'Catalan', 'local_name' => 'Català'),
// 		array('id' => 'hr', 'english_name' => 'Croatian'),
		array('id' => 'cs-cz', 'english_name' => 'Czech', 'local_name' => 'Česky'),
		array('id' => 'da-dk', 'english_name' => 'Danish', 'local_name' => 'Dansk'),
		array('id' => 'nl', 'english_name' => 'Dutch', 'local_name' => 'Nederlands'),
		array('id' => 'en-us', 'english_name' => 'English (US)'),
		array('id' => 'en-gb', 'english_name' => 'English (GB)'),
// 		array('id' => 'et', 'english_name' => 'Estonian'),
// 		array('id' => 'fo', 'english_name' => 'Faeroese'),
		array('id' => 'fi', 'english_name' => 'Finnish', 'local_name' => 'Suomi'),
// 		array('id' => 'fr-be', 'english_name' => 'French (Belgium)', 'local_name' => 'Français (Belgique)'),
// 		array('id' => 'fr-ca', 'english_name' => 'French (Canada)', 'local_name' => 'Français'),
// 		array('id' => 'fr-ch', 'english_name' => 'French (Switzerland)', 'local_name' => 'Français (Suisse)'),
		array('id' => 'fr', 'english_name' => 'French (France)', 'local_name' => 'Français'),
// 		array('id' => 'fr-lu', 'english_name' => 'French (Luxembourg)', 'local_name' => 'Français'),
		array('id' => 'de', 'english_name' => 'German', 'local_name' => 'Deutsch'),
		array('id' => 'el-gr', 'english_name' => 'Greek', 'local_name' => 'Ελληνικά'),
		array('id' => 'fa-ir', 'english_name' => 'Persian', 'local_name' => 'فارسی'),
		array('id' => 'he-il', 'english_name' => 'Hebrew', 'local_name' => 'עברית'),
// 		array('id' => 'hi', 'english_name' => 'Hindi'),
		array('id' => 'hu', 'english_name' => 'Hungarian', 'local_name' => 'Magyar'),
		array('id' => 'is', 'english_name' => 'Icelandic', 'local_name' => 'Íslenska'),
		array('id' => 'id', 'english_name' => 'Indonesian', 'local_name' => 'Bahasa Indonesia'),
		array('id' => 'it', 'english_name' => 'Italian', 'local_name' => 'Italiano'),
		array('id' => 'ja-jp', 'english_name' => 'Japanese', 'local_name' => '日本語'),
		array('id' => 'ko-kr', 'english_name' => 'Korean', 'local_name' => '한국어'),
// 		array('id' => 'lv', 'english_name' => 'Latvian'),
// 		array('id' => 'lt', 'english_name' => 'Lithuanian', 'local_name' => 'Lietuvių'),
// 		array('id' => 'mt', 'english_name' => 'Maltese'),
		array('id' => 'nb-no', 'english_name' => 'Norwegian (Bokmal)', 'local_name' => 'Norsk (Bokmål)'),
// 		array('id' => 'no', 'english_name' => 'Norwegian (Nynorsk)'),
		array('id' => 'pl', 'english_name' => 'Polish', 'local_name' => 'Polski'),
		array('id' => 'pt', 'english_name' => 'Portuguese', 'local_name' => 'Português'),
		array('id' => 'pt-br', 'english_name' => 'Portuguese (Brazil)', 'local_name' => 'Português (Brasil)'),
		array('id' => 'ro', 'english_name' => 'Romanian', 'local_name' => 'Română'),
		array('id' => 'ru', 'english_name' => 'Russian', 'local_name' => 'Русский'),
		array('id' => 'sk', 'english_name' => 'Slovak', 'local_name' => 'Slovenčina'),
// 		array('id' => 'sl', 'english_name' => 'Slovenian'),
// 		array('id' => 'sb', 'english_name' => 'Sorbian'),
		array('id' => 'es', 'english_name' => 'Spanish (Spain)', 'local_name' => 'Español (España)'),
// 		array('id' => 'sv', 'english_name' => 'Swedish', 'local_name' => 'Svenska'),
// 		array('id' => 'th', 'english_name' => 'Thai'),
// 		array('id' => 'tn', 'english_name' => 'Tswana'),
// 		array('id' => 'tr', 'english_name' => 'Turkish', 'local_name' => 'Türkçe'),
// 		array('id' => 'uk', 'english_name' => 'Ukrainian', 'local_name' => 'Українська'),
// 		array('id' => 've', 'english_name' => 'Venda'),
// 		array('id' => 'vi', 'english_name' => 'Vietnamese', 'local_name' => 'Tiếng Việt'),
	);
}

function get_available_keymaps() {
	return array(
		array('id' => 'ar', 'name' => 'Arabic'),
		array('id' => 'da', 'name' => 'Danish'),
		array('id' => 'de', 'name' => 'German'),
		array('id' => 'en-us', 'name' => 'English (US)'),
		array('id' => 'en-gb', 'name' => 'English (GB)'),
		array('id' => 'es', 'name' => 'Spanish'),
		array('id' => 'fi', 'name' => 'Finnish'),
		array('id' => 'fr', 'name' => 'French'),
		array('id' => 'fr-be', 'name' => 'French (Belgium)'),
		array('id' => 'hr', 'name' => 'Croatian'),
		array('id' => 'it', 'name' => 'Italian'),
		array('id' => 'ja', 'name' => 'Japanese'),
		array('id' => 'lt', 'name' => 'Lithuanian'),
		array('id' => 'lv', 'name' => 'Latvian'),
		array('id' => 'no', 'name' => 'Norwegian (Nynorsk)'),
		array('id' => 'pl', 'name' => 'Polish'),
		array('id' => 'pt', 'name' => 'Portuguese'),
		array('id' => 'pt-br', 'name' => 'Portuguese (Brazil)'),
		array('id' => 'ru', 'name' => 'Russian'),
		array('id' => 'sl', 'name' => 'Slovenian'),
		array('id' => 'sv', 'name' => 'Swedish'),
		array('id' => 'tr', 'name' => 'Turkish')
	);
}
