/**
 * Copyright (C) 2009-2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com>, 2009
 * Author Jeremy DESVAGES <jeremy@ulteo.com>, 2009
 * Author Julien LANGLOIS <julien@ulteo.com>, 2010
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

var available_applications;

function fetchInstallableApplicationsList(fqdn_) {
	$('installableApplicationsList_content').innerHTML = '<div style="width: 5%; text-align: center; margin-top: 10px;"><img src="media/image/loader.gif" width="16" height="16" alt="" title="" /></div>';
	
	new Ajax.Request(
		'ajax/installable_applications.php',
		{
			method: 'get',
			parameters: {
				fqdn: fqdn_
			},
			onSuccess: function(transport) {
				var xml = transport.responseXML;
				var list = {};
				
				try {   
					xml.documentElement.nodeName;                        
				}
				catch(err) {
					InstallableApplicationsError();
					return;
				}
				
				if (xml.documentElement.nodeName != "task") {
					InstallableApplicationsError();
					return;
				}
				
				try {
					var id = xml.documentElement.getAttribute("id");
				} catch(err) {
					InstallableApplicationsError();
					return;
				}
				
				setTimeout(function() {
					InstallableApplicationsWait(id);
				}, 2000);
			},
			onFailure: function(transport) {
				InstallableApplicationsError();
			}
		}
	);
}


function InstallableApplicationsWait(task) {
	new Ajax.Request(
		'ajax/installable_applications.php',
		{
			method: 'get',
			parameters: {
				task: task
			},
			onSuccess: function(transport) {
				var xml = transport.responseXML;
				
				try {   
					xml.documentElement.nodeName;                        
				}
				catch(err) {
					InstallableApplicationsError();
					return;
				}
				
				if (xml.documentElement.nodeName == "task") {
					setTimeout(function() {
						InstallableApplicationsWait(task);
					}, 2000);
					return;
				}
				
				var list = {};
				
				buffer = xml.getElementsByTagName('categories');
				if (buffer.length != 1) {
					InstallableApplicationsError();
					return;
				}
				
				buffer = buffer[0];
				
				categories = buffer.getElementsByTagName('category');
				if (categories.length < 1) {
					InstallableApplicationsError();
					return;
				}
				
				for (i=0; i<categories.length; i++) {
					category = categories[i];
					category_name = category.getAttribute('name');
					
					applications = category.getElementsByTagName('application');
					
					list[category_name] = {};
					for (j=0; j<applications.length; j++) {
						application = applications[j];
						
						app_name = application.getAttribute('name');
						app_package = application.getAttribute('package');
						
						list[category_name][app_name] = app_package;
					}
				}
				
				available_applications = list;
				
				$('installableApplicationsList_content').innerHTML = $('installableApplicationsListDefault').innerHTML;
				
				InstallableApplicationsInitList();
			},
			onFailure: function(transport) {
				InstallableApplicationsError();
			}
		}
	);
}


function InstallableApplicationsError() {
	$('installableApplicationsList_content').innerHTML = '<br /><img src="media/image/error.png" width="16" height="16" alt="" title=""> Internal error<br />';
}


function InstallableApplicationsInitList() {
	var buffer = '';

	buffer+='<select id="installable_applications_category_select" onchange="InstallableApplicationsChangeList();">';
	for (i in available_applications)
		buffer+='<option value="'+i+'">'+i+'</option>';
	buffer+='</select>';

	$('installable_applications_category').innerHTML = buffer;

	InstallableApplicationsChangeList();
}

function InstallableApplicationsChangeList() {
	var subElements = available_applications[$('installable_applications_category_select').value];
	var buffer = '';

	buffer+='<select name="line">';
	for (i in subElements)
		buffer+='<option value="'+subElements[i]+'">'+i+'</option>';
	buffer+='</select>';

	$('installable_applications_application').innerHTML = buffer;
}

var installable_applications_list_already_shown = false;
function toggleInstallableApplicationsList(fqdn_) {
	toggleContent('installableApplicationsList');

	if ($('installableApplicationsList_content').visible() && installable_applications_list_already_shown == false) {
		installable_applications_list_already_shown = true;
 		fetchInstallableApplicationsList(fqdn_);
	}
}
