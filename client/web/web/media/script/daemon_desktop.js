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

var Desktop = Class.create(Daemon, {
	mode: 'desktop',

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

			var applet_params = new Hash();
			applet_params.set('server', server.fqdn);
			applet_params.set('port', '3389');
			applet_params.set('username', server.username);
			applet_params.set('password', server.password);
			applet_params.set('keymap', this.keymap);
			applet_params.set('multimedia', this.multimedia);
			applet_params.set('redirect_client_printers', this.redirect_client_printers);
			if (this.fullscreen)
				applet_params.set('fullscreen', 1);

			var applet = buildAppletNode('ulteoapplet', this.applet_main_class, 'log4j-1.2.jar,'+this.applet_version, applet_params);
			applet.setAttribute('width', applet_width);
			applet.setAttribute('height', applet_height);
			$('desktopAppletContainer').show();
			$('desktopAppletContainer').appendChild(applet);

			this.load_printing_applet();

			return true;
		}
	}
});
