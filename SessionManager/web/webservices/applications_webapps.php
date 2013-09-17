<?php
/**
 * Copyright (C) 2010-2013 Ulteo SAS
 * http://www.ulteo.com
 * Author Wojciech LICHOTA <wojciech.lichota@stxnext.pl> 2013
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
require_once(dirname(__FILE__).'/../includes/webservices.inc.php');

$server = webservices_load_server($_SERVER['REMOTE_ADDR']);;
if (! $server) {
	Logger::error('main', '(webservices/applications/webapps) Server does not exist (error_code: 0)');
	webservices_return_error(1, 'Server does not exist');
}

if (! $server->isAuthorized()) {
	Logger::error('main', '(webservices/applications/webapps) Server is not authorized (error_code: 2)');
	webservices_return_error(2, 'Server is not authorized');
}

header('Content-Type: text/xml; charset=utf-8');
$dom = new DomDocument('1.0', 'utf-8');

$node = $dom->createElement('webapps');

$applicationDB = ApplicationDB::getInstance();
$webapp_application_DB = WebAppConfDB::getInstance();

$applications = $applicationDB->getList('webapp');

if (is_array($applications)) {
	foreach ($applications as $app) {
		$app_node = $dom->createElement('webapp');
		$app_node->setAttribute('id', $app->getAttribute('id'));
		$app_node->setAttribute('name', $app->getAttribute('name'));
		$app_node->setAttribute('description', $app->getAttribute('description'));
		$app_node->setAttribute('revision', $app->getAttribute('revision'));

		$webapp_configuration_object = $webapp_application_DB->search($app->getAttribute('id'));
		if ($webapp_configuration_object != NULL) {
			$configuration = $webapp_configuration_object->getUpdatedConfguration();
			$config_node = $dom->createElement('configuration');
			$config_node->appendChild($dom->createCDATASection($configuration));
			$app_node->appendChild($config_node);
		}
		$node->appendChild($app_node);
	}
}

$dom->appendChild($node);

echo $dom->saveXML();
exit(0);
