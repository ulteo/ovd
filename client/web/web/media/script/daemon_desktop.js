/**
 * Copyright (C) 2009-2012 Ulteo SAS
 * http://www.ulteo.com
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2009-2011
 * Author Julien LANGLOIS <julien@ulteo.com> 2011, 2012
 * Author Jocelyn DELALALANDE <j.delalande@ulteo.com> 2012
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

	initialize: function(debug_) {
		Daemon.prototype.initialize.apply(this, [debug_]);

		$('desktopAppletContainer').innerHTML = '';
	},

	parse_do_started: function(transport) {
		Logger.debug('[desktop] parse_do_started(transport@do_started())');

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
			
			// Creates and configures the Java applet
			var applet = this.buildAppletNode('Desktop', applet_params);
			applet.setAttribute('width', applet_width);
			applet.setAttribute('height', applet_height);

			// And it's parent element
			$('desktopAppletContainer').setStyle({width: applet_width+'px', height: applet_height+'px', top: 0+'px', left: 0+'px'});
			$('desktopAppletContainer').show();
			$('desktopAppletContainer').appendChild(applet);

			/* In fullscreen mode, the applet appears in its own window, out of the HTML
			* desktopFullscreenContainer is then a placeholder in the HTML page.
			*/
			if (this.fullscreen) {
				if ($('splashContainer').visible())
					$('splashContainer').hide();
				$('desktopFullscreenContainer').show();
			}

			return true;
		}
	}
});
