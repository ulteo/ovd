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

var Daemon = Class.create({
	i18n: new Array(),
	context: null,

	applet_version: '',
	applet_main_class: '',

	sessionmanager: null,

	debug: false,
	explorer: false,

	my_width: 0,
	my_height: 0,

	mode: '',
	keymap: 'en-us',
	duration: -1,
	rdp_imput_method: null,

	settings: new Hash(),

	servers: new Hash(),
	liaison_server_applications: new Hash(),

	persistent: false,

	session_status: '',
	session_status_old: '',
	sessionmanager_request_time: 2000,

	loop_timer: null,

	ready: false,
	ready_lock: false,
	started: false,
	started_lock: false,
	stopped: false,
	stopped_lock: false,

	error_message: '',

	progressbar_value: 0,
	progress_bar_step: 20,

	application_token: 0,

	initialize: function(applet_version_, applet_main_class_, debug_) {
		this.settings = new Hash();
		this.servers = new Hash();
		this.liaison_server_applications = new Hash();

		this.applet_version = applet_version_;
		this.applet_main_class = applet_main_class_;

		this.debug = debug_;

		this.refresh_body_size();

		if (this.debug) {
			$('debugContainer').innerHTML = '';
			$('debugContainer').show();
			$('debugContainer').style.display = 'inline';
			$('debugLevels').show();
			$('debugLevels').style.display = 'inline';
		}

		if ($('progressBar') && $('progressBarContent'))
			this.progressBar();

		window.onbeforeunload = function(e) {
			return i18n.get('window_onbeforeunload');
		}

		try {
			this.rdp_input_method = rdp_input_method;
		} catch(e) {}
		
		Event.observe(window, 'unload', this.client_exit.bind(this));
	},

	refresh_body_size: function() {
		if (document.documentElement && (document.documentElement.clientWidth || document.documentElement.clientHeight)) {
			this.my_width  = document.documentElement.clientWidth;
			this.my_height = document.documentElement.clientHeight;
		} else if (document.body && (document.body.clientWidth || document.body.clientHeight)) {
			this.my_width  = document.body.clientWidth;
			this.my_height = document.body.clientHeight;
		}

		if (this.debug)
			this.my_height = parseInt(this.my_height)-149;
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

	is_ready: function() {
		return this.ready;
	},

	is_started: function() {
		return this.started;
	},

	is_stopped: function() {
		return (this.stopped || this.session_status == 'unknown');
	},

	progressBar: function() {
		if (! $('progressBar') || ! $('progressBarContent'))
			return false;

		if (this.progressbar_value > 100)
			this.progressbar_value = 100;

		this.progressbar_value += this.progress_bar_step;

		$('progressBarContent').style.width = this.progressbar_value+'%';

		if (this.progressbar_value < 100)
			setTimeout(this.progressBar.bind(this), 500);
	},

	warn_expire: function() {
		if (! this.is_stopped()) {
			this.push_log('warning', '[daemon] warn_expire() - Session will expire in 3 minutes');

			alert(i18n.get('session_expire_in_3_minutes'));
		}
	},

	prepare: function() {
		this.push_log('debug', '[daemon] prepare()');

		if (this.duration > 0) {
			if (this.duration > 180)
				setTimeout(this.warn_expire.bind(this), (this.duration-180)*1000);
			else
				this.warn_expire();
		}
	},

	loop: function() {
		this.push_log('debug', '[daemon] loop()');

		this.check_status();

		if (! this.is_stopped()) {
			if (this.session_status == 'logged' && this.session_status_old != 'logged')
				this.sessionmanager_request_time = 60000;

			this.loop_timer = setTimeout(this.loop.bind(this), this.sessionmanager_request_time);
		}
	},

	break_loop: function() {
		this.push_log('debug', '[daemon] break_loop()');

		clearTimeout(this.loop_timer);
	},

	suspend: function() {
		this.push_log('debug', '[daemon] suspend()');

		new Ajax.Request(
			'logout.php',
			{
				method: 'post',
				parameters: {
					mode: 'suspend'
				}
			}
		);

		this.do_ended();
	},

	logout: function() {
		this.push_log('debug', '[daemon] logout()');

		new Ajax.Request(
			'logout.php',
			{
				method: 'post',
				parameters: {
					mode: 'logout'
				}
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

			this.check_status_post();
		} catch(e) {
			this.push_log('error', '[daemon] parse_check_status(transport@check_status()) - Invalid XML (Missing argument for "session" node)');
			return;
		}
	},

	check_status_post: function() {
		if (! this.is_ready()) {
			if (this.ready_lock) {
				this.push_log('debug', '[daemon] check_status_post() - Already in "is_ready" state');
				return;
			}
			this.ready_lock = true;

			this.push_log('info', '[daemon] check_status_post() - Now preparing session');

			this.list_servers();
		} else if (! this.is_started() && this.session_status == 'ready') {
			if (this.started_lock) {
				this.push_log('debug', '[daemon] check_status_post() - Already in "is_started" state');
				return;
			}
			this.started_lock = true;

			this.push_log('info', '[daemon] check_status_post() - Now starting session');

			new Effect.Move($(this.mode+'ModeContainer'), { x: 0, y: my_height });
			setTimeout(function() {
				hideSplash();
			}, 2000);
			
			this.start();

			this.started = true;
		} else if (this.is_stopped()) {
			if (this.stopped_lock) {
				this.push_log('debug', '[daemon] check_status_post() - Already in "is_stopped" state');
				return;
			}
			this.stopped_lock = true;

			this.push_log('info', '[daemon] check_status_post() - Now ending session');

			if (! this.is_started()) {
				this.push_log('warning', '[daemon] check_status_post() - Session end is unexpected (session was never started)');
				this.error_message = this.i18n['session_close_unexpected'];
			}

			this.do_ended();

			this.stopped = true;
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
				method: 'post',
				parameters: {
					requested_host: window.location.hostname,
					requested_port: ((window.location.port !=  '')?window.location.port:'443')
				},
				onSuccess: this.parse_list_servers.bind(this)
			}
		);
	},

	do_ended: function() {
		window.onbeforeunload = function(e) {}

		this.push_log('debug', '[daemon] do_ended()');

		if ($('splashContainer').visible())
			$('splashContainer').hide();
		if ($('desktopFullscreenContainer') && $('desktopFullscreenContainer').visible())
			$('desktopFullscreenContainer').hide();

		if ($(this.mode+'AppletContainer').visible())
			$(this.mode+'AppletContainer').hide();
		$(this.mode+'AppletContainer').innerHTML = '';

		if ($(this.mode+'ModeContainer').visible())
			$(this.mode+'ModeContainer').hide();

		if (this.explorer) {
			if ($('fileManagerWrap'))
				$('fileManagerWrap').hide();
			if ($('fileManagerContainer'))
				$('fileManagerContainer').innerHTML = '';
		}

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

			if ($('loginBox')) {
				var close_container = document.createElement('div');
				close_container.setAttribute('style', 'margin-top: 10px;');
				var close_text = document.createElement('span');
				close_text.innerHTML = this.i18n['start_another_session'];
				close_container.appendChild(close_text);
				buf.appendChild(close_container);
			}

			$('endContent').appendChild(buf);

			$('endContent').innerHTML = $('endContent').innerHTML;

			if (this.error_message != '' && this.error_message != 'undefined')
				offContent('errorContainer');

			showEnd();
		}

		if ($('endMessage')) {
			if (this.error_message != '')
				$('endMessage').innerHTML = '<span class="msg_error">'+this.i18n['session_end_unexpected']+'</span>';
			else
				$('endMessage').innerHTML = this.i18n['session_end_ok'];
		}

		if ($('progressBar') && $('progressBarContent')) {
			$('progressBarContent').style.width = '0px';
			this.progressbar_value = 0;
		}

		this.break_loop();
		this.stopped = true;
	},
	
	parseSessionSettings: function(setting_nodes) {
		this.push_log('debug', '[daemon] parseSessionSettings()');
		
		for (var i=0; i < setting_nodes.length; i++) {
			var name, value;
			try {
				name = setting_nodes[i].getAttribute('name');
				value = setting_nodes[i].getAttribute('value');
			} catch(e) {
				continue;
			}
			
			this.settings.set(name, value);
		}
	}
});
