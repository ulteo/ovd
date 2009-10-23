<?php
/**
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com>
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

Logger::debug('main', 'Starting webservices/installable_applications.php');

if (! isSessionManagerRequest()) {
	Logger::error('main', 'Request not coming from Session Manager');
	header('HTTP/1.1 400 Bad Request');
	die('ERROR - Request not coming from Session Manager');
}

$applications = array();
$desktop_files = glob(CHROOT.'/usr/share/app-install/desktop/*.desktop');

foreach ($desktop_files as $a_desktop_file) {
	$ban_categories = array(' ', 'GTK', 'GNOME', 'Qt', 'KDE', 'X-KDE-More', 'TextEditor', 'Core', 'X-KDE-Utilities-PIM', 'X-KDE-settings-sound', 'X-Ximian-Main', 'X-Novell-Main', 'X-Red-Hat-Base', 'Gtk', 'X-GGZ', 'X-KDE-settings-components', 'X-KDE-settings-hardware', 'X-Fedora', 'X-Red-Hat-Extra', 'X-GNOME-SystemSettings', 'X-GNOME-NetworkSettings', 'X-KDE-Utilities-Desktop', 'X-KDE-systemsettings-network', 'X-KDE-settings-security', 'X-KDE-settings-webbrowsing', 'X-KDE-information', 'X-KDE-settings-system', 'X-KDE-settings-accessibility', 'X-KDE-settings-peripherals', 'X-KDE-KDevelopIDE', 'X-KDE-systemsettings-lookandfeel-appearance', 'X-KDE-Edu-Language', 'X-KDE-settings-desktop', 'X-KDE-settings-looknfeel', 'X-KDE-Edu-Misc', 'X-KDE-settings-power', 'X-GNOME-PersonalSettings', 'X-SuSE-Sequencer', 'QT', 'X-Red-Hat-ServerConfig', 'X-Debian-Games-Arcade', 'X-SuSE-Core-System', 'X-KDE-systemsettings-advancedadministration', 'X-SuSE-Core-Game');
	$desktop = parse_ini_file_quotes_safe($a_desktop_file);
	if (isset($desktop['Desktop Entry'])) {
		$desktop = $desktop['Desktop Entry'];
	}
	if (isset($desktop['Name']) && 
// 		isset($desktop['Type']) && 
		isset($desktop['Categories']) && 
		isset($desktop['X-AppInstall-Package'])) {
		$name = $desktop['Name'];
// 		$type = $desktop['Type'];
		$categories = $desktop['Categories'];
		$package = $desktop['X-AppInstall-Package'];
		$cats = explode(';', $categories);
		
		$cat = array_shift($cats);
		while (in_array($cat, $ban_categories) and (!is_null($cat))) {
			$cat = array_shift($cats);
		}
		
		if (($cat === '') or (is_null($cat))) {
			$cat = 'Others';
		}
		
		if (! isset($applications[$cat])) {
			$applications[$cat] = array();
		}
		$applications[$cat][$name]= $package;
	}
}

header('Content-Type: text/xml; charset=utf-8');

$dom = new DomDocument('1.0', 'utf-8');
$categories_node = $dom->createElement('categories');
foreach ($applications as $category => $apps) {
	
	$category_node = $dom->createElement('category');
	$category_node->setAttribute('name', $category);
	foreach($apps as $name => $package) {
		$application_node = $dom->createElement('application');
		$application_node->setAttribute('name', $name);
		$application_node->setAttribute('package', $package);
		$category_node->appendChild($application_node);
	}
	$categories_node->appendChild($category_node);
	
}
$dom->appendChild($categories_node);

echo $dom->saveXML();
