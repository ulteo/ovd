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
	loop: function() {
		this.check_status();

		if (this.session_state == 0 || this.session_state == 10) {
			this.start_request();
		} else if (this.session_state == 2 && this.application_state == 2 && $('splashContainer').visible() && ! $('appletContainer').visible()) {
			if (! this.started)
				this.start();

			this.started = true;
		} else if ((this.old_session_state == 2 && this.session_state != 2) || this.session_state == 3 || this.session_state == 4 || this.session_state == 9 || (this.old_application_state == 2 && this.application_state != 2) || this.application_state == 3 || this.application_state == 4 || this.application_state == 9) {
			if (! this.started)
				this.error_message = this.i18n['session_close_unexpected'];

			this.do_ended();

			return;
		}

		setTimeout(this.loop.bind(this), 2000);
	},

	parse_check_status: function(transport) {
		var xml = transport.responseXML;

		var buffer = xml.getElementsByTagName('session');

		if (buffer.length != 1)
			return;

		var sessionNode = buffer[0];

		this.old_session_state = this.session_state;

		try { // IE does not have hasAttribute in DOM API...
			this.session_state = sessionNode.getAttribute('status');
		} catch(e) {
			return;
		}

		var buffer = xml.getElementsByTagName('application');

		if (buffer.length != 1)
			return;

		var applicationNode = buffer[0];

		this.old_application_state = this.application_state;

		try { // IE does not have hasAttribute in DOM API...
			this.application_state = applicationNode.getAttribute('status');
		} catch(e) {
			return;
		}

		var printNode = sessionNode.getElementsByTagName('print');
		if (printNode.length > 0) {
			printNode = printNode[0];

			var path = printNode.getAttribute('path');
			var timestamp = printNode.getAttribute('time');
			this.do_print(path, timestamp);
		}
	},

	start: function() {
		this.access_id = 'desktop';

		Daemon.prototype.start.apply(this);
	}
});
