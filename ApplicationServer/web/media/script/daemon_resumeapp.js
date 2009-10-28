/**
 * Copyright (C) 2009 Ulteo SAS
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

var ResumeApp = Class.create(Daemon, {
	initialize: function(applet_version_, applet_main_class_, printing_applet_version_, debug_) {
		Daemon.prototype.initialize.apply(this, [applet_version_, applet_main_class_, printing_applet_version_, debug_]);
	},

	loop: function() {
		this.push_log('[resumeapp] loop()', 'debug');

		this.check_status();

		if ((this.session_state == 0 || this.session_state == 10 || this.application_state == 0 || this.application_state == 10) && $('splashContainer').visible() && ! $('appletContainer').visible()) {
			this.start_request();
		} else if (this.session_state == 2 && this.application_state == 2 && $('splashContainer').visible() && ! $('appletContainer').visible()) {
			if (! this.started) {
				this.applet_width = parseInt(this.my_width);
				this.applet_height = parseInt(this.my_height);

				this.do_started();

				this.focus_watch();
			}

			this.started = true;
		} else if ((this.old_session_state == 2 && this.session_state != 2) || this.session_state == 3 || this.session_state == 4 || this.session_state == 9 || (this.old_application_state == 2 && this.application_state != 2) || this.application_state == 3 || this.application_state == 4 || this.application_state == 9) {
			this.do_ended();

			return;
		}

		setTimeout(this.loop.bind(this), 2000);
	},

	client_exit: function() {
	},

	focus_watch: function() {
		var access_id = this.access_id;

		Event.observe(window, 'focus', function() {
			new Ajax.Request(
				'focus.php',
				{
					method: 'get',
					parameters: {
						access_id: access_id,
						focus: 1
					}
				}
			);
		});

		Event.observe(window, 'blur', function() {
			new Ajax.Request(
				'focus.php',
				{
					method: 'get',
					parameters: {
						access_id: access_id,
						focus: 0
					}
				}
			);
		});
	},

	parse_check_status: function(transport) {
		var xml = transport.responseXML;

		var buffer = xml.getElementsByTagName('session');

		if (buffer.length != 1) {
			this.push_log('[session] bad xml format 1', 'error');
			return;
		}

		var sessionNode = buffer[0];

		this.old_session_state = this.session_state;

		try { // IE does not have hasAttribute in DOM API...
			this.session_state = sessionNode.getAttribute('status');
		} catch(e) {
			this.push_log('[session] bad xml format 2', 'error');
			return;
		}

		if (this.session_state != this.old_session_state)
			this.push_log('[session] Change status from '+this.old_session_state+' to '+this.session_state, 'info');

		if (this.session_state != 2)
			this.push_log('[session] Status: '+this.session_state, 'warning');
		else
			this.push_log('[session] Status: '+this.session_state, 'debug');

		var buffer = xml.getElementsByTagName('application');

		if (buffer.length != 1) {
			this.push_log('[application] bad xml format 1', 'error');
			return;
		}

		var applicationNode = buffer[0];

		this.old_application_state = this.application_state;

		try { // IE does not have hasAttribute in DOM API...
			this.application_state = applicationNode.getAttribute('status');
		} catch(e) {
			this.push_log('[application] bad xml format 2', 'error');
			return;
		}

		if (this.application_state != this.old_application_state)
			this.push_log('[application] Change status from '+this.old_application_state+' to '+this.application_state, 'info');
		if (this.application_state != 2)
			this.push_log('[application] Status: '+this.application_state, 'warning');
		else
			this.push_log('[application] Status: '+this.application_state, 'debug');

		var printNode = sessionNode.getElementsByTagName('print');
		if (printNode.length > 0) {
			printNode = printNode[0];

			var path = printNode.getAttribute('path');
			var timestamp = printNode.getAttribute('time');
			this.do_print(path, timestamp);
		}
	},

	start_request: function() {
		new Ajax.Request(
			'resume_app.php',
			{
				method: 'get',
				parameters: {
					access_id: this.access_id
				}
			}
		);
	},

	do_ended: function() {
		Daemon.prototype.do_ended.apply(this);

		window.close();
	}
});
