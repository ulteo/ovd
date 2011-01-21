/**
 * Copyright (C) 2009-2010 Ulteo SAS
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

var External = Class.create(Applications, {
	initialize: function(applet_version_, applet_main_class_, debug_) {
		Daemon.prototype.initialize.apply(this, [applet_version_, applet_main_class_, debug_]);

		$('applicationsAppletContainer').innerHTML = '';

		this.applicationsPanel = new ApplicationsPanel($('appsContainer'));
		this.runningApplicationsPanel = new ApplicationsPanel($('runningAppsContainer'));
	},

	connect_servers: function() {
		this.push_log('debug', '[external] connect_servers()');

		Applications.prototype.connect_servers.apply(this);

		setTimeout(this.explorer_loop.bind(this), 5000);

		return true;
	},

	do_started: function() {
		this.push_log('debug', '[external] do_started()');

		Daemon.prototype.do_started.apply(this);

		setTimeout(this.connect_servers.bind(this), 1000);
	},

	list_running_apps: function(applicationsNode_) {
		this.push_log('debug', '[external] list_running_apps(xml@applicationsNode)');

		var runningApplicationsNodes = applicationsNode_.getElementsByTagName('running');

		var apps_in_xml = new Array();

		for (var i=0; i < runningApplicationsNodes.length; i++) {
			var pid = runningApplicationsNodes[i].getAttribute('job');
			var app_status = parseInt(runningApplicationsNodes[i].getAttribute('status'));

			if (typeof this.running_applications.get(pid) == 'undefined') {
				var app_id = runningApplicationsNodes[i].getAttribute('app_id');

				var app_object = this.applications.get(app_id);
				if (typeof app_object == 'undefined')
					continue;

				var instance = new Running_Application(app_object.id, app_object.name, app_object.server, pid, app_status, this.getContext());
				this.running_applications.set(instance.pid, instance);
			} else {
				var instance = this.running_applications.get(pid);
				instance.update(app_status);
			}

			apps_in_xml.push(pid);
		}
	},

	applicationStatus: function(token_, status_) {
		this.push_log('debug', '[external] applicationStatus(token: '+token_+', status: '+status_+')');

		var app_status = 2;

		if (typeof this.running_applications.get(token_) == 'undefined') {
			this.push_log('info', '[external] applicationStatus(token: '+token_+', status: '+status_+') - Creating "running" application "'+token_+'"');

			var app_id = this.liaison_runningapplicationtoken_application.get(token_);
			if (typeof app_id == 'undefined')
				return false;

			var app_object = this.applications.get(app_id);
			if (typeof app_object == 'undefined') {
				this.push_log('error', '[external] applicationStatus(token: '+token_+', status: '+status_+') - Application "'+app_id+'" does not exist');
				return false;
			}

			var instance = new Running_Application(app_object.id, app_object.name, app_object.server, token_, app_status, this.getContext());
			this.running_applications.set(instance.pid, instance);

			if (status_ == 'started')
				this.push_log('info', '[external] applicationStatus(token: '+token_+', status: '+status_+') - Adding "running" application "'+token_+'" to running applications list');
		} else {
			this.push_log('info', '[external] applicationStatus(token: '+token_+', status: '+status_+') - Updating "running" application "'+token_+'" status: "'+app_status+'"');

			var instance = this.running_applications.get(token_);
			instance.update(app_status);

			if (status_ == 'stopped') {
				this.push_log('info', '[external] applicationStatus(token: '+token_+', status: '+status_+') - Deleting "running" application "'+token_+'" from running applications list');

				var app_id = this.liaison_runningapplicationtoken_application.get(token_);
				if (typeof app_id == 'undefined')
					return false;
			}
		}

		return true;
	}
});
