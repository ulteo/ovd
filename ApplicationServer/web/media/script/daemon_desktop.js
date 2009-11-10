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

var Desktop = Class.create(Daemon, {
	initialize: function(applet_version_, applet_main_class_, printing_applet_version_, debug_) {
		Daemon.prototype.initialize.apply(this, [applet_version_, applet_main_class_, printing_applet_version_, debug_]);

		if ($('menuShare')) {
			$('menuShare').style.width = this.my_width+'px';
			this.new_height = parseInt(this.my_height)-18;
			if (this.debug)
				this.new_height = parseInt(this.new_height)-149;
			$('menuShare').style.height = this.new_height+'px';

			this.my_height = parseInt(this.my_height)-18;
		}
	},

	loop: function() {
		this.push_log('[desktop] loop()', 'debug');

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

		var sharingNode = sessionNode.getElementsByTagName('sharing');
		if (sharingNode.length > 0) {
			sharingNode = sharingNode[0];

			try { // IE does not have hasAttribute in DOM API...
				var nb = sharingNode.getAttribute('count');
			} catch(e) {
				this.push_log('[session] bad xml format 3', 'error');
				return;
			}

			if (nb > 0) {
				var shareNodes = sharingNode.getElementsByTagName('share');

				var html = '<div style="margin-left: 0px; margin-right: 0px; text-align: left"><ul>';

				var nb_share_active = 0;
				for (var i = 0; i < shareNodes.length; i++) {
					var buf = shareNodes[i];

					var email = buf.getAttribute('email');
					var mode = buf.getAttribute('mode');
					var alive = buf.getAttribute('alive');
					if (alive == 1)
						nb_share_active += 1;
					var joined = buf.getAttribute('joined');

					html += '<li>';

					html += '<span style="';
					if (alive != 1 && joined != 1)
						html += 'color: orange;';
					if (alive == 1 && joined == 1)
						html += 'color: green;';
					if (alive != 1 && joined == 1)
						html += 'color: blue; text-decoration: line-through;';
					html += '">'+email+'</span>';

					html += ' ('+mode+')</li>';
				}

				html += '</ul></div>';

				$('menuShareContent').innerHTML = html;

				if (this.nb_share != nb_share_active) {
					this.push_log('[session] Watching desktop: '+nb_share_active+' users', 'info');
					this.nb_share = nb_share_active;
				}

				if (nb_share_active != 0) {
					var buf_html = '<img style="margin-left: 5px;" src="../media/image/watch_icon.png" width="16" height="16" alt="" title="" /> <span style="font-size: 0.8em;">Currently watching your desktop: '+nb_share_active+' user';
					if (nb_share_active > 1)
						buf_html += 's';
					buf_html += '</span>';
					$('menuShareWarning').innerHTML = buf_html;
				} else
					$('menuShareWarning').innerHTML = '';
			}
		}
	},

	start: function() {
		this.access_id = 'desktop';

		Daemon.prototype.start.apply(this);
	},

	do_ended: function() {
		Daemon.prototype.do_ended.apply(this);

		if ($('endMessage')) {
			if (this.error_message != '')
				$('endMessage').innerHTML = '<span class="msg_error">'+this.i18n['session_end_unexpected']+'</span>';
			else
				$('endMessage').innerHTML = this.i18n['session_end_ok'];
		}

		if ($('menuContainer')) {
			$('menuContainer').innerHTML = '';
			$('menuContainer').hide();
		}
	}
});
