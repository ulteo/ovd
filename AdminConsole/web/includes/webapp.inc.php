<?php
/**
 * Copyright (C) 2013 Ulteo SAS
 * http://www.ulteo.com
 * Author Tomasz MACKOWIAK <tomasz.mackowiak@stxnext.pl> 2013
 * Author David PHAM-VAN <d.pham-van@ulteo.com> 2013
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

// Check if application URL prefix is in expected format.
function checkUrlPrefixFormat($url_prefix) {
	return preg_match("/^[a-z0-9]+$/", $url_prefix);
}

// Return url prefix (main config key) of application given by id.
function getUrlPrefix($app_id) {
	$raw_config = $_SESSION['service']->application_webapp_get_raw_configuration($app_id);
	$parsed_config = json_decode($raw_config, True);
	$main_key = current(array_keys($parsed_config));
	return $main_key;
}

// Check if application URL prefix is unique.
function checkUrlPrefixUnique($url_prefix, $current_app_id=NULL) {
	// Fetch information about every application aleady in the system.
	$applications = $_SESSION['service']->applications_list();
	foreach ($applications as $app_id => $app) {
		// Only consider apps other than the current one.
		// Only consider web apps.
		if ($app_id != $current_app_id && $app->getAttribute('static') && $app->getAttribute('type') == 'webapp') {
			$prefix = getUrlPrefix($app_id);
			if ($url_prefix == $prefix) {
				return FALSE;
			}
		}
	}
	return TRUE;
}

// Change url prefix of an application.
function changeUrlPrefix($app_id, $url_prefix) {
	$raw_config = $_SESSION['service']->application_webapp_get_raw_configuration($app_id);
	$parsed_config = json_decode($raw_config, True);
	$main_key = current(array_keys($parsed_config));
	$config_content = $parsed_config[$main_key];
	$transformed_config = array($url_prefix => $config_content);
	$transformed_json = json_encode($transformed_config);
	return $_SESSION['service']->application_webapp_set_raw_configuration($app_id, $transformed_json);
}

?>