var refresh = 2000;

var applet_version;
var applet_main_class;
var printing_applet_version;
var protocol;
var server;
var port;
var debug;

var my_width;
var my_height;

var session_state = -1;
var old_session_state = -1;

var nb_share = 0;

var application_started = false;
var window_alive = true;

function daemon_init(applet_version_, applet_main_class_, printing_applet_version_, debug_) {
	applet_version = applet_version_;
	applet_main_class = applet_main_class_;
	printing_applet_version = printing_applet_version_;
	protocol = window.location.protocol;
	server = window.location.host;
	port = window.location.port;
	debug = debug_;

	$('printerContainer').show();
	$('printerContainer').innerHTML = '<applet code="com.ulteo.OnlineDesktopPrinting" archive="'+printing_applet_version+'" codebase="/applet/" width="1" height="1" name="ulteoprinting"> \
		<param name="do_nothing" value="1"> \
	</applet>';

	push_log('[daemon] init()', 'info');

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

	if ($('menuShare')) {
		$('menuShare').style.width = my_width+'px';
		var new_height = parseInt(my_height)-18;
		if (debug)
			new_height = parseInt(new_height)-149;
		$('menuShare').style.height = new_height+'px';

		my_height = parseInt(my_height)-18;
	}

	if (debug)
		my_height = parseInt(my_height)-149;

	Event.observe(window, 'unload', function() {
		client_exit();
	});

	daemon_loop();
}

function daemon_loop() {
	push_log('[daemon] loop()', 'debug');

	session_check();

	if (session_state == 0 || session_state == 10) {
		new Ajax.Request(
			'../start.php',
			{
				method: 'get',
				parameters: {
					width: parseInt(my_width),
					height: parseInt(my_height)
				}
			}
		);
	} if (session_state == 2 && $('splashContainer').visible() && !$('appletContainer').visible()) {
		if (! application_started)
			start_app('desktop');

		application_started = true;
	} else if ((old_session_state == 2 && session_state != 2) || session_state == 3 || session_state == 4) {
		window_alive = false;
		switch_applet_to_end();
		return;
	}

	setTimeout(function() {
		daemon_loop();
	}, refresh);
}

function start_app(command_) {
	new Ajax.Request(
		'../start_app.php',
		{
			method: 'get',
			parameters: {
				app_id: 'desktop',
				command: command_,
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

				switch_splash_to_applet(access_id);
			}
		}
	);
}

function switch_splash_to_applet(access_id_) {
	new Ajax.Request(
		'../access.php',
		{
			method: 'get',
			parameters: {
				application_id: access_id_
			},
			onSuccess: function(transport) {
				var buffer;

				$('splashContainer').hide();
				if ($('menuContainer'))
					$('menuContainer').show();

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

					buffer = sessionNode.getElementsByTagName('proxy');
					if (buffer.length == 1) {
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

				$('appletContainer').innerHTML = '<applet code="'+applet_main_class+'" codebase="/applet/" archive="'+applet_version+'" mayscript="true" width="'+applet_width+'" height="'+applet_height+'"> \
					<param name="name" value="ulteoapplet" /> \
					<param name="code" value="'+applet_main_class+'" /> \
					<param name="codebase" value="/applet/" /> \
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
					<param name="rfb.cache.datasize" value="2000000" /> \
					\
					<param name="proxyType" value="'+applet_proxy_type+'" /> \
					<param name="proxyHost" value="'+applet_proxy_host+'" /> \
					<param name="proxyPort" value="'+applet_proxy_port+'" /> \
					<param name="proxyUsername" value="'+applet_proxy_username+'" /> \
					<param name="proxyPassword" value="'+applet_proxy_password+'" /> \
				</applet>';

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
	if ($('menuContainer'))
		$('menuContainer').hide();
	$('appletContainer').hide();
	$('endContainer').show();

// 	if (text_ != false)
// 		$('errorContainer').innerHTML = text_;
}

function client_exit() {
	new Ajax.Request(
		'../exit.php',
		{
			method: 'get'
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

function session_check() {
	push_log('[session] check()', 'debug');
	new Ajax.Request(
		'../whatsup.php',
		{
			method: 'get',
			asynchronous: false,
			parameters: {
					differentiator: Math.floor(Math.random()*50000)
			},
			onSuccess: onUpdateInfos
		}
	);
}

function onUpdateInfos(transport) {
  var xml = transport.responseXML;

  var buffer = xml.getElementsByTagName('session');

  if (buffer.length != 1) {
    push_log('[session] bad xml format', 'error');
    return;
  }

  var sessionNode = buffer[0];

  old_session_state = session_state;

  try { // IE does not have hasAttribute in DOM API...
    session_state = sessionNode.getAttribute('status');
  } catch(e) {
    push_log('[session] bad xml format', 'error');
    return;
  }

  if (session_state != old_session_state)
    push_log('[session] Change status from '+old_session_state+' to '+session_state, 'info');
  if (session_state != 2)
    push_log('[session] Status: '+session_state, 'warning');
  else
    push_log('[session] Status: '+session_state, 'debug');

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

    if (nb > 0) {
        var totoNodes = sharingNode.getElementsByTagName('share');

        var html = '<div style="margin-left: 0px; margin-right: 0px; text-align: left"><ul>';

        var nb_share_active = 0;
        for (var i = 0; i < totoNodes.length; i++) {
            var buf = totoNodes[i];

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

      if (nb_share != nb_share_active) {
        push_log('[session] Watching desktop: '+nb_share_active+' users', 'info');
        nb_share = nb_share_active;
      }

      if (nb_share_active != 0) {
        var buf_html = '<img style="margin-left: 5px;" src="media/image/watch_icon.png" width="16" height="16" alt="" title="" /> <span style="font-size: 0.8em;">Currently watching your desktop: '+nb_share_active+' user';
        if (nb_share_active > 1)
          buf_html += 's';
        buf_html += '</span>';
        $('menuShareWarning').innerHTML = buf_html;
      } else
        $('menuShareWarning').innerHTML = '';
    }
  }
}

function do_print(path, timestamp) {
  push_log('[print] PDF: yes', 'info');

  var print_url = protocol+'//'+server+':'+port+'/print.php?timestamp='+timestamp;

	$('printerContainer').show();
	$('printerContainer').innerHTML = '<applet code="com.ulteo.OnlineDesktopPrinting" archive="'+'+printing_applet_version+'+'" codebase="/applet/" width="1" height="1" name="ulteoprinting"> \
		<param name="url" value="'+print_url+'"> \
		<param name="filename" value="'+path+'"> \
	</applet>';

  push_log('[print] Applet: starting', 'warning');
}

function do_invite() {
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
				'mode': mode
			},
			onSuccess: function(transport) {
				if (transport.responseText != 'OK') {
					$('invite_email').disabled = true;
					$('invite_mode').disabled = true;
					$('invite_submit').disabled = true;

					$('menuShareError').innerHTML = '<ul><li>Unable to send invitation mail, please try again later...</li></ul>';
				} else if (transport.responseText == 'OK') {
					$('invite_submit').disabled = false;
				}
			}
		}
	);

	$('invite_email').value = '';
	$('invite_mode').checked = false;
}
