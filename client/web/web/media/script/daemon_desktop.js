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

var Desktop = Class.create(Daemon, {
	mode: 'desktop',
	persistent: true,

	fullscreen: false,

	initialize: function(applet_version_, applet_main_class_, debug_) {
		Daemon.prototype.initialize.apply(this, [applet_version_, applet_main_class_, debug_]);

		$('desktopAppletContainer').innerHTML = '';
	},

	parse_do_started: function(transport) {
		this.push_log('debug', '[desktop] parse_do_started(transport@do_started())');

		var server = false;

		var servers = this.servers.values();
		for (var i=0; i < servers.length; i++)
			server = servers[i];

		if (! server)
			setTimeout(this.parse_do_started.bind(this, transport), 1000);
		else {
			this.refresh_body_size();

			var applet_width = (this.my_width-(this.my_width % 4));
			var applet_height = (this.my_height*applet_width/this.my_width);
			if (this.fullscreen) {
				applet_width = 1;
				applet_height = 1;
			}

			var applet_params = new Hash();
			applet_params.set('wc_url', getWebClientBaseURL());
			applet_params.set('server', server.fqdn);
			applet_params.set('port', server.port);
			if (server.token != null)
				applet_params.set('token', server.token);
			applet_params.set('username', server.username);
			applet_params.set('password', server.password);
			applet_params.set('keymap', this.keymap);
			if (this.rdp_input_method != null)
				applet_params.set('rdp_input_method', this.rdp_input_method);
			if (this.fullscreen)
				applet_params.set('fullscreen', 1);
			if (this.sessionmanager != null)
				applet_params.set('sessionmanager', this.sessionmanager);

			this.settings.each(function(pair) {
				applet_params.set('setting_'+pair.key, pair.value);
			});
			
			var applet = buildAppletNode('ulteoapplet', this.applet_main_class, this.applet_version, applet_params);
			applet.setAttribute('width', applet_width);
			applet.setAttribute('height', applet_height);
			$('desktopAppletContainer').setStyle({width: applet_width+'px', height: applet_height+'px', top: 0+'px', left: 0+'px'});
			$('desktopAppletContainer').show();
			$('desktopAppletContainer').appendChild(applet);
			if (this.fullscreen) {
				if ($('splashContainer').visible())
					$('splashContainer').hide();
				$('desktopFullscreenContainer').show();
			}

			return true;
		}
	},

	parse_list_servers: function(transport) {
		this.push_log('debug', '[desktop] parse_list_servers(transport@list_servers())');

		var xml = transport.responseXML;

		var sessionNode = xml.getElementsByTagName('session');

		if (sessionNode.length != 1) {
			this.push_log('error', '[desktop] parse_list_servers(transport@list_servers()) - Invalid XML (No "session" node)');
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
					this.push_log('info', '[desktop] parse_list_servers(transport@list_servers()) - Adding server "'+server.id+'" to servers list');
				else
					this.push_log('info', '[desktop] parse_list_servers(transport@list_servers()) - Adding server "'+server.fqdn+'" to servers list');
				this.servers.set(server.id, server);
				this.liaison_server_applications.set(server.id, new Array());
			} catch(e) {
				this.push_log('error', '[desktop] parse_list_servers(transport@list_servers()) - Invalid XML (Missing argument for "server" node '+i+')');
				return;
			}
		}

		this.ready = true;
	}
});
