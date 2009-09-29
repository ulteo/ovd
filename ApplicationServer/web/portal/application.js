var refresh = 2000;

var app_id;
var doc;
var access_id;
var applet_version;
var applet_main_class;
var protocol;
var server;
var port;
var debug;

var my_width;
var my_height;

var session_state = -1;
var old_session_state = -1;

var application_state = -1;
var old_application_state = -1;

var nb_share = 0;

var application_started = false;
var window_alive = true;

function application_init(app_id_, doc_, applet_version_, applet_main_class_, debug_) {
	app_id = app_id_;
	doc = doc_;
	applet_version = applet_version_;
	applet_main_class = applet_main_class_;
	protocol = window.location.protocol;
	server = window.location.host;
	port = window.location.port;
	debug = debug_;

	push_log('[application] init()', 'info');

	if (debug) {
		$('debugContainer').style.display = 'inline';
		$('debugLevels').style.display = 'inline';
	}

	if (typeof(window.innerWidth) == 'number' || typeof(window.innerHeight) == 'number') {
		my_width  = window.innerWidth;
		my_height = window.innerHeight;
	} else if (document.documentElement && (document.documentElement.clientWidth || document.documentElement.clientHeight)) {
		my_width  = document.documentElement.clientWidth;
		my_height = document.documentElement.clientHeight;
	} else if (document.body && (document.body.clientWidth || document.body.clientHeight)) {
		my_width  = document.body.clientWidth;
		my_height = document.body.clientHeight;
	}

	if (debug)
		my_height = parseInt(my_height)-149;

	Event.observe(window, 'unload', function() {
		client_exit();
	});

	application_loop();
}

function application_init_resume(applet_version_, applet_main_class_, access_id_, debug_) {
	applet_version = applet_version_;
	applet_main_class = applet_main_class_;
	access_id = access_id_;
	protocol = window.location.protocol;
	server = window.location.host;
	port = window.location.port;
	debug = debug_;

	push_log('[application] init_resume()', 'info');

	if (debug) {
		$('debugContainer').style.display = 'inline';
		$('debugLevels').style.display = 'inline';
	}

	if (typeof(window.innerWidth) == 'number' || typeof(window.innerHeight) == 'number') {
		my_width  = window.innerWidth;
		my_height = window.innerHeight;
	} else if (document.documentElement && (document.documentElement.clientWidth || document.documentElement.clientHeight)) {
		my_width  = document.documentElement.clientWidth;
		my_height = document.documentElement.clientHeight;
	} else if (document.body && (document.body.clientWidth || document.body.clientHeight)) {
		my_width  = document.body.clientWidth;
		my_height = document.body.clientHeight;
	}

	if (debug)
		my_height = parseInt(my_height)-149;

	Event.observe(window, 'unload', function() {
		client_exit();
	});

	application_loop_resume();
}

function application_loop() {
	push_log('[application] loop()', 'debug');

	application_check();

	if (session_state == 2 && $('splashContainer').visible() && !$('appletContainer').visible()) {
		if (! application_started)
			start_app();

		application_started = true;
	} else if ((old_session_state == 2 && session_state != 2) || session_state == 3 || session_state == 4 || (old_application_state == 2 && application_state != 2) || application_state == 3 || application_state == 4) {
		window_alive = false;
		switch_applet_to_end();
		return;
	}

	setTimeout(function() {
		application_loop();
	}, refresh);
}

function application_loop_resume() {
	push_log('[application] loop()', 'debug');

	application_check();

	if (session_state == 2 && $('splashContainer').visible() && !$('appletContainer').visible()) {
		if (! application_started)
			resume_app();

		application_started = true;
	} else if ((old_session_state == 2 && session_state != 2) || session_state == 3 || session_state == 4 || (old_application_state == 2 && application_state != 2) || application_state == 3 || application_state == 4) {
		window_alive = false;
		switch_applet_to_end();
		return;
	}

	setTimeout(function() {
		application_loop();
	}, refresh);
}

function start_app() {
	new Ajax.Request(
		'../start_app.php',
		{
			method: 'get',
			parameters: {
				app_id: app_id,
				doc: doc,
				size: my_width+'x'+my_height
			},
			onSuccess: function(transport) {
				try {
					var xml = transport.responseXML;
					buffer = xml.getElementsByTagName('access');
					if (buffer.length != 1) {
						push_log('[start_app] bad xml format 1', 'error');
						return;
					}

					var accessNode = buffer[0];

					access_id = accessNode.getAttribute('id');
				} catch(e) {
					push_log('[start_app] bad xml format 2', 'error');
					return;
				}

				switch_splash_to_applet();
			}
		}
	);
}

function resume_app() {
	new Ajax.Request(
		'resume_app.php',
		{
			method: 'get',
			parameters: {
				access_id: access_id,
			},
			onSuccess: function(transport) {
				switch_splash_to_applet();
			}
		}
	);
}

function switch_splash_to_applet() {
	new Ajax.Request(
		'../access.php',
		{
			method: 'get',
			parameters: {
				application_id: access_id
			},
			onSuccess: function(transport) {
				var buffer;

				$('splashContainer').hide();

				try {
					var xml = transport.responseXML;
					buffer = xml.getElementsByTagName('session');
					if (buffer.length != 1) {
						push_log('[applet] bad xml format', 'error');
						return;
					}

					var sessionNode = buffer[0];

					buffer = sessionNode.getElementsByTagName('parameters');
					var parametersNode = buffer[0];

					applet_width = parametersNode.getAttribute('width');
					applet_height = parametersNode.getAttribute('height');
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
					push_log('[applet] bad xml format', 'error');
					return;
				}

				applet_html_string = '<applet code="'+applet_main_class+'" codebase="../applet/" archive="'+applet_version+'" mayscript="true" width="'+applet_width+'" height="'+applet_height+'"> \
					<param name="name" value="ulteoapplet" /> \
					<param name="code" value="'+applet_main_class+'" /> \
					<param name="codebase" value="../applet/" /> \
					<param name="archive" value="'+applet_version+'" /> \
					<param name="cache_archive" value="'+applet_version+'" /> \
					<param name="cache_archive_ex" value="'+applet_version+';preload" /> \
					\
					<param name="Share desktop" value="'+applet_share_desktop+'" /> \
					<param name="View only" value="'+applet_view_only+'" /> \
					\
					<param name="SSH" value="yes" /> \
					<param name="ssh.host" value="'+applet_ssh_host+'" /> \
					<param name="ssh.port" value="'+applet_ssh_ports+'" /> \
					<param name="ssh.user" value="'+applet_ssh_user+'" /> \
					<param name="ssh.password" value="'+applet_ssh_passwd+'" /> \
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
					appletNode.width = parseInt(my_width);
					appletNode.height = parseInt(my_height);
				}
				$('appletContainer').show();
			}
		}
	);
}

function switch_applet_to_end() {
	$('splashContainer').hide();
	$('appletContainer').hide();
// 	$('endContainer').show();

	window.close();

// 	if (text_ != false)
// 		$('errorContainer').innerHTML = text_;
}

function client_exit() {
	new Ajax.Request(
		'application_exit.php',
		{
			method: 'get',
			parameters: {
				access_id: access_id
			}
		}
	);
}

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

function push_log(data_, level_) {
	if (!debug)
		return;

	//if (! $('level_'+level_).checked)
	//	return;

	var flag = ($('debugContainer').scrollTop+$('debugContainer').offsetHeight) == $('debugContainer').scrollHeight;

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
}

function application_check() {
	push_log('[session] check()', 'debug');
	new Ajax.Request(
		'../whatsup.php',
		{
			method: 'get',
			parameters: {
				application_id: access_id,
				differentiator: Math.floor(Math.random()*50000)
			},
			asynchronous: false,
			onSuccess: onUpdateInfos
		}
	);
}

function onUpdateInfos(transport) {
  var xml = transport.responseXML;

  var buffer = xml.getElementsByTagName('session');

  if (buffer.length != 1) {
    push_log('[session] bad xml format 1', 'error');
    return;
  }

  var sessionNode = buffer[0];

  old_session_state = session_state;

  try { // IE does not have hasAttribute in DOM API...
    session_state = sessionNode.getAttribute('status');
  } catch(e) {
    push_log('[session] bad xml format 2', 'error');
    return;
  }

  if (session_state != old_session_state)
    push_log('[session] Change status from '+old_session_state+' to '+session_state, 'info');
  if (session_state != 2)
    push_log('[session] Status: '+session_state, 'warning');
  else
    push_log('[session] Status: '+session_state, 'debug');

  var buffer = xml.getElementsByTagName('application');

  if (buffer.length != 1) {
    push_log('[application] bad xml format 1', 'error');
    return;
  }

  var applicationNode = buffer[0];

  old_application_state = application_state;

  try { // IE does not have hasAttribute in DOM API...
    application_state = applicationNode.getAttribute('status');
  } catch(e) {
    push_log('[application] bad xml format 2', 'error');
    return;
  }

  if (application_state != old_application_state)
    push_log('[application] Change status from '+old_application_state+' to '+application_state, 'info');
  if (application_state != 2)
    push_log('[application] Status: '+application_state, 'warning');
  else
    push_log('[application] Status: '+application_state, 'debug');

  var printNode = sessionNode.getElementsByTagName('print');
  if (printNode.length > 0) {
    printNode = printNode[0];

    var path = printNode.getAttribute('path');
    var timestamp = printNode.getAttribute('time');
    do_print(path, timestamp);
  }

  var sharingNode = sessionNode.getElementsByTagName('sharing');
  if (sharingNode.length > 0) {
    sharingNode = sharingNode[0];

    try { // IE does not have hasAttribute in DOM API...
      var nb = sharingNode.getAttribute('count');
    } catch(e) {
      push_log('[session] bad xml format', 'error');
      return;
    }
  }
}
