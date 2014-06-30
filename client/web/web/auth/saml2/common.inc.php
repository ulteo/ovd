<?php
/**
 * Copyright (C) 2014 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2014
 * Author David PHAM-VAN <d.pham-van@ulteo.com> 2014
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

require_once(dirname(__FILE__).'/../../includes/core.inc.php');
include_once(dirname(dirname(dirname(__FILE__))).'/PEAR/php-saml/_toolkit_loader.php');

if (!defined('SAML2_REDIRECT_URI'))
	define('SAML2_REDIRECT_URI', OneLogin_Saml2_Utils::getSelfURLhost());


function send_error($message) {
	die(<<<EOF
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<title>SAML2 Error</title>
	<meta http-equiv="content-type" content="text/html;charset=utf-8" />
	<link rel="stylesheet" type="text/css" href="/ovd/media/style/webclient.css" />
</head>
<body>
	<div id="overlay">
		<div class="shadowBox" id="systemTestError">
			<div class="boxLogo">
				<div class="image_error_png"></div>
			</div>
			<h1>SAML2 Error</h1>
			<p>$message</p>
		</div>
	</div>
</body>

</html>
EOF
);
}


function build_saml_settings($idp_url_, $idp_fingerprint_, $x509cert_) {
	if (strlen($idp_fingerprint_) == 0) {
		$idp_fingerprint_ = NULL;
	}
	if (strlen($x509cert_) == 0) {
		$x509cert_ = NULL;
	}
	$settingsInfo = array (
		'strict' => defined("SAML_STRICT") && SAML_STRICT,
		'debug' => defined("SAML_DEBUG") && SAML_DEBUG,
		'sp' => array (
			'entityId' => SAML2_REDIRECT_URI.'/ovd/auth/saml2',
			'assertionConsumerService' => array (
				'url' => SAML2_REDIRECT_URI.$_SERVER['REQUEST_URI'],
				'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
			),
		),
		'idp' => array (
			'entityId' => $idp_url_,
			'singleSignOnService' => array (
				'url' => $idp_url_,
				'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
			),
			
			'certFingerprint' => $idp_fingerprint_,
			'x509cert' => $x509cert_,
		),
	);
	
	return $settingsInfo;
}


function init_saml2_auth() {
	global $sessionmanager_url;

	$sm = new SessionManager($sessionmanager_url);
	$ret = $sm->query('auth_params');
	$dom = new DomDocument('1.0', 'utf-8');
	$buf = @$dom->loadXML($ret);
	if (! $buf) {
		send_error("Unable to retrieve the SAML parameters");
	}
	if (! $dom->hasChildNodes()) {
		send_error("Unable to retrieve the SAML parameters");
	}
	$saml2 = $dom->getElementsByTagname('SAML2')->item(0);
	$url = $saml2->getElementsByTagname('idp_url')->item(0)->textContent;
	$fingerprint = $saml2->getElementsByTagname('idp_fingerprint')->item(0)->textContent;
	$cert = $saml2->getElementsByTagname('idp_cert')->item(0)->textContent;
	$settings = build_saml_settings($url, $fingerprint, $cert);
	return new OneLogin_Saml2_Auth($settings);
}
