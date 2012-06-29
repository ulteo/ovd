/**
 * Copyright (C) 2009-2012 Ulteo SAS
 * http://www.ulteo.com
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2009-2011
 * Author Julien LANGLOIS <julien@ulteo.com> 2011, 2012
 * Author Omar AKHAM <oakham@ulteo.com> 2011
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
	local_integration: false,

	applications: new Hash(),
	applicationsPanel: null,
	running_applications: new Hash(),
	nb_running_applications: 0,
	runningApplicationsPanel: null,

	news: new Hash(),

	liaison_runningapplicationtoken_application: new Hash(),

	progress_bar_step: 50,
	
	waiting_applications_instances: new Array(),

	initialize: function(debug_) {
		Daemon.prototype.initialize.apply(this, [debug_]);

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
		
		try {
			this.local_integration = local_integration;
		} catch(e) {}
	},

	parseSessionSettings: function(setting_nodes) {
		Daemon.prototype.parseSessionSettings.apply(this, [setting_nodes]);

		Logger.error('Applications - persistent: '+this.persistent);
		if (this.persistent) {
			$('suspend_button').show();
		}
		else {
			$('suspend_button').hide();
		}
	},

	connect_servers: function() {
		Logger.debug('[applications] connect_servers()');

		try {
			var ulteoapplet_isactive = $('ulteoapplet').isActive();
			if (! ulteoapplet_isactive)
				throw "applet is not ready";
		} catch(e) {
			Logger.warn('[applications] connect_servers() - Applet is not ready');
			setTimeout(this.connect_servers.bind(this), 1000);
			return;
		}

		var servers = this.servers.values();
		for (var i=0; i < servers.length; i++) {
			if (servers[i].token != null)
				Logger.info('[applications] connect_servers() - Connecting to server "'+servers[i].id+'"');
			else
				Logger.info('[applications] connect_servers() - Connecting to server "'+servers[i].fqdn+'"');
			servers[i].connect();
		}

		return true;
	},

	do_started: function() {
		Logger.debug('[applications] do_started()');

		this.load_explorer();
		this.display_news();

		Daemon.prototype.do_started.apply(this);

		setTimeout(this.connect_servers.bind(this), 1000);
	},

	parse_do_started: function(transport) {
		Logger.debug('[applications] parse_do_started(transport@do_started())');

		var applet_params = new Hash();
		applet_params.set('wc_url', getWebClientBaseURL());
		applet_params.set('keymap', this.keymap);
		if (this.rdp_input_method != null)
			applet_params.set('rdp_input_method', this.rdp_input_method);
		if (this.local_integration == true)
			applet_params.set('local_integration', 'true');
		if (this.sessionmanager != null)
			applet_params.set('sessionmanager', this.sessionmanager);

		this.settings.each(function(pair) {
			applet_params.set('setting_'+pair.key, pair.value);
		});

		var applet = this.buildAppletNode('Applications', applet_params);
		$('applicationsAppletContainer').show();
		$('applicationsAppletContainer').appendChild(applet);

		return true;
	},

	parse_server_node: function(server_, serverNode_) {
		var applicationNodes = serverNode_.getElementsByTagName('application');
		
		for (var j=0; j<applicationNodes.length; j++) {
			try { // IE does not have hasAttribute in DOM API...
				Logger.info('[applications] parse_list_servers(transport@list_servers()) - Adding application "'+applicationNodes[j].getAttribute('id')+'" to applications list');
				
				if (typeof this.liaison_server_applications.get(server_.id) == 'undefined')
					continue;
				
				var application = new Application(applicationNodes[j].getAttribute('id'), applicationNodes[j].getAttribute('name'), server_.id);
				this.applications.set(application.id, application);
				this.applicationsPanel.add(application);
				this.liaison_server_applications.get(server_.id).push(application.id);
			} catch(e) {
				Logger.error('[applications] parse_list_servers(transport@list_servers()) - Invalid XML (Missing argument for "application" node '+j+')');
				return false;
			}
		}
	},

	list_running_apps: function(applicationsNode_) {
		Logger.debug('[applications] list_running_apps(xml@applicationsNode)');

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

	nb_running_apps: function() {
		return this.nb_running_applications;
	},

	applicationStatus: function(app_id_, token_, status_) {
		Logger.debug('[applications] applicationStatus(token: '+token_+', status: '+status_+')');

		var app_status = 2;

		if (typeof this.running_applications.get(token_) == 'undefined') {
			Logger.info('[applications] applicationStatus(token: '+token_+', status: '+status_+') - Creating "running" application "'+token_+'"');
			
			var app_id = this.liaison_runningapplicationtoken_application.get(token_);
			if (typeof app_id == 'undefined')
				app_id = app_id_;

			var app_object = this.applications.get(app_id);
			if (typeof app_object == 'undefined') {
				Logger.error('[applications] applicationStatus(token: '+token_+', status: '+status_+') - Application "'+app_id+'" does not exist');
				return false;
			}

			var instance = new Running_Application(app_object.id, app_object.name, app_object.server, token_, app_status, this.getContext());
			this.running_applications.set(instance.pid, instance);

			if (status_ == 'started') {
				Logger.info('[applications] applicationStatus(token: '+token_+', status: '+status_+') - Adding "running" application "'+token_+'" to running applications list');
				this.runningApplicationsPanel.add(instance);

				var running = 0;
				if ($('running_'+app_id)) {
					if ($('running_'+app_id).innerHTML != '' && typeof parseInt($('running_'+app_id).innerHTML) == 'number')
						running += parseInt($('running_'+app_id).innerHTML);
				}
				running += 1;
				this.nb_running_applications += 1;

				$('running_'+app_id).innerHTML = running;
			}
		} else {
			Logger.info('[applications] applicationStatus(token: '+token_+', status: '+status_+') - Updating "running" application "'+token_+'" status: "'+app_status+'"');

			var instance = this.running_applications.get(token_);
			instance.update(app_status);

			if (status_ == 'stopped') {
				Logger.info('[applications] applicationStatus(token: '+token_+', status: '+status_+') - Deleting "running" application "'+token_+'" from running applications list');
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
				this.nb_running_applications -= 1;

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
		Logger.debug('[applications] explorer_loop()');

		this.check_start_app();

		if (! this.is_stopped())
			setTimeout(this.explorer_loop.bind(this), 2000);
	},

	check_start_app: function() {
		Logger.debug('[applications] check_start_app()');

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
		Logger.debug('[applications] parse_check_start_app(transport@check_start_app())');

		var xml = transport.responseXML;

		var buffer = xml.getElementsByTagName('start_app');

		if (buffer.length == 0)
			return;

		for (var i=0; i<buffer.length; i++) {
			this.waiting_applications_instances.push(buffer[i]);
		}
		
		this.start_waiting_instances();
	},

	start_waiting_instances: function() {
		Logger.debug('[applications] start_waiting_instances()');
		var instances2start = new Array();
		
		for (var i=0; i<this.waiting_applications_instances.length; i++) {
			var node = this.waiting_applications_instances[i];
		  
			var application = this.applications.get(parseInt(node.getAttribute('id')));
			
			var server = daemon.servers.get(application.server_id);
			if (server.ready == false)
				continue;
			
			instances2start.push(node);
		}
		
		for (var i=0; i<instances2start.length; i++) {
			this.waiting_applications_instances = this.waiting_applications_instances.without(instances2start[i]);
			
			Logger.info('start application '+instances2start[i].getAttribute('id'));
			
			var application = this.applications.get(parseInt(instances2start[i].getAttribute('id')));
			
			var file = instances2start[i].getElementsByTagName('file');
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
		Logger.debug('[applications] parse_display_news(transport@display_news())');

		var xml = transport.responseXML;

		var buffer = xml.getElementsByTagName('news');

		if (buffer.length != 1) {
			Logger.error('[applications] parse_display_news(transport@display_news()) - Invalid XML (No "news" node)');
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

function applicationStatus(app_id_, token_, status_) {
	Logger.debug('[proxy] applicationStatus(token: '+token_+', status: '+status_+')');

	return daemon.applicationStatus(app_id_, token_, status_);
}
