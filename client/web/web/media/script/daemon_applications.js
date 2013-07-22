/**
 * Copyright (C) 2009-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Jeremy DESVAGES <jeremy@ulteo.com>
 * Author Julien LANGLOIS <julien@ulteo.com> 2011
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
	persistent: false,

	applications: new Hash(),
	applicationsPanel: null,
	running_applications: new Hash(),
	runningApplicationsPanel: null,

	news: new Hash(),

	liaison_runningapplicationtoken_application: new Hash(),

	progress_bar_step: 50,

	initialize: function(applet_version_, applet_main_class_, debug_) {
		Daemon.prototype.initialize.apply(this, [applet_version_, applet_main_class_, debug_]);

		$('applicationsAppletContainer').innerHTML = '';

		var remove_height = 114;
		if (this.debug)
			remove_height = 115;
		$('applicationsContainer').style.height = parseInt(this.my_height)-remove_height+'px';
		$('appsContainer').style.height = parseInt(this.my_height)-remove_height+'px';
		$('runningAppsContainer').style.height = parseInt(this.my_height)-remove_height+'px';
		$('fileManagerContainer').style.height = parseInt(this.my_height)-remove_height+'px';

		this.applicationsPanel = new ApplicationsPanel($('appsContainer'));
		this.runningApplicationsPanel = new ApplicationsPanel($('runningAppsContainer'));
	},

	connect_servers: function() {
		this.push_log('debug', '[applications] connect_servers()');

		try {
			var ulteoapplet_isactive = $('ulteoapplet').isActive();
			if (! ulteoapplet_isactive)
				throw "applet is not ready";
		} catch(e) {
			this.push_log('warning', '[applications] connect_servers() - Applet is not ready');
			setTimeout(this.connect_servers.bind(this), 1000);
			return;
		}

		var servers = this.servers.values();
		for (var i=0; i < servers.length; i++) {
			if (servers[i].token != null)
				this.push_log('info', '[applications] connect_servers() - Connecting to server "'+servers[i].id+'"');
			else
				this.push_log('info', '[applications] connect_servers() - Connecting to server "'+servers[i].fqdn+'"');
			servers[i].connect();
		}

		return true;
	},

	do_started: function() {
		this.push_log('debug', '[applications] do_started()');

		this.load_explorer();
		this.display_news();

		Daemon.prototype.do_started.apply(this);

		setTimeout(this.connect_servers.bind(this), 1000);
	},

	parse_do_started: function(transport) {
		this.push_log('debug', '[applications] parse_do_started(transport@do_started())');

		var applet_params = new Hash();
		applet_params.set('wc_url', getWebClientBaseURL());
		applet_params.set('keymap', this.keymap);
		if (this.rdp_input_method != null)
			applet_params.set('rdp_input_method', this.rdp_input_method);
		if (this.sessionmanager != null)
			applet_params.set('sessionmanager', this.sessionmanager);

		this.settings.each(function(pair) {
			applet_params.set('setting_'+pair.key, pair.value);
		});

		var applet = buildAppletNode('ulteoapplet', this.applet_main_class, this.applet_version, applet_params);
		$('applicationsAppletContainer').show();
		$('applicationsAppletContainer').appendChild(applet);

		return true;
	},

	parse_list_servers: function(transport) {
		this.push_log('debug', '[applications] parse_list_servers(transport@list_servers())');

		var xml = transport.responseXML;

		var sessionNode = xml.getElementsByTagName('session');

		if (sessionNode.length != 1) {
			this.push_log('error', '[applications] parse_list_servers(transport@list_servers()) - Invalid XML (No "session" node)');
			return;
		}

		var serverNodes = xml.getElementsByTagName('server');

		for (var i=0; i<serverNodes.length; i++) {
			try { // IE does not have hasAttribute in DOM API...
				var mode_gateway = false;
				try {
					var token = serverNodes[i].getAttribute('token');
					if (token == null)
						go_to_the_catch_please(); //call a function which does not exist to throw an exception and go to the catch()

					mode_gateway = true;
				} catch(e) {}

				var server = new Server(i, i, serverNodes[i].getAttribute('fqdn'), serverNodes[i].getAttribute('port'), serverNodes[i].getAttribute('login'), serverNodes[i].getAttribute('password'));
				if (mode_gateway)
					server.setToken(serverNodes[i].getAttribute('token'));

				if (mode_gateway)
					this.push_log('info', '[applications] parse_list_servers(transport@list_servers()) - Adding server "'+server.id+'" to servers list');
				else
					this.push_log('info', '[applications] parse_list_servers(transport@list_servers()) - Adding server "'+server.fqdn+'" to servers list');
				this.servers.set(server.id, server);
				this.liaison_server_applications.set(server.id, new Array());

				var applicationNodes = serverNodes[i].getElementsByTagName('application');

				for (var j=0; j<applicationNodes.length; j++) {
					try { // IE does not have hasAttribute in DOM API...
						this.push_log('info', '[applications] parse_list_servers(transport@list_servers()) - Adding application "'+applicationNodes[j].getAttribute('id')+'" to applications list');

						if (typeof this.liaison_server_applications.get(server.id) == 'undefined')
							continue;

						var application = new Application(applicationNodes[j].getAttribute('id'), applicationNodes[j].getAttribute('name'), server.id);
						this.applications.set(application.id, application);
						this.applicationsPanel.add(application);
						this.liaison_server_applications.get(server.id).push(application.id);
					} catch(e) {
						this.push_log('error', '[applications] parse_list_servers(transport@list_servers()) - Invalid XML (Missing argument for "application" node '+j+')');
						return;
					}
				}
			} catch(e) {
				this.push_log('error', '[applications] parse_list_servers(transport@list_servers()) - Invalid XML (Missing argument for "server" node '+i+')');
				return;
			}
		}

		this.ready = true;
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

				var running = 0;
				if ($('running_'+app_id)) {
					if ($('running_'+app_id).innerHTML != '' && typeof parseInt($('running_'+app_id).innerHTML) == 'number')
						running += parseInt($('running_'+app_id).innerHTML);
				}
				running += 1;

				$('running_'+app_id).innerHTML = running;
			}
		} else {
			this.push_log('info', '[applications] applicationStatus(token: '+token_+', status: '+status_+') - Updating "running" application "'+token_+'" status: "'+app_status+'"');

			var instance = this.running_applications.get(token_);
			instance.update(app_status);

			if (status_ == 'stopped') {
				this.push_log('info', '[applications] applicationStatus(token: '+token_+', status: '+status_+') - Deleting "running" application "'+token_+'" from running applications list');
				this.runningApplicationsPanel.del(instance);

				var app_id = this.liaison_runningapplicationtoken_application.get(token_);
				if (typeof app_id == 'undefined')
					return false;

				var running = 0;
				if ($('running_'+app_id)) {
					if ($('running_'+app_id).innerHTML != '' && typeof parseInt($('running_'+app_id).innerHTML) == 'number')
						running = parseInt($('running_'+app_id).innerHTML);
				}
				running -= 1;

				if (running > 0)
					$('running_'+app_id).innerHTML = running;
				else
					$('running_'+app_id).innerHTML = '';
			}
		}

		return true;
	},

	load_explorer: function() {
		if (! this.explorer)
			return;

		this.explorer_loop();

		$('fileManagerContainer').innerHTML = '<iframe style="width: 100%; height: 100%; border: none;" src="ajaxplorer/"></iframe>';
	},

	explorer_loop: function() {
		this.push_log('debug', '[applications] explorer_loop()');

		this.check_start_app();

		if (! this.is_stopped())
			setTimeout(this.explorer_loop.bind(this), 2000);
	},

	check_start_app: function() {
		this.push_log('debug', '[applications] check_start_app()');

		new Ajax.Request(
			'start_app.php',
			{
				method: 'get',
				parameters: {
					check: true,
					differentiator: Math.floor(Math.random()*50000)
				},
				onSuccess: this.parse_check_start_app.bind(this)
			}
		);
	},

	parse_check_start_app: function(transport) {
		this.push_log('debug', '[applications] parse_check_start_app(transport@check_start_app())');

		var xml = transport.responseXML;

		var buffer = xml.getElementsByTagName('start_app');

		if (buffer.length == 0)
			return;

		for (var i=0; i<buffer.length; i++) {
			var application = this.applications.get(parseInt(buffer[i].getAttribute('id')));
			
			var file = buffer[i].getElementsByTagName('file');
			if (file.length == 0)
				application.launch();
			else {
				var type = file[0].getAttribute('type');
				var path = file[0].getAttribute('path');
				var share = file[0].getAttribute('share');
				
				application.launch_with_file(type, path, share);
			}
		}
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
