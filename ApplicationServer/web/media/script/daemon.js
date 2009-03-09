var refresh = 2000;

var server;
var debug;

var my_width;
var my_height;

var session_state = -1;
var old_session_state = -1;

var nb_share = 0;

var window_alive = true;

function daemon_init(server_, debug_) {
	server = server_;
	debug = debug_;

	$('printerContainer').show();
	$('printerContainer').innerHTML = '<applet code="com.ulteo.OnlineDesktopPrinting" archive="ulteo-printing-0.5.1.jar" codebase="http://'+server_+'/applet/" width="1" height="1" name="ulteoprinting"><param name="do_nothing" value="1"></applet>';

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
		my_height = parseInt(my_height)-150;

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
			'start.php',
			{
				method: 'get',
				parameters: {
					width: parseInt(my_width),
					height: parseInt(my_height)
				}
			}
		);
	} if (session_state == 2 && $('splashContainer').visible() && !$('appletContainer').visible()) {
		switch_splash_to_applet();
	} else if ((old_session_state == 2 && session_state != 2) || session_state == 3 || session_state == 4) {
		window_alive = false;
		switch_applet_to_end();
		return;
	}

	setTimeout(function() {
		daemon_loop();
	}, refresh);
}

function switch_splash_to_applet() {
	new Ajax.Request(
		'access.php',
		{
			method: 'get',
			parameters: {
				html: 1,
				width: parseInt(my_width),
				height: parseInt(my_height)
			},
			onSuccess: function(transport) {
				$('splashContainer').hide();
				if ($('menuContainer'))
					$('menuContainer').show();

				$('appletContainer').show();
				$('appletContainer').innerHTML = transport.responseText;
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
		'client_exit.php',
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
		'whatsup.php',
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

        for (var i = 0; i < totoNodes.length; i++) {
            var nb_share = 0;
            var buf = totoNodes[i];

            var email = buf.getAttribute('email');
            var mode = buf.getAttribute('mode');
            var alive = buf.getAttribute('alive');
            if (alive == 1)
              nb_share += 1;
            var joined = buf.getAttribute('joined');

            html += '<li>'+email+' ('+mode+')</li>';
        }

        html += '</ul></div>';

        $('menuShareContent').innerHTML = html;

      push_log('[session] nb share: '+nb_share, 'info');

      if (nb_share != 0) {
        var buf_html = '<img style="margin-left: 5px;" src="media/image/watch_icon.png" width="16" height="16" alt="" title="" /> <span style="font-size: 0.8em;">Currently watching your desktop: '+nb_share+' user';
        if (nb_share > 1)
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

  var print_url = 'http://'+server+'/print.php?timestamp='+timestamp;

  $('printerContainer').show();
  $('printerContainer').innerHTML = '<applet code="com.ulteo.OnlineDesktopPrinting" archive="ulteo-printing-0.5.1.jar" codebase="http://'+server_+'/applet/" width="1" height="1" name="ulteoprinting"><param name="url" value="'+print_url+'"><param name="filename" value="'+path+'"></applet>';

  push_log('[print] Applet: starting', 'warning');
}

function do_invite() {
	var email = $('invite_email').value;
	var mode = 'passive';
	if ($('invite_mode').checked)
		mode = 'active';
	$('invite_submit').disabled = true;

	new Ajax.Request(
		'invite.php',
		{
			method: 'post',
			parameters: {
				'email': email,
				'mode': mode
			},
			onSuccess: function(transport) {
				if (transport.responseText != 'OK')
					alert(transport.responseText);
			}
		}
	);

	$('invite_email').value = '';
	$('invite_mode').checked = false;
	$('invite_submit').disabled = false;
}
