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

var Daemon = Class.create({
	i18n: new Array(),
	context: null,

	applet_version: '',
	applet_main_class: '',
	printing_applet_version: '',

	protocol: '',
	server: '',
	port: '',

	mode: '',

	servers: new Hash(),
	liaison_server_applications: new Hash(),

	shareable: false,
	persistent: false,
	in_popup: true,
	shared: false,

	session_state: -1,
	old_session_state: -1,
	started: false,
	stopped: false,

	application_state: -1,
	old_application_state: -1,
	app_id: '',
	doc: '',

	error_message: '',

	applet_width: -1,
	applet_height: -1,

	nb_share: 0,

	initialize: function(applet_version_, applet_main_class_, printing_applet_version_) {
		this.applet_version = applet_version_;
		this.applet_main_class = applet_main_class_;
		this.printing_applet_version = printing_applet_version_;

		this.protocol = window.location.protocol;
		this.server = window.location.host;
		this.port = window.location.port;
		if (this.port == '')
			this.port = 80;

		this.session_state = -1;
		this.old_session_state = -1;
		this.started = false;

		if (typeof(window.innerWidth) == 'number' || typeof(window.innerHeight) == 'number') {
			this.my_width  = window.innerWidth;
			this.my_height = window.innerHeight;
		} else if (document.documentElement && (document.documentElement.clientWidth || document.documentElement.clientHeight)) {
			this.my_width  = document.documentElement.clientWidth;
			this.my_height = document.documentElement.clientHeight;
		} else if (document.body && (document.body.clientWidth || document.body.clientHeight)) {
			this.my_width  = document.body.clientWidth;
			this.my_height = document.body.clientHeight;
		}

		this.list_servers();

		setTimeout(this.preload.bind(this), 2000);

		Event.observe(window, 'unload', this.client_exit.bind(this));
	},

	preload: function() {
return;
		if ($('printerContainer')) {
			$('printerContainer').show();
			$('printerContainer').innerHTML = '<applet code="com.ulteo.OnlineDesktopPrinting" archive="'+this.printing_applet_version+'" codebase="../applet/" width="1" height="1" name="ulteoprinting"> \
				<param name="do_nothing" value="1"> \
			</applet>';
		}
	},

	initContext: function() {
		var context = new Context(this.i18n, this.shareable, this.persistent);

		return context;
	},

	getContext: function() {
		if (this.context == null)
			this.context = this.initContext();

		return this.context;
	},

	loop: function() {
		this.check_status();

		if (this.session_state == 0 || this.session_state == 10) {
			this.start_request();
		} else if (this.session_state == 2 && $('splashContainer').visible()) {
			if (! this.started)
				this.start();

			this.started = true;
		} else if ((this.old_session_state == 2 && this.session_state != 2) || this.session_state == 3 || this.session_state == 4 || this.session_state == 9) {
			if (! this.started)
				this.error_message = this.i18n['session_close_unexpected'];

			this.do_ended();

			return;
		}

		setTimeout(this.loop.bind(this), 2000);
	},

	suspend: function() {
		new Ajax.Request(
			'suspend.php',
			{
				asynchronous: false,
				method: 'get'
			}
		);

		this.do_ended();
	},

	logout: function() {
		new Ajax.Request(
			'logout.php',
			{
				asynchronous: false,
				method: 'get'
			}
		);

		this.do_ended();
	},

	client_exit: function() {
		if (this.persistent == true)
			this.suspend();
		else
			this.logout();
	},

	check_status: function() {
this.old_session_state = 2;
this.session_state = 2;
this.old_application_state = 2;
this.application_state = 2;
return;
		new Ajax.Request(
			'whatsup.php',
			{
				method: 'get',
				asynchronous: false,
				parameters: {
					application_id: this.access_id,
					differentiator: Math.floor(Math.random()*50000)
				},
				onSuccess: this.parse_check_status.bind(this)
			}
		);
	},

	parse_check_status: function(transport) {
return;
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

	start_request: function() {
return;
		new Ajax.Request(
			'start.php',
			{
				method: 'get',
				parameters: {
					width: parseInt(this.my_width),
					height: parseInt(this.my_height)
				}
			}
		);
	},

	start: function() {
		if (! $(this.mode+'ModeContainer').visible())
			$(this.mode+'ModeContainer').show();

		if (! $(this.mode+'AppletContainer').visible())
			$(this.mode+'AppletContainer').show();

		this.do_started();
	},

	do_started: function() {
		this.parse_do_started();
	},

	parse_do_started: function(transport) {
	},

	list_servers: function() {
		new Ajax.Request(
			'servers.php',
			{
				method: 'get',
				onSuccess: this.parse_list_servers.bind(this)
			}
		);
	},

	parse_list_servers: function(transport) {
		var xml = transport.responseXML;

		var buffer = xml.getElementsByTagName('servers');

		if (buffer.length != 1)
			return;

		var serverNodes = xml.getElementsByTagName('server');

		for (var i=0; i<serverNodes.length; i++) {
			try { // IE does not have hasAttribute in DOM API...
				var server = new Server(serverNodes[i].getAttribute('fqdn'), i, serverNodes[i].getAttribute('fqdn'), 3389, serverNodes[i].getAttribute('login'), serverNodes[i].getAttribute('password'));
				this.servers.set(server.id, server);
				this.liaison_server_applications.set(server.id, new Array());
			} catch(e) {
				return;
			}
		}
	},

	do_ended: function() {
		if (this.stopped == true)
			return;

		this.stopped = true;

		if ($('splashContainer').visible())
			$('splashContainer').hide();

		if ($(this.mode+'AppletContainer').visible())
			$(this.mode+'AppletContainer').hide();

		if ($(this.mode+'ModeContainer').visible())
			$(this.mode+'ModeContainer').hide();

		if ($('endContainer')) {
			$('endContent').innerHTML = '';

			var buf = document.createElement('span');
			buf.setAttribute('style', 'font-size: 1.1em; font-weight: bold; color: #686868;');

			var end_message = document.createElement('span');
			end_message.setAttribute('id', 'endMessage');
			buf.appendChild(end_message);

			if (this.error_message != '' && this.error_message != 'undefined') {
				var error_container = document.createElement('div');
				error_container.setAttribute('id', 'errorContainer');
				error_container.setAttribute('style', 'width: 100%; margin-top: 10px; margin-left: auto; margin-right: auto; display: none; visibility: hidden;');
				buf.appendChild(error_container);

				var error_toggle_div = document.createElement('div');

				var error_toggle_table = document.createElement('table');
				error_toggle_table.setAttribute('style', 'margin-top: 10px; margin-left: auto; margin-right: auto;');

				var error_toggle_tr = document.createElement('tr');

				var error_toggle_img_td = document.createElement('td');
				var error_toggle_img_link = document.createElement('a');
				error_toggle_img_link.setAttribute('href', 'javascript:;');
				error_toggle_img_link.setAttribute('onclick', 'toggleContent(\'errorContainer\'); return false;');
				var error_toggle_img = document.createElement('span');
				error_toggle_img.setAttribute('id', 'errorContainer_ajax');
				error_toggle_img.setAttribute('style', 'width: 16px; height: 16px;');
				error_toggle_img.innerHTML = '<img src="../media/image/show.png" width="16" height="16" alt="+" title="" />';
				error_toggle_img_link.appendChild(error_toggle_img);
				error_toggle_img_td.appendChild(error_toggle_img_link);
				error_toggle_tr.appendChild(error_toggle_img_td);

				var error_toggle_text_td = document.createElement('td');
				var error_toggle_text_link = document.createElement('a');
				error_toggle_text_link.setAttribute('href', 'javascript:;');
				error_toggle_text_link.setAttribute('onclick', 'toggleContent(\'errorContainer\'); return false;');
				var error_toggle_text = document.createElement('span');
				error_toggle_text.setAttribute('style', 'height: 16px;');
				error_toggle_text.innerHTML = this.i18n['error_details'];
				error_toggle_text_link.appendChild(error_toggle_text);
				error_toggle_text_td.appendChild(error_toggle_text_link);
				error_toggle_tr.appendChild(error_toggle_text_td);

				error_toggle_table.appendChild(error_toggle_tr);
				error_toggle_div.appendChild(error_toggle_table);

				var error_content = document.createElement('div');
				error_content.setAttribute('id', 'errorContainer_content');
				error_content.setAttribute('style', 'display: none;');
				error_content.innerHTML = this.error_message;
				error_toggle_div.appendChild(error_content);

				buf.appendChild(error_toggle_div);
			}

			var close_container = document.createElement('div');
			close_container.setAttribute('style', 'margin-top: 10px;');
			if (this.in_popup == true) {
				var close_button = document.createElement('input');
				close_button.setAttribute('type', 'button');
				close_button.setAttribute('value', this.i18n['close_this_window']);
				close_button.setAttribute('onclick', 'window.close(); return false;');
				close_container.appendChild(close_button);
			} else if (this.shared == false) {
				var close_text = document.createElement('span');
				close_text.innerHTML = this.i18n['start_another_session'];
				close_container.appendChild(close_text);
			}
			buf.appendChild(close_container);

			$('endContent').appendChild(buf);

			$('endContent').innerHTML = $('endContent').innerHTML;

			if (this.error_message != '' && this.error_message != 'undefined')
				offContent('errorContainer');

			$('endContainer').show();
		}

		if ($('endMessage')) {
			if (this.error_message != '')
				$('endMessage').innerHTML = '<span class="msg_error">'+this.i18n['session_end_unexpected']+'</span>';
			else
				$('endMessage').innerHTML = this.i18n['session_end_ok'];
		}
	},

	do_print: function(path_, timestamp_) {
return;
		var print_url = this.protocol+'//'+this.server+':'+this.port+'/applicationserver/print.php?timestamp='+timestamp_;

		$('printerContainer').show();
		$('printerContainer').innerHTML = '<applet code="com.ulteo.OnlineDesktopPrinting" archive="'+this.printing_applet_version+'" codebase="../applet/" width="1" height="1" name="ulteoprinting"> \
			<param name="url" value="'+print_url+'"> \
				<param name="filename" value="'+path_+'"> \
			</applet>';
	}
});
