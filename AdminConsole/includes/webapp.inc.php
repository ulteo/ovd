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

// Check if application URL prefix is unique.
function checkUrlPrefixUnique($url_prefix, $current_app_id=NULL) {
	// Fetch information about every application aleady in the system.
	$applications = $_SESSION['service']->applications_list();
	foreach ($applications as $app_id => $app) {
		// Only consider apps other than the current one.
		// Only consider web apps.
		if ($app_id != $current_app_id && $app->getAttribute('static') && $app->getAttribute('type') == 'webapp') {
			$app = $_SESSION['service']->application_webapp_info($app_id);
			$prefix = $app['url_prefix'];
			if ($url_prefix == $prefix) {
				return FALSE;
			}
		}
	}
	return TRUE;
}
