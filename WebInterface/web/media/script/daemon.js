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

	in_popup: true,
	debug: false,

	protocol: '',
	server: '',
	port: '',

	my_width: 0,
	my_height: 0,

	mode: '',
	keymap: 'en-us',

	multimedia: true,
	redirect_client_printers: true,

	servers: new Hash(),
	liaison_server_applications: new Hash(),

	persistent: false,

	session_status: '',
	session_status_old: '',

	started: false,
	stopped: false,

	error_message: '',

	initialize: function(applet_version_, applet_main_class_, in_popup_, debug_) {
		this.applet_version = applet_version_;
		this.applet_main_class = applet_main_class_;

		this.in_popup = in_popup_;
		this.debug = debug_;

		this.protocol = window.location.protocol;
		this.server = window.location.host;
		this.port = window.location.port;
		if (this.port == '')
			this.port = 80;

		if (document.documentElement && (document.documentElement.clientWidth || document.documentElement.clientHeight)) {
			this.my_width  = document.documentElement.clientWidth;
			this.my_height = document.documentElement.clientHeight;
		} else if (document.body && (document.body.clientWidth || document.body.clientHeight)) {
			this.my_width  = document.body.clientWidth;
			this.my_height = document.body.clientHeight;
		}

		if (this.debug) {
			$('debugContainer').show();
			$('debugContainer').style.display = 'inline';
			$('debugLevels').show();
			$('debugLevels').style.display = 'inline';

			this.my_height = parseInt(this.my_height)-149;
		}

		this.list_servers();

		Event.observe(window, 'unload', this.client_exit.bind(this));
	},

	initContext: function() {
		var context = new Context(this.i18n, this.persistent);

		return context;
	},

	getContext: function() {
		if (this.context == null)
			this.context = this.initContext();

		return this.context;
	},

	push_log: function(level_, data_) {
		if (! this.debug)
			return;

		var flag = (($('debugContainer').scrollTop+$('debugContainer').offsetHeight) == $('debugContainer').scrollHeight);

		buf = new Date();
		hour = buf.getHours();
		if (hour < 10)
			hour = '0'+hour;
		minutes = buf.getMinutes();
		if (minutes < 10)
			minutes = '0'+minutes;
		seconds = buf.getSeconds();
		if (seconds < 10)
			seconds = '0'+seconds;

		$('debugContainer').innerHTML += '<div class="'+level_+'">['+hour+':'+minutes+':'+seconds+'] - '+data_+'</div>'+"\n";

		if (flag)
			$('debugContainer').scrollTop = $('debugContainer').scrollHeight;
	},

	switch_debug: function(level_) {
		var flag = (($('debugContainer').scrollTop+$('debugContainer').offsetHeight) == $('debugContainer').scrollHeight);

		var buf = $('debugContainer').className;

		if (buf.match('no_'+level_))
			buf = buf.replace('no_'+level_, level_);
		else
			buf = buf.replace(level_, 'no_'+level_);

		$('debugContainer').className = buf;

		if (flag)
			$('debugContainer').scrollTop = $('debugContainer').scrollHeight;
	},

	clear_debug: function() {
		$('debugContainer').innerHTML = '';
	},

	loop: function() {
		this.push_log('debug', '[daemon] loop()');

		this.check_status();

		if (! this.started) {
			this.push_log('info', '[daemon] loop() - Now starting session');

			this.start();

			this.started = true;
		} else if (this.stopped || this.session_status == 'unknown') {
			this.push_log('info', '[daemon] loop() - Now ending session');

			if (! this.started) {
				this.push_log('warning', '[daemon] loop() - Session end is unexpected (session was never started)');
				this.error_message = this.i18n['session_close_unexpected'];
			}

			this.do_ended();

			this.stopped = true;
		}

		if (! this.stopped)
			setTimeout(this.loop.bind(this), 2000);
	},

	suspend: function() {
		this.push_log('debug', '[daemon] suspend()');

		new Ajax.Request(
			'suspend.php',
			{
				method: 'get'
			}
		);

		this.do_ended();
	},

	logout: function() {
		this.push_log('debug', '[daemon] logout()');

		new Ajax.Request(
			'logout.php',
			{
				method: 'get'
			}
		);

		this.do_ended();
	},

	client_exit: function() {
		this.push_log('debug', '[daemon] client_exit()');

		if (this.persistent == true) {
			this.push_log('info', '[daemon] client_exit() - We are in a "persistent" mode, now suspending session');
			this.suspend();
		} else {
			this.push_log('info', '[daemon] client_exit() - We are in a "non-persistent" mode, now ending session');
			this.logout();
		}
	},

	get_session_status: function() {
		this.push_log('debug', '[daemon] get_session_status()');

		this.check_status();

		return this.session_status;
	},

	check_status: function() {
		this.push_log('debug', '[daemon] check_status()');

		new Ajax.Request(
			'session_status.php',
			{
				method: 'get',
				parameters: {
					differentiator: Math.floor(Math.random()*50000)
				},
				onSuccess: this.parse_check_status.bind(this)
			}
		);
	},

	parse_check_status: function(transport) {
		this.push_log('debug', '[daemon] parse_check_status(transport@check_status())');

		var xml = transport.responseXML;

		var buffer = xml.getElementsByTagName('session');

		if (buffer.length != 1) {
			this.push_log('error', '[daemon] parse_check_status(transport@check_status()) - Invalid XML (No "session" node)');
			return;
		}

		var sessionNode = buffer[0];

		try { // IE does not have hasAttribute in DOM API...
			this.session_status_old = this.session_status;
			this.session_status = sessionNode.getAttribute('status');

			if (this.session_status_old != this.session_status)
				this.push_log('info', '[daemon] parse_check_status(transport@check_status()) - Session status is now "'+this.session_status+'"');
			else
				this.push_log('debug', '[daemon] parse_check_status(transport@check_status()) - Session status is "'+this.session_status+'"');
		} catch(e) {
			this.push_log('error', '[daemon] parse_check_status(transport@check_status()) - Invalid XML (Missing argument for "session" node)');
			return;
		}
	},

	start: function() {
		this.push_log('debug', '[daemon] start()');

		if (! $(this.mode+'ModeContainer').visible())
			$(this.mode+'ModeContainer').show();

		if (! $(this.mode+'AppletContainer').visible())
			$(this.mode+'AppletContainer').show();

		this.do_started();
	},

	do_started: function() {
		this.push_log('debug', '[daemon] do_started()');

		this.parse_do_started();
	},

	parse_do_started: function(transport) {
		this.push_log('debug', '[daemon] parse_do_started(transport@do_started())');

		this.started = true;
	},

	list_servers: function() {
		this.push_log('debug', '[daemon] list_servers()');

		new Ajax.Request(
			'servers.php',
			{
				method: 'get',
				onSuccess: this.parse_list_servers.bind(this)
			}
		);
	},

	parse_list_servers: function(transport) {
		this.push_log('debug', '[daemon] parse_list_servers(transport@list_servers())');

		var xml = transport.responseXML;

		var buffer = xml.getElementsByTagName('servers');

		if (buffer.length != 1) {
			this.push_log('error', '[daemon] parse_list_servers(transport@list_servers()) - Invalid XML (No "servers" node)');
			return;
		}

		var serverNodes = xml.getElementsByTagName('server');

		for (var i=0; i<serverNodes.length; i++) {
			try { // IE does not have hasAttribute in DOM API...
				this.push_log('info', '[daemon] parse_list_servers(transport@list_servers()) - Adding server "'+serverNodes[i].getAttribute('fqdn')+'" to servers list');

				var server = new Server(serverNodes[i].getAttribute('fqdn'), i, serverNodes[i].getAttribute('fqdn'), 3389, serverNodes[i].getAttribute('login'), serverNodes[i].getAttribute('password'));
				this.servers.set(server.id, server);
				this.liaison_server_applications.set(server.id, new Array());
			} catch(e) {
				this.push_log('error', '[daemon] parse_list_servers(transport@list_servers()) - Invalid XML (Missing argument for "server" node '+i+')');
				return;
			}
		}
	},

	do_ended: function() {
		this.push_log('debug', '[daemon] do_ended()');

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
				error_toggle_img.setAttribute('style', 'width: 9px; height: 9px;');
				error_toggle_img.innerHTML = '<img src="../media/image/show.png" width="9" height="9" alt="+" title="" />';
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
			} else {
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

		this.stopped = true;
	}
});
