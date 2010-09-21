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

var Applications = Class.create(Daemon, {
	mode: 'applications',

	applications: new Hash(),
	applicationsPanel: null,
	running_applications: new Hash(),
	runningApplicationsPanel: null,

	news: new Hash(),

	liaison_runningapplicationtoken_application: new Hash(),

	initialize: function(applet_version_, applet_main_class_, in_popup_, debug_) {
		Daemon.prototype.initialize.apply(this, [applet_version_, applet_main_class_, in_popup_, debug_]);

		$('applicationsAppletContainer').innerHTML = '';

		$('applicationsContainer').style.height = parseInt(this.my_height)-154+'px';
		$('appsContainer').style.height = parseInt(this.my_height)-154+'px';
		$('runningAppsContainer').style.height = parseInt(this.my_height)-154+'px';
		$('fileManagerContainer').style.height = parseInt(this.my_height)-154+'px';

		this.applicationsPanel = new ApplicationsPanel($('appsContainer'));
		this.runningApplicationsPanel = new ApplicationsPanel($('runningAppsContainer'));
	},

	connect_servers: function() {
		this.push_log('debug', '[applications] connect_servers()');

		if (! $('ulteoapplet') || ! $('ulteoapplet').isActive()) {
			this.push_log('warning', '[applications] connect_servers() - Applet is not ready');
			setTimeout(this.connect_servers.bind(this), 1000);
			return;
		}

		var servers = this.servers.values();
		for (var i=0; i < servers.length; i++) {
			this.push_log('info', '[applications] connect_servers() - Connecting to server "'+servers[i].id+'"');
			servers[i].connect();
		}

		return true;
	},

	do_started: function() {
		this.push_log('debug', '[applications] do_started()');

		this.list_apps();

		this.load_explorer();
		this.display_news();

		Daemon.prototype.do_started.apply(this);

		setTimeout(this.connect_servers.bind(this), 1000);
	},

	parse_do_started: function(transport) {
		this.push_log('debug', '[applications] parse_do_started(transport@do_started())');

		var applet_params = new Hash();
		applet_params.set('keymap', this.keymap);
		applet_params.set('multimedia', this.multimedia);
		applet_params.set('redirect_client_printers', this.redirect_client_printers);

		var applet = buildAppletNode('ulteoapplet', this.applet_main_class, 'log4j-1.2.jar,'+this.applet_version, applet_params);
		$('applicationsAppletContainer').show();
		$('applicationsAppletContainer').appendChild(applet);

		this.load_printing_applet();

		return true;
	},

	list_apps: function() {
		this.push_log('debug', '[applications] list_apps()');

		new Ajax.Request(
			'apps.php',
			{
				method: 'get',
				onSuccess: this.parse_list_apps.bind(this)
			}
		);
	},

	parse_list_apps: function(transport) {
		this.push_log('debug', '[applications] parse_list_apps(transport@list_apps())');

		var xml = transport.responseXML;

		var buffer = xml.getElementsByTagName('applications');

		if (buffer.length != 1) {
			this.push_log('error', '[applications] parse_list_apps(transport@list_apps()) - Invalid XML (No "applications" node)');
			return;
		}

		var applicationNodes = xml.getElementsByTagName('application');

		for (var i=0; i < applicationNodes.length; i++) {
			try { // IE does not have hasAttribute in DOM API...
				this.push_log('info', '[applications] parse_list_apps(transport@list_apps()) - Adding application "'+applicationNodes[i].getAttribute('id')+'" to applications list');

				var server_id = applicationNodes[i].getAttribute('server');

				if (typeof this.liaison_server_applications.get(server_id) == 'undefined')
					continue;

				var application = new Application(applicationNodes[i].getAttribute('id'), applicationNodes[i].getAttribute('name'), server_id);
				this.applications.set(application.id, application);
				this.applicationsPanel.add(application);
				this.liaison_server_applications.get(server_id).push(application.id);
			} catch(e) {
				this.push_log('error', '[applications] parse_list_apps(transport@list_apps()) - Invalid XML (Missing argument for "application" node '+i+')');
				return;
			}
		}
	},

	list_running_apps: function(applicationsNode_) {
		this.push_log('debug', '[applications] list_running_apps(xml@applicationsNode)');

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
		this.push_log('debug', '[applications] applicationStatus(token: '+token_+', status: '+status_+')');

		var app_status = 2;

		if (typeof this.running_applications.get(token_) == 'undefined') {
			this.push_log('info', '[applications] applicationStatus(token: '+token_+', status: '+status_+') - Creating "running" application "'+token_+'"');

			var app_id = this.liaison_runningapplicationtoken_application.get(token_);
			if (typeof app_id == 'undefined')
				return false;

			var app_object = this.applications.get(app_id);
			if (typeof app_object == 'undefined') {
				this.push_log('error', '[applications] applicationStatus(token: '+token_+', status: '+status_+') - Application "'+app_id+'" does not exist');
				return false;
			}

			var instance = new Running_Application(app_object.id, app_object.name, app_object.server, token_, app_status, this.getContext());
			this.running_applications.set(instance.pid, instance);

			if (status_ == 'started') {
				this.push_log('info', '[applications] applicationStatus(token: '+token_+', status: '+status_+') - Adding "running" application "'+token_+'" to running applications list');
				this.runningApplicationsPanel.add(instance);
			}
		} else {
			this.push_log('info', '[applications] applicationStatus(token: '+token_+', status: '+status_+') - Updating "running" application "'+token_+'" status: "'+app_status+'"');

			var instance = this.running_applications.get(token_);
			instance.update(app_status);

			if (status_ == 'stopped') {
				this.push_log('info', '[applications] applicationStatus(token: '+token_+', status: '+status_+') - Deleting "running" application "'+token_+'" from running applications list');
				this.runningApplicationsPanel.del(instance);
			}
		}

		return true;
	},

	load_explorer: function() {
		if (! this.explorer)
			return;

		$('fileManagerContainer').innerHTML = '<iframe style="width: 100%; height: 100%; border: none;" src="ajaxplorer/"></iframe>';
	},

	display_news: function() {
		new Ajax.Request(
			'news.php',
			{
				method: 'get',
				onSuccess: this.parse_display_news.bind(this)
			}
		);

		setTimeout(this.display_news.bind(this), 300000);
	},

	parse_display_news: function(transport) {
		this.push_log('debug', '[applications] parse_display_news(transport@display_news())');

		var xml = transport.responseXML;

		var buffer = xml.getElementsByTagName('news');

		if (buffer.length != 1) {
			this.push_log('error', '[applications] parse_display_news(transport@display_news()) - Invalid XML (No "news" node)');
			return;
		}

		var html = '';
		html += '<table style="width: 100%; margin-left: auto; margin-right: auto;" border="0" cellspacing="0" cellpadding="3">';
		var new_nodes = xml.getElementsByTagName('new');
		for (var i=0; i<new_nodes.length; i++) {
			this.news.set(''+new_nodes[i].getAttribute('id'), new_nodes[i]);

			var date = new Date();
			date.setTime(new_nodes[i].getAttribute('timestamp')*1000);

			html += '<tr><td style="text-align: left;">';
			html += '<span style="font-size: 1.1em; color: black;">';
			html += '<em>'+date.toLocaleString()+'</em> - <strong><a href="javascript:;" onclick="daemon.show_new('+new_nodes[i].getAttribute('id')+'); return false;">'+new_nodes[i].getAttribute('title')+'</a></strong>';
			html += '</span>';
			html += '</td></tr>';
		}
		html += '</table>';

		$('newsContainer').innerHTML = html;
	},

	show_new: function(i_) {
		var new_ = this.news.get(''+i_);
		var title = new_.getAttribute('title');
		var content = new_.firstChild.nodeValue;

		showNews(title, content);
	}
});

function applicationStatus(token_, status_) {
	daemon.push_log('debug', '[proxy] applicationStatus(token: '+token_+', status: '+status_+')');

	return daemon.applicationStatus(token_, status_);
}
