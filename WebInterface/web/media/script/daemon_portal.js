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

var Portal = Class.create(Daemon, {
	applications: new Hash(),
	applicationsPanel: null,
	running_applications: new Hash(),
	runningApplicationsPanel: null,

	initialize: function(applet_version_, applet_main_class_, printing_applet_version_, debug_) {
		Daemon.prototype.initialize.apply(this, [applet_version_, applet_main_class_, printing_applet_version_, debug_]);

		this.applicationsPanel = new ApplicationsPanel($('appsContainer'));
		this.runningApplicationsPanel = new ApplicationsPanel($('runningAppsContainer'));
	},

	loop: function() {
		this.check_status();

		if (this.session_state == 0 || this.session_state == 10) {
			this.start_request();
		} else if (this.session_state == 2 && $('splashContainer').visible() && ! $('portalModeContainer').visible()) {
			if (! this.started)
				this.start();

			this.started = true;
		} else if ((this.old_session_state == 2 && this.session_state != 2) || this.session_state == 3 || this.session_state == 4 || this.session_state == 9) {
			if (! this.started)
				this.error_message = this.i18n['session_close_unexpected'];

			this.do_ended();

			return;
		}

		setTimeout(this.loop.bind(this), 2000);
	},

	start: function() {
		this.access_id = 'portal';

		if (! $('portalModeContainer').visible())
			$('portalModeContainer').show();

		if (! $('portalContainer').visible())
			$('portalContainer').show();

		Daemon.prototype.start.apply(this);

		this.display_news();
		this.list_apps();
	},

	do_ended: function() {
		if ($('portalContainer').visible())
			$('portalContainer').hide();

		if ($('portalModeContainer').visible())
			$('portalModeContainer').hide();

		Daemon.prototype.do_ended.apply(this);
	},

	display_news: function() {
return;
		new Ajax.Updater(
			$('newsContainer'),
			'get_news.php'
		);

		setTimeout(this.display_news.bind(this), 300000);
	},

	list_apps: function() {
		new Ajax.Request(
			'apps.php',
			{
				method: 'get',
				onSuccess: this.parse_list_apps.bind(this)
			}
		);
	},

	parse_list_apps: function(transport) {
		var xml = transport.responseXML;

		var buffer = xml.getElementsByTagName('applications');

		if (buffer.length != 1)
			return;

		var applicationNodes = xml.getElementsByTagName('application');

		for (var i=0; i<applicationNodes.length; i++) {
			try { // IE does not have hasAttribute in DOM API...
				var application = new Application(applicationNodes[i].getAttribute('id'), applicationNodes[i].getAttribute('name'));
				this.applications.set(application.id, application);
				this.applicationsPanel.add(application);
			} catch(e) {
				return;
			}
		}
	},

	list_running_apps: function(applicationsNode_) {
		var runningApplicationsNodes = applicationsNode_.getElementsByTagName('running');

		var apps_in_xml = new Array();
		for (var i = 0; i < runningApplicationsNodes.length; i++) {
			var pid = runningApplicationsNodes[i].getAttribute('job');
			var app_status = parseInt(runningApplicationsNodes[i].getAttribute('status'));

			if (typeof this.running_applications.get(pid) == 'undefined') {
				var app_id = runningApplicationsNodes[i].getAttribute('app_id');

				var app_object = this.applications.get(app_id);
				if (typeof app_object == 'undefined')
					continue;

				var instance = new Running_Application(app_object.id, app_object.name, pid, app_status, this.getContext());
				this.running_applications.set(instance.pid, instance);
				this.runningApplicationsPanel.add(instance);
			} else {
				var instance = this.running_applications.get(pid);
				instance.update(app_status);
			}

			apps_in_xml.push(pid);
		}

		var runnings = this.running_applications.keys();
		if (runnings.length > apps_in_xml.length) {
			for (var i=0; i<runnings.length; i++) {
				if (apps_in_xml.indexOf(runnings[i]) == -1)
					this.runningApplicationsPanel.del(this.running_applications.get(runnings[i]));
			}
		}
	}
});
