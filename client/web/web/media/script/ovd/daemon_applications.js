/**
 * Copyright (C) 2009-2013 Ulteo SAS
 * http://www.ulteo.com
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2009-2011
 * Author Julien LANGLOIS <julien@ulteo.com> 2011, 2012
 * Author Omar AKHAM <oakham@ulteo.com> 2011
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

var Applications = Class.create(Daemon, {
	mode: 'applications',
	local_integration: false,

	applications: null, // Hash
	running_applications: null, // Hash
	nb_running_applications: 0,

	liaison_runningapplicationtoken_application: null, // Hash

	progress_bar_step: 50,
	
	waiting_applications_instances: null,

	initialize: function(debug_) {
		Daemon.prototype.initialize.apply(this, [debug_]);
		
		this.applications = new Hash();
		this.running_applications = new Hash();
		this.liaison_runningapplicationtoken_application = new Hash();

		this.waiting_applications_instances = new Array();
	},

	connect_servers: function() {
		Logger.debug('[applications] connect_servers()');

		try {
			var ulteoapplet_isactive = jQuery('#ulteoapplet')[0].isActive();
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
		var servers = this.webapp_servers.values();
		for (var i=0; i < servers.length; i++) {
			Logger.info('[applications] connect_servers() - Connecting to webapp server "'+servers[i].base_url+'"');
			servers[i].connect();
		}

		return true;
	},

	do_started: function() {
		Logger.debug('[applications] do_started()');

		this.process_loop();

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
			applet_params.set('sessionmanager', this.sessionmanager.host+":"+this.sessionmanager.port);

		this.settings.each(function(pair) {
			applet_params.set('setting_'+pair.key, pair.value);
		});

		var applet = this.buildAppletNode('Applications', applet_params);
		jQuery('#applicationsAppletContainer').show();
		jQuery('#applicationsAppletContainer').append(applet);

		return true;
	},

	parse_server_node: function(server_, serverNode_) {
		var applicationNodes = serverNode_.getElementsByTagName('application');
		
		for (var j=0; j<applicationNodes.length; j++) {
			try { // IE does not have hasAttribute in DOM API...
				Logger.info('[applications] parse_list_servers(transport@list_servers()) - Adding application "'+applicationNodes[j].getAttribute('id')+'" to applications list');
				
				var app_type = applicationNodes[j].getAttribute('type');
				if (app_type !== 'webapp' && typeof this.liaison_server_applications.get(server_.id) == 'undefined')
					continue;
				
				var application = new Application(applicationNodes[j].getAttribute('id'), applicationNodes[j].getAttribute('name'), server_.id, applicationNodes[j].getAttribute('type'));
				this.applications.set(application.id, application);
				
				if (app_type !== 'webapp')
					this.liaison_server_applications.get(server_.id).push(application.id);
				
				this.on_application_add(application);
			
			} catch(e) {
				Logger.error('[applications] parse_list_servers(transport@list_servers()) - Invalid XML (Missing argument for "application" node '+j+')');
				Logger.debug('[applications] parse_list_servers(transport@list_servers()) - Exception: '+e);
				return false;
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

			var instance = new Running_Application(app_object.id, app_object.name, app_object.server, token_, app_status);
			this.running_applications.set(instance.pid, instance);

			if (status_ == 'started') {
				Logger.info('[applications] applicationStatus(token: '+token_+', status: '+status_+') - Adding "running" application "'+token_+'" to running applications list');

				this.on_running_app_started(instance);
			}
		} else {
			Logger.info('[applications] applicationStatus(token: '+token_+', status: '+status_+') - Updating "running" application "'+token_+'" status: "'+app_status+'"');

			var instance = this.running_applications.get(token_);
			instance.update(app_status);

			if (status_ == 'stopped') {
				Logger.info('[applications] applicationStatus(token: '+token_+', status: '+status_+') - Deleting "running" application "'+token_+'" from running applications list');

				var app_id = this.liaison_runningapplicationtoken_application.get(token_);
				if (typeof app_id == 'undefined')
					return false;

				this.on_running_app_stopped(instance);
			}
		}

		return true;
	},

	on_application_add: function(application_) {},
	on_running_app_started: function(instance_) {},
	on_running_app_stopped: function(instance_) {},
	
	on_server_status_change: function(server_, status_) {
		Daemon.prototype.on_server_status_change.apply(this, [server_, status_]);
		
		if (server_.connected && status_ == 'ready') {
			this.start_waiting_instances();
		}
	},
	
	launch_application: function(application_) {
		var server;
		if (application_.type === 'webapp') {
			server = this.webapp_servers.get(application_.server_id);
			var url = server.server_url + '/open?id=' + application_.id + '&user=' + server.username + '&pass=' + server.password;
			var app_window = window.open(url, '_blank');
		} else {
			server = this.servers.get(application_.server_id);
			jQuery('#ulteoapplet')[0].startApplication(++this.application_token, application_.id, server.java_id);
			this.liaison_runningapplicationtoken_application.set(this.application_token, application_.id);
		}
	},

	launch_application_with_file: function(application_, type_, path_, share_) {
		var server = this.servers.get(application_.server_id);
		jQuery('#ulteoapplet')[0].startApplicationWithFile(++this.application_token, application_.id, server.java_id, type_, path_, share_);
		this.liaison_runningapplicationtoken_application.set(this.application_token, application_.id);
	},

	process_loop: function() {
		Logger.debug('[applications] process_loop()');

		this.check_start_app();

		if (! this.is_stopped())
			setTimeout(this.process_loop.bind(this), 2000);
	},

	check_start_app: function() {
		Logger.debug('[applications] check_start_app()');

		jQuery.ajax({
				url: 'start_app.php?differentiator='+Math.floor(Math.random()*50000)+'&check=true',
				type: 'GET',
				dataType: 'xml',
				success: this.parse_check_start_app.bind(this)
			}
		);
	},

	parse_check_start_app: function(xml) {
		Logger.debug('[applications] parse_check_start_app(transport@check_start_app())');

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
			
			var server = this.servers.get(application.server_id);
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
				this.launch_application(application);
			else {
				var type = file[0].getAttribute('type');
				var path = file[0].getAttribute('path');
				var share = file[0].getAttribute('share');
				
				this.launch_application_with_file(application, type, path, share);
			}
		}
	}
});
