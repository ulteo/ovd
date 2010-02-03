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
	mode: 'portal',
	access_id: 'portal',

	applications: new Hash(),
	applicationsPanel: null,
	running_applications: new Hash(),
	runningApplicationsPanel: null,

	matching_applications: new Hash(),

	initialize: function(applet_version_, applet_main_class_, printing_applet_version_, debug_) {
		Daemon.prototype.initialize.apply(this, [applet_version_, applet_main_class_, printing_applet_version_, debug_]);

		this.applicationsPanel = new ApplicationsPanel($('appsContainer'));
		this.runningApplicationsPanel = new ApplicationsPanel($('runningAppsContainer'));
	},

	do_started: function() {
		Daemon.prototype.do_started.apply(this);

		this.display_news();
		this.list_apps();
	},

	parse_do_started: function(transport) {
		$('splashContainer').hide();

		var applet_html_string = '<applet id="ulteoapplet" name="ulteoapplet" code="org.ulteo.ovd.applet.Portal" codebase="applet/" archive="getopt-signed.jar,log4j-signed.jar,OVDapplet.jar" cache_archive="getopt-signed.jar,log4j-signed.jar,OVDapplet.jar" cache_archive_ex="getopt-signed.jar,log4j-signed.jar,OVDapplet.jar;preload" mayscript="true" width="1" height="1"> \
			<param name="name" value="ulteoapplet" /> \
			<param name="code" value="org.ulteo.ovd.applet.Portal" /> \
			<param name="codebase" value="applet/" /> \
			<param name="archive" value="getopt-signed.jar,log4j-signed.jar,OVDapplet.jar" /> \
			<param name="cache_archive" value="getopt-signed.jar,log4j-signed.jar,OVDapplet.jar" /> \
			<param name="cache_archive_ex" value="getopt-signed.jar,log4j-signed.jar,OVDapplet.jar;preload" /> \
			<param name="mayscript" value="true" /> \
			\
			<param name="onInit" value="daemon.applet_loaded" /> \
			<param name="js_daemon_var" value="daemon" /> \
			<param name="access_nb" value="1" /> \
			<param name="server0" value="'+this.session_server+'" /> \
			<param name="username0" value="'+this.session_login+'" /> \
			<param name="password0" value="'+this.session_password+'" /> \
		</applet>';

		$('portalAppletContainer').show();
		$('portalAppletContainer').innerHTML = applet_html_string;

		return true;
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
				var application = new Application(applicationNodes[i].getAttribute('id'), applicationNodes[i].getAttribute('name'), applicationNodes[i].getAttribute('server'));
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

				var instance = new Running_Application(app_object.id, app_object.name, app_object.server, pid, app_status, this.getContext());
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
	},

	applicationStatus: function(token_, status_) {
		var app_status = 2;

		if (typeof this.running_applications.get(token_) == 'undefined') {
			var app_id = this.matching_applications.get(token_);
			if (typeof app_id == 'undefined')
				return false;

			var app_object = this.applications.get(app_id);
			if (typeof app_object == 'undefined')
				return false;

			var instance = new Running_Application(app_object.id, app_object.name, app_object.server, token_, app_status, this.getContext());
			this.running_applications.set(instance.pid, instance);

			if (status_ == 'started')
				this.runningApplicationsPanel.add(instance);
		} else {
			var instance = this.running_applications.get(token_);
			instance.update(app_status);

			if (status_ == 'stopped')
				this.runningApplicationsPanel.del(instance);
		}

		return true;
	}
});

function applicationStatus(token_, status_) {
	return daemon.applicationStatus(token_, status_);
}
