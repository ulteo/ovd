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

var Daemon = Class.create({
	i18n: new Array(),

	applet_version: '',
	applet_main_class: '',
	printing_applet_version: '',
	debug: '',

	protocol: '',
	server: '',
	port: '',

	shareable: false,
	persistent: false,
	in_popup: true,
	shared: false,

	session_state: -1,
	old_session_state: -1,
	started: false,
	stopped: false,
	access_id: '',

	application_state: -1,
	old_application_state: -1,
	app_id: '',
	doc: '',

	error_message: '',

	applet_width: -1,
	applet_height: -1,

	nb_share: 0,

	initialize: function(applet_version_, applet_main_class_, printing_applet_version_, debug_) {
		this.applet_version = applet_version_;
		this.applet_main_class = applet_main_class_;
		this.printing_applet_version = printing_applet_version_;
		this.debug = debug_;

		this.protocol = window.location.protocol;
		this.server = window.location.hostname;
		this.port = window.location.port;
		if (this.port == '')
			this.port = 80;

		this.session_state = -1;
		this.old_session_state = -1;
		this.started = false;

		if (this.debug) {
			$('debugContainer').style.display = 'inline';
			$('debugLevels').style.display = 'inline';
		}

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

		if (this.debug)
			this.my_height = parseInt(this.my_height)-149;

		setTimeout(this.preload.bind(this), 2000);

		Event.observe(window, 'unload', this.client_exit.bind(this));
	},

	preload: function() {
		if ($('printerContainer')) {
			$('printerContainer').show();
			$('printerContainer').innerHTML = '<applet code="com.ulteo.OnlineDesktopPrinting" archive="'+this.printing_applet_version+'" codebase="../applet/" width="1" height="1" name="ulteoprinting"> \
				<param name="do_nothing" value="1"> \
			</applet>';
		}
	},

	push_log: function(data_, level_) {
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

	loop: function() {
		this.push_log('[daemon] loop()', 'debug');

		this.check_status();

		if (this.session_state == 0 || this.session_state == 10) {
			this.start_request();
		} else if (this.session_state == 2 && $('splashContainer').visible() && ! $('appletContainer').visible()) {
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
			'../suspend.php',
			{
				method: 'get'
			}
		);

		this.do_ended();
	},

	logout: function() {
		new Ajax.Request(
			'../exit.php',
			{
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
		this.push_log('[daemon] check_status()', 'debug');

		new Ajax.Request(
			'../whatsup.php',
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
	},

	start_request: function() {
		this.push_log('[daemon] start_request()', 'debug');

		new Ajax.Request(
			'../start.php',
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
		this.do_started();
	},

	do_started: function() {
		new Ajax.Request(
			'../access.php',
			{
				method: 'get',
				parameters: {
					application_id: this.access_id
				},
				onSuccess: this.parse_do_started.bind(this)
			}
		);
	},

	parse_do_started: function(transport) {
		var buffer;

		$('splashContainer').hide();
		if ($('menuContainer'))
			$('menuContainer').show();

		try {
			var xml = transport.responseXML;
			buffer = xml.getElementsByTagName('session');
			if (buffer.length != 1) {
				this.push_log('[applet] bad xml format 1', 'error');
				return;
			}

			var sessionNode = buffer[0];

			buffer = sessionNode.getElementsByTagName('parameters');
			var parametersNode = buffer[0];

			if (this.applet_width == -1)
				this.applet_width = parametersNode.getAttribute('width');
			if (this.applet_height == -1)
				this.applet_height = parametersNode.getAttribute('height');

			var vnc_width = parametersNode.getAttribute('width');
			var vnc_height = parametersNode.getAttribute('height');
			if (vnc_width >= this.applet_width && vnc_height >= this.applet_height) { // more vnc than applet
				if ((this.applet_width/vnc_width) < (this.applet_height/vnc_height)) { // more ratio on width
					this.applet_height = (vnc_height*(this.applet_width/vnc_width));
					this.applet_width = (vnc_width*(this.applet_width/vnc_width));
				} else { // more ratio on height
					this.applet_width = (vnc_width*(this.applet_height/vnc_height));
					this.applet_height = (vnc_height*(this.applet_height/vnc_height));
				}
			} else if (vnc_width > this.applet_width && vnc_height <= this.applet_height) { // more vnc width than applet width
				this.applet_width = (vnc_width*(this.applet_width/vnc_width));
				this.applet_height = (vnc_height*(this.applet_width/vnc_width));
			} else if (vnc_width <= this.applet_width && vnc_height > this.applet_height) { // more vnc height than applet height
				this.applet_height = (vnc_height*(this.applet_height/vnc_height));
				this.applet_width = (vnc_width*(this.applet_height/vnc_height));
			} else { // less vnc than applet
				this.applet_width = vnc_width;
				this.applet_height = vnc_height;
			}
			this.applet_width = parseInt(this.applet_width);
			this.applet_height = parseInt(this.applet_height);
			
			applet_view_only = parametersNode.getAttribute('view_only');

			buffer = sessionNode.getElementsByTagName('ssh');
			var sshNode = buffer[0];

			applet_ssh_host = sshNode.getAttribute('host');
			applet_ssh_user = sshNode.getAttribute('user');
			applet_ssh_passwd = sshNode.getAttribute('passwd');

			buffer = sshNode.getElementsByTagName('port');
			applet_ssh_ports = '';
			for (var i = 0; i < buffer.length; i++) {
				applet_ssh_ports = applet_ssh_ports+buffer[i].firstChild.nodeValue;
				if (i < buffer.length-1)
					applet_ssh_ports = applet_ssh_ports+',';
			}

			if (this.access_id != 'portal') {
				buffer = sessionNode.getElementsByTagName('vnc');
				var vncNode = buffer[0];
				applet_vnc_port = vncNode.getAttribute('port');
				applet_vnc_passwd = vncNode.getAttribute('passwd');
				applet_vnc_quality = vncNode.getAttribute('quality');

				//default: highest
				applet_vnc_quality_compression_level = 9;
				applet_vnc_quality_jpeg_image_quality = 9;
				applet_vnc_quality_restricted_colors = 'no';
				if (applet_vnc_quality == 'lowest') {
					applet_vnc_quality_jpeg_image_quality = 8;
					applet_vnc_quality_restricted_colors = 'yes';
				} else if (applet_vnc_quality == 'medium')
					applet_vnc_quality_jpeg_image_quality = 7;
				else if (applet_vnc_quality == 'high')
					applet_vnc_quality_jpeg_image_quality = 8;
			}

			applet_have_proxy = false;
			buffer = sessionNode.getElementsByTagName('proxy');
			if (buffer.length == 1) {
				applet_have_proxy = true;

				var proxyNode = buffer[0];

				applet_proxy_type = proxyNode.getAttribute('type');
				applet_proxy_host = proxyNode.getAttribute('host');
				applet_proxy_port = proxyNode.getAttribute('port');
				applet_proxy_username = proxyNode.getAttribute('username');
				applet_proxy_password = proxyNode.getAttribute('password');
			}
		} catch(e) {
			this.push_log('[applet] bad xml format 2', 'error');
			return;
		}

		applet_html_string = '<applet name="ulteoapplet" code="'+this.applet_main_class+'" codebase="../applet/" archive="'+this.applet_version+'" cache_archive="'+this.applet_version+'" cache_archive_ex="'+this.applet_version+';preload" mayscript="true" width="'+this.applet_width+'" height="'+this.applet_height+'"> \
			<param name="name" value="ulteoapplet" /> \
			<param name="code" value="'+this.applet_main_class+'" /> \
			<param name="codebase" value="../applet/" /> \
			<param name="archive" value="'+this.applet_version+'" /> \
			<param name="cache_archive" value="'+this.applet_version+'" /> \
			<param name="cache_archive_ex" value="'+this.applet_version+';preload" /> \
			<param name="mayscript" value="true" /> \
			\
			<param name="errorCallback" value="daemon.errorCallback" /> \
			\
			<param name="ssh.host" value="'+applet_ssh_host+'" /> \
			<param name="ssh.port" value="'+applet_ssh_ports+'" /> \
			<param name="ssh.user" value="'+applet_ssh_user+'" /> \
			<param name="ssh.password" value="'+applet_ssh_passwd+'" />';

		if (this.access_id != 'portal') {
			applet_html_string = applet_html_string+'<param name="View only" value="'+applet_view_only+'" /> \
				\
				<param name="PORT" value="'+applet_vnc_port+'" /> \
				<param name="ENCPASSWORD" value="'+applet_vnc_passwd+'" /> \
				\
				<param name="Compression level" value="'+applet_vnc_quality_compression_level+'" /> \
				<param name="Restricted colors" value="'+applet_vnc_quality_restricted_colors+'" /> \
				<param name="JPEG image quality" value="'+applet_vnc_quality_jpeg_image_quality+'" /> \
				\
				<!-- Caching options --> \
				<param name="rfb.cache.enabled" value="true" /> \
				<param name="rfb.cache.ver.major" value="1" /> \
				<param name="rfb.cache.ver.minor" value="0" /> \
				<param name="rfb.cache.size" value="42336000" /> \
				<param name="rfb.cache.alg" value="LRU" /> \
				<param name="rfb.cache.datasize" value="2000000" />';
		}

		if (applet_have_proxy) {
			applet_html_string = applet_html_string+'<param name="proxyType" value="'+applet_proxy_type+'" /> \
				<param name="proxyHost" value="'+applet_proxy_host+'" /> \
				<param name="proxyPort" value="'+applet_proxy_port+'" /> \
				<param name="proxyUsername" value="'+applet_proxy_username+'" /> \
				<param name="proxyPassword" value="'+applet_proxy_password+'" />';
		}

		applet_html_string = applet_html_string+'</applet>';

		$('appletContainer').innerHTML = applet_html_string;

		var appletNode = $('appletContainer').getElementsByTagName('applet');
		if (appletNode.length > 0) {
			appletNode = appletNode[0];

			appletNode.width = this.applet_width;
			appletNode.height = this.applet_height;
		}

		if ($('mainWrap'))
			$('mainWrap').show();
		$('appletContainer').show();
	},

	do_ended: function() {
		if (this.stopped == true)
			return;

		this.stopped = true;

		$('splashContainer').innerHTML = '';
		$('splashContainer').hide();
		$('appletContainer').hide();

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
	},

	errorCallback: function(error_status_, error_string_) {
		this.error_message = 'Java: '+error_string_;

		this.do_ended();
	},

	do_print: function(path_, timestamp_) {
		this.push_log('[print] PDF: yes', 'info');

		var print_url = this.protocol+'//'+this.server+':'+this.port+'/applicationserver/print.php?timestamp='+timestamp_;

			$('printerContainer').show();
			$('printerContainer').innerHTML = '<applet code="com.ulteo.OnlineDesktopPrinting" archive="'+this.printing_applet_version+'" codebase="../applet/" width="1" height="1" name="ulteoprinting"> \
				<param name="url" value="'+print_url+'"> \
					<param name="filename" value="'+path_+'"> \
				</applet>';

		this.push_log('[print] Applet: starting', 'warning');
	}
});

function clearDebug() {
	$('debugContainer').innerHTML = '';
}

function switchDebug(level_) {
	var flag = ($('debugContainer').scrollTop+$('debugContainer').offsetHeight) == $('debugContainer').scrollHeight;

	var buf = $('debugContainer').className;

	if (buf.match('no_'+level_))
		buf = buf.replace('no_'+level_, level_);
	else
		buf = buf.replace(level_, 'no_'+level_);

	$('debugContainer').className = buf;

	if (flag)
		$('debugContainer').scrollTop = $('debugContainer').scrollHeight;
}

function offContent(container) {
	$(container+'_ajax').innerHTML = '<img src="../media/image/show.png" width="16" height="16" alt="+" title="" />';
	$(container+'_content').hide();

	return true;
}

function onContent(container) {
	$(container+'_ajax').innerHTML = '<img src="../media/image/hide.png" width="16" height="16" alt="-" title="" />';
	$(container+'_content').show();

	return true;
}

function toggleContent(container) {
	if ($(container+'_content').visible())
		offContent(container);
	else
		onContent(container);

	return true;
}

function doInvite(mode_) {
	if (mode_ == 'desktop')
		var invite_access_id = 'desktop';
	else if (mode_ == 'portal')
		var invite_access_id = $('invite_access_id').value;

	var email = $('invite_email').value;
	var mode = 'passive';
	if ($('invite_mode').checked)
		mode = 'active';
	$('invite_submit').disabled = true;

	new Ajax.Request(
		'../invite.php',
		{
			method: 'post',
			parameters: {
				'email': email,
				'mode': mode,
				'access_id': invite_access_id
			},
			onSuccess: function(transport) {
				if (transport.responseText != 'OK') {
					if (mode_ == 'desktop')
						$('menuShareError').innerHTML = '<ul><li>Unable to send invitation mail, please try again later...</li></ul>';
					else if (mode_ == 'portal')
						showError('Unable to send invitation mail, please try again later...');
				} else if (transport.responseText == 'OK') {
					if (mode_ == 'portal')
						showOk('Invitation has been sent !');
				}
			}
		}
	);

	$('invite_email').value = '';
	$('invite_mode').checked = false;
	$('invite_submit').disabled = false;
}
