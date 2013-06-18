<?php
/**
 * Copyright (C) 2013 Ulteo SAS
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

require_once(dirname(__FILE__).'/../includes/core.inc.php');

class Application_webapp_configuration extends Application_webapp {

	public function __construct($id_, $application_id, $raw_configuration) {
		Logger::info('api', "Application_webapp_configuration::construct('$id_','$application_id','$raw_configuration')");

		$prefs = Preferences::getInstance();
		if (! $prefs) {
			die_error('get Preferences failed', __FILE__, __LINE__);
		}

		$mods_enable = $prefs->get('general','module_enable');
		if (!in_array('ApplicationDB',$mods_enable)){
			die_error('Module ApplicationDB must be enabled',__FILE__,__LINE__);
		}

		if (!in_array('WebAppConfDB',$mods_enable)){
			die_error('Module WebAppConfDB must be enabled',__FILE__,__LINE__);
		}
		$this->attributes = array();
		$this->attributes['id'] = $id_;
		$this->attributes['application_id'] = $application_id;
		$this->attributes['raw_configuration'] = $raw_configuration;
	}
}
