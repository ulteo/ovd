var refresh = 2000;

var server;
var session;
var timestamp;
var lead;
var debug;

var my_width;
var my_height;

var session_state = -1;
var old_session_state = -1;

var window_alive = true;

function daemon_init(server_, session_, timestamp_, lead_, debug_) {
	server = server_;
	session = session_;
	timestamp = timestamp_;
	lead = lead_;
	debug = debug_;

	$('printerContainer').hide();

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

	if ($('menuShareFrame')) {
		$('menuShareFrame').style.width = my_width+'px';
		$('menuShareFrame').style.height = my_height+'px';

		my_height = parseInt(my_height)-18;
	}

	if (debug)
		my_height = parseInt(my_height)-150;

	daemon_loop();
}

function daemon_loop() {
	push_log('[daemon] loop()', 'debug');

	session_check();

	if (session_state == 0 || session_state == 10) {
		new Ajax.Request(
			'startsession.php',
			{
				method: 'get',
				parameters: {
					width: parseInt(my_width),
					height: parseInt(my_height),
					lead: lead
				}
			}
		);
	} if (session_state == 2 && $('splashContainer').visible()) {
		switch_splash_to_applet();
	} else if (old_session_state == 2 && session_state != 2) {
		window_alive = false;
		switch_applet_to_end();
		return;
	}

	print_check();

	setTimeout(function() {
		daemon_loop();
	}, refresh);
}

function switch_splash_to_applet() {
	new Ajax.Request(
		'applet.php',
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

function onSuccessSession(transport) {
	if (!window_alive)
		return;

	old_session_state = session_state;
	session_state = transport.responseText;

	if (session_state != old_session_state)
		push_log('[session] Change status from '+old_session_state+' to '+session_state, 'info');

	if (session_state != 2)
		push_log('[session] Status: '+session_state, 'warning');
	else
		push_log('[session] Status: '+session_state, 'debug');
}

function onPrint(transport) {
	if (!window_alive)
		return;

	if (transport.status == 200) {
		push_log('[print] PDF: yes', 'info');

		next_timestamp = transport.getResponseHeader('UlteoPrintTime');
		print_filename = transport.getResponseHeader('UlteoFileName');
		print_url = 'http://'+server+'/webservices/print.php?session='+session+'&timestamp='+timestamp;
		timestamp = next_timestamp;

		$('printerContainer').innerHTML = '<applet code="com.ulteo.OnlineDesktopPrinting" archive="ulteo-printing-0.5.jar" codebase="applet/" width="1" height="1" name="ulteoprinting"><param name="url" value="'+print_url+'"><param name="filename" value="'+print_filename+'"></applet>';

		$('printerContainer').show();

		push_log('[print] Applet: starting', 'warning');
	} else
		push_log('[print] PDF: no', 'debug');
}

function session_check() {
	push_log('[session] check()', 'debug');

	new Ajax.Request(
		'webservices/session_status.php',
		{
			method: 'get',
			parameters: {
				session: session
			},
			asynchronous: false,
			onSuccess: onSuccessSession
		}
	);
}

function print_check() {
	push_log('[print] check()', 'debug');

	new Ajax.Request(
		'webservices/print.php',
		{
			method: 'get',
			parameters: {
				session: session,
				timestamp: timestamp,
				lead: lead
			},
			onSuccess: onPrint,
			onFailure: onPrint
		}
	);
}
