var Daemon = Class.create({
	applet_version: '',
	applet_main_class: '',
	printing_applet_version: '',
	debug: '',

	protocol: '',
	server: '',
	port: '',

	session_state: -1,
	old_session_state: -1,
	started: false,
	access_id: '',

	application_state: -1,
	old_application_state: -1,
	app_id: '',
	doc: '',

	applet_width: -1,
	applet_height: -1,

	initialize: function(applet_version_, applet_main_class_, printing_applet_version_, debug_) {
		this.applet_version = applet_version_;
		this.applet_main_class = applet_main_class_;
		this.printing_applet_version = printing_applet_version_;
		this.debug = debug_;

		this.protocol = window.location.protocol;
		this.server = window.location.host;
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

		this.preload();

		Event.observe(window, 'unload', this.client_exit.bind(this));
	},

	preload: function() {
		$('printerContainer').show();
		$('printerContainer').innerHTML = '<applet code="com.ulteo.OnlineDesktopPrinting" archive="'+this.printing_applet_version+'" codebase="../applet/" width="1" height="1" name="ulteoprinting"> \
			<param name="do_nothing" value="1"> \
		</applet>';
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
			this.do_ended();

			return;
		}

		setTimeout(this.loop.bind(this), 2000);
	},

	client_exit: function() {
		new Ajax.Request(
			'../exit.php',
			{
				method: 'get',
				parameters: {
					access_id: this.access_id
				}
			}
		);
	},

	check_status: function() {
		this.push_log('[daemon] check_status()', 'debug');

		new Ajax.Request(
			'../whatsup.php',
			{
				method: 'get',
				asynchronous: false,
				parameters: {
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
		this.access_id = 'desktop';

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
			applet_share_desktop = parametersNode.getAttribute('share_desktop');
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

			buffer = sessionNode.getElementsByTagName('vnc');
			var vncNode = buffer[0];
			applet_vnc_host = vncNode.getAttribute('host');
			applet_vnc_port = vncNode.getAttribute('port');
			applet_vnc_passwd = vncNode.getAttribute('passwd');

			buffer = vncNode.getElementsByTagName('quality');
			var vncQualityNode = buffer[0];

			applet_vnc_quality_compression_level = vncQualityNode.getAttribute('compression_level');
			applet_vnc_quality_restricted_colors = vncQualityNode.getAttribute('restricted_colors');
			applet_vnc_quality_jpeg_image_quality = vncQualityNode.getAttribute('jpeg_image_quality');
			applet_vnc_quality_encoding = vncQualityNode.getAttribute('encoding');

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

		applet_html_string = '<applet code="'+this.applet_main_class+'" codebase="../applet/" archive="'+this.applet_version+'" mayscript="true" width="'+this.applet_width+'" height="'+this.applet_height+'"> \
			<param name="name" value="ulteoapplet" /> \
			<param name="code" value="'+this.applet_main_class+'" /> \
			<param name="codebase" value="../applet/" /> \
			<param name="archive" value="'+this.applet_version+'" /> \
			<param name="cache_archive" value="'+this.applet_version+'" /> \
			<param name="cache_archive_ex" value="'+this.applet_version+';preload" /> \
			\
			<param name="SSH" value="yes" /> \
			<param name="ssh.host" value="'+applet_ssh_host+'" /> \
			<param name="ssh.port" value="'+applet_ssh_ports+'" /> \
			<param name="ssh.user" value="'+applet_ssh_user+'" /> \
			<param name="ssh.password" value="'+applet_ssh_passwd+'" /> \
			\
			<param name="Share desktop" value="'+applet_share_desktop+'" /> \
			<param name="View only" value="'+applet_view_only+'" /> \
			\
			<param name="HOST" value="'+applet_vnc_host+'" /> \
			<param name="PORT" value="'+applet_vnc_port+'" /> \
			<param name="ENCPASSWORD" value="'+applet_vnc_passwd+'" /> \
			\
			<param name="Compression level" value="'+applet_vnc_quality_compression_level+'" /> \
			<param name="Restricted colors" value="'+applet_vnc_quality_restricted_colors+'" /> \
			<param name="JPEG image quality" value="'+applet_vnc_quality_jpeg_image_quality+'" /> \
			<param name="Encoding" value="'+applet_vnc_quality_encoding+'" /> \
			\
			<!-- Caching options --> \
			<param name="rfb.cache.enabled" value="true" /> \
			<param name="rfb.cache.ver.major" value="1" /> \
			<param name="rfb.cache.ver.minor" value="0" /> \
			<param name="rfb.cache.size" value="42336000" /> \
			<param name="rfb.cache.alg" value="LRU" /> \
			<param name="rfb.cache.datasize" value="2000000" />';

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
		$('splashContainer').hide();
		$('appletContainer').hide();
		if ($('endContainer'))
			$('endContainer').show();
	},

	do_print: function(path_, timestamp_) {
		this.push_log('[print] PDF: yes', 'info');

		var print_url = this.protocol+'//'+this.server+':'+this.port+'/applicationserver/print.php?timestamp='+timestamp;

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
					$('invite_email').disabled = true;
					$('invite_mode').disabled = true;
					$('invite_submit').disabled = true;

					if (mode_ == 'desktop')
						$('menuShareError').innerHTML = '<ul><li>Unable to send invitation mail, please try again later...</li></ul>';
					else if (mode_ == 'portal')
						showError('Unable to send invitation mail, please try again later...');
				} else if (transport.responseText == 'OK') {
					$('invite_submit').disabled = false;

					if (mode_ == 'portal')
						showOk('Invitation has been sent !');
				}
			}
		}
	);

	$('invite_email').value = '';
	$('invite_mode').checked = false;
}
