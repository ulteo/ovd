var my_width;
var my_height;

function refresh_body_size() {
	if (document.documentElement && (document.documentElement.clientWidth || document.documentElement.clientHeight)) {
		my_width  = document.documentElement.clientWidth;
		my_height = document.documentElement.clientHeight;
	} else if (document.body && (document.body.clientWidth || document.body.clientHeight)) {
		my_width  = document.body.clientWidth;
		my_height = document.body.clientHeight;
	}
}

var date = new Date();
var rand = Math.round(Math.random()*100)+date.getTime();
var window_;

var use_popup = false;
var debug = false;
var startsession = false;

var session_mode = false;

function startSession() {
	disableLogin();

	use_popup = false;
	if ($('use_popup_true') && $('use_popup_true').checked)
		use_popup = true;

	debug = false;
	if ($('debug_true') && $('debug_true').checked)
		debug = true;

	session_mode = $('session_mode').value;
	session_mode = session_mode.substr(0, 1).toUpperCase()+session_mode.substr(1, session_mode.length-1);

	var ret = new Ajax.Request(
		'ajax/login.php',
		{
			method: 'post',
			parameters: {
				sessionmanager_url: $('sessionmanager_url').value,
				use_https: (($('use_https').checked)?1:0),
				login: $('user_login').value,
				password: $('user_password').value,
				mode: $('session_mode').value,
				language: $('session_language').value,
				keymap: $('session_keymap').value,
				use_popup: ((use_popup)?1:0),
				debug: ((debug)?1:0)
			},
			asynchronous: false,
			onSuccess: onStartSessionSuccess,
			onFailure: onStartSessionFailure
		}
	);

	if (parseInt(ret.getStatus()) != 200)
		return false;

	if (! startsession)
		return false;

	if (use_popup) {
		window_ = popupOpen(rand);
		$('startsession').target = 'Ulteo'+rand;
	} else
		return false;

	return true;
}

function hideSplash() {
	new Effect.Fade($('splashContainer'));
}

function showSplash() {
	new Effect.Appear($('splashContainer'));
}

function hideEnd() {
	new Effect.Fade($('endContainer'));
}

function showEnd() {
	new Effect.Appear($('endContainer'));
}

function hideLogin() {
	new Effect.Move($('loginBox'), { x: 0, y: -500 });
}

function showLogin() {
	new Effect.Move($('loginBox'), { x: 0, y: 500 });

	if (debug) {
		$('debugContainer').hide();
		$('debugLevels').hide();
		debug = false;
	}
}

function disableLogin() {
	$('submitButton').hide();
	$('submitLoader').show();
}

function enableLogin() {
	$('submitButton').show();
	$('submitLoader').hide();
}

function onStartSessionSuccess(transport) {
	var xml = transport.responseXML;

	var buffer = xml.getElementsByTagName('error');
	if (buffer.length == 1) {
		try {
			showError(buffer[0].getAttribute('message'));
		} catch(e) {}
		enableLogin();
		return false;
	}

	var buffer = xml.getElementsByTagName('session');
	if (buffer.length != 1) {
		enableLogin();
		return false;
	}
	session_node = buffer[0];

	$('user_password').value = '';

	startsession = true;

	if (! use_popup) {
		hideLogin();
		showSplash();

		if (session_mode == 'Desktop') {
			new Effect.Move($('desktopModeContainer'), { x: 0, y: -my_height });
			setTimeout(function() {
				$('desktopModeContainer').show();
			}, 2000);
		} else {
			new Effect.Move($('applicationsModeContainer'), { x: 0, y: -my_height });
			setTimeout(function() {
				$('applicationsModeContainer').show();
			}, 2000);
		}

		setTimeout(function() {
			if (session_mode == 'Desktop')
				daemon = new Desktop('ulteo-applet.jar', 'org.ulteo.ovd.applet.Desktop', false, debug);
			else
				daemon = new Applications('ulteo-applet.jar', 'org.ulteo.ovd.applet.Applications', false, debug);
			daemon.keymap = $('session_keymap').value;
			daemon.multimedia = ((session_node.getAttribute('multimedia') == 1)?true:false);
			daemon.redirect_client_printers = ((session_node.getAttribute('redirect_client_printers') == 1)?true:false);

			daemon.i18n['session_close_unexpected'] = 'Server: session closed unexpectedly';
			daemon.i18n['session_end_ok'] = 'Your session has ended, you can now close the window';
			daemon.i18n['session_end_unexpected'] = 'Your session has ended unexpectedly';
			daemon.i18n['error_details'] = 'error details';
			daemon.i18n['close_this_window'] = 'Close this window';
			daemon.i18n['start_another_session'] = 'Click <a href="javascript:;" onclick="hideEnd(); showLogin(); return false;">here</a> to start a new session';

			daemon.i18n['suspend'] = 'suspend';
			daemon.i18n['resume'] = 'resume';

			if (debug) {
				if (session_mode == 'Desktop')
					$('desktopModeContainer').style.height = daemon.my_height+'px';
				else
					$('applicationsModeContainer').style.height = daemon.my_height+'px';
			}

			daemon.loop();
		}, 2500);

		setTimeout(function() {
			if (session_mode == 'Desktop')
					new Effect.Move($('desktopModeContainer'), { x: 0, y: my_height });
			else
					new Effect.Move($('applicationsModeContainer'), { x: 0, y: my_height });
		}, 3000);

		setTimeout(function() {
			hideSplash();
			enableLogin();
		}, 5000);
	} else {
		enableLogin();
	}

	return true;
}

function onStartSessionFailure(transport) {
	alert('onStartSessionFailure');

	enableLogin();

	startsession = false;

	return false;
}

function popupOpen(rand_) {
	var w = window.open('about:blank', 'Ulteo'+rand_, 'toolbar=no,status=no,top=0,left=0,width='+screen.width+',height='+screen.height+',scrollbars=no,resizable=no,resizeable=no,fullscreen=no');

	return w;
}

function offContent(container) {
	$(container+'_ajax').innerHTML = '<img src="media/image/show.png" width="9" height="9" alt="+" title="" />';
	$(container+'_content').hide();

	return true;
}

function onContent(container) {
	$(container+'_ajax').innerHTML = '<img src="media/image/hide.png" width="9" height="9" alt="-" title="" />';
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

Event.observe(window, 'load', function() {
	refresh_body_size();

	$('loginBox').hide();

	$('lockWrap').hide();
	$('lockWrap').style.width = my_width+'px';
	$('lockWrap').style.height = my_height+'px';

	$('errorWrap').hide();
	$('okWrap').hide();
	$('infoWrap').hide();

	Event.observe($('lockWrap'), 'click', function() {
		if ($('errorWrap').visible())
			hideError();

		if ($('okWrap').visible())
			hideOk();

		if ($('infoWrap').visible())
			hideInfo();
	});

	testJava();

	setTimeout(function() {
		new Effect.Appear($('loginBox'));
	}, 1000);
});

function showSystemTest() {
	showLock();

	new Effect.Center($('systemTestWrap'));

	new Effect.Appear($('systemTestWrap'));
}

function hideSystemTest() {
	$('systemTestWrap').hide();

	hideLock();
}

function showSystemTestError(error_id_) {
	hideError();

	hideOk();
	hideInfo();

	hideSystemTest();

	showLock();

	$(error_id_).show();

	new Effect.Center($('systemTestErrorWrap'));

	new Effect.Appear($('systemTestErrorWrap'));
}

function testJava() {
	showSystemTest();

	setTimeout(function() {
		try {
			$('CheckJava').isActive();
		} catch(e) {
			showSystemTestError('systemTestError1');
			return;
		}

		$('testJava').innerHTML = '<applet id="ulteoapplet" code="org.ulteo.ovd.applet.CheckJava" codebase="applet/" archive="ulteo-applet.jar" cache_archive="ulteo-applet.jar" cache_archive_ex="ulteo-applet.jar;preload" mayscript="true" width="1" height="1"> \
			<param name="code" value="org.ulteo.ovd.applet.CheckJava" /> \
			<param name="codebase" value="applet/" /> \
			<param name="archive" value="ulteo-applet.jar" /> \
			<param name="cache_archive" value="ulteo-applet.jar" /> \
			<param name="cache_archive_ex" value="ulteo-applet.jar;preload" /> \
			<param name="mayscript" value="true" /> \
			\
			<param name="onSuccess" value="appletSuccess" /> \
			<param name="onFailure" value="appletFailure" /> \
			</applet>';
		testUlteoApplet();
	}, 2000);
}

var ulteo_applet_inited;
function appletSuccess() {
	ulteo_applet_inited = true;
}
function appletFailure() {
	ulteo_applet_inited = false;
}

var ti = 0;
function testUlteoApplet() {
	try {
		if (ulteo_applet_inited == true) {
			hideSystemTest();
			return;
		} else if (ulteo_applet_inited == false) {
			showSystemTestError('systemTestError2');
			return;
		}

		go_to_the_catch_please(); //call a function which does not exist to throw an exception and go to the catch()
	} catch(e) {
		ti += 1;
		setTimeout(function() {
			if (ti < 60) {
				testUlteoApplet();
			} else {
				showSystemTestError('systemTestError2');
				return;
			}
		}, 1000);
	}
}

function showLock() {
	refresh_body_size();

	if (! $('lockWrap').visible()) {
		$('lockWrap').style.width = my_width+'px';
		$('lockWrap').style.height = my_height+'px';

		$('lockWrap').show();
	}
}

function hideLock() {
	if ($('lockWrap').visible() && (! $('errorWrap').visible() && ! $('okWrap').visible() && ! $('infoWrap').visible()))
		$('lockWrap').hide();
}

function showError(errormsg) {
	hideError();

	hideOk();
	hideInfo();

	showLock();

	$('errorWrap').innerHTML = '<div style="width: 16px; height: 16px; float: right"><a href="javascript:;" onclick="hideError(); return false"><img src="media/image/cross.png" width="16" height="16" alt="" title="" /></a></div>'+errormsg;

	new Effect.Center($('errorWrap'));

	new Effect.Appear($('errorWrap'));
}

function hideError() {
	$('errorWrap').hide();

	hideLock();

	$('errorWrap').innerHTML = '';
	$('errorWrap').style.width = '';
	$('errorWrap').style.height = '';
}

function showOk(okmsg) {
	hideOK();

	hideError();
	hideInfo();

	showLock();

	$('okWrap').innerHTML = '<div style="width: 16px; height: 16px; float: right"><a href="javascript:;" onclick="hideOk(); return false"><img src="media/image/cross.png" width="16" height="16" alt="" title="" /></a></div>'+okmsg;

	new Effect.Center($('okWrap'));

	new Effect.Appear($('okWrap'));
}

function hideOk() {
	$('okWrap').hide();

	hideLock();

	$('okWrap').innerHTML = '';
	$('okWrap').style.width = '';
	$('okWrap').style.height = '';
}

function showInfo(infomsg) {
	hideInfo();

	hideError();
	hideOk();

	showLock();

	$('infoWrap').innerHTML = '<div style="width: 16px; height: 16px; float: right"><a href="javascript:;" onclick="hideInfo(); return false"><img src="media/image/cross.png" width="16" height="16" alt="" title="" /></a></div>'+infomsg;

	new Effect.Center($('infoWrap'));

	new Effect.Appear($('infoWrap'));
}

function hideInfo() {
	$('infoWrap').hide();

	hideLock();

	$('infoWrap').innerHTML = '';
	$('infoWrap').style.width = '';
	$('infoWrap').style.height = '';
}

function updateFlag(id_) {
	$('session_language_flag').src = 'media/image/flags/'+id_+'.png';
}

function updateKeymap(id_) {
	for (var i = 0; i < $('session_keymap').length; i++) {
		if ($('session_keymap')[i].value == id_)
			$('session_keymap')[i].selected = 'selected';
	}
}

switchsettings_lock = false;
function switchSettings() {
	if (switchsettings_lock)
		return;

	switchsettings_lock = true;
	setTimeout(function() {
		switchsettings_lock = false;
	}, 400);

	if ($('advanced_settings').visible()) {
		$('advanced_settings_status').innerHTML = '<img src="media/image/show.png" width="12" height="12" alt="" title="" />';
		new Effect.SlideUp($('advanced_settings'), { duration: 0.4 });
	} else {
		$('advanced_settings_status').innerHTML = '<img src="media/image/hide.png" width="12" height="12" alt="" title="" />';
		new Effect.SlideDown($('advanced_settings'), { duration: 0.4 });
	}
}

function setCaretPosition(ctrl, pos) {
	if(ctrl.setSelectionRange) {
		ctrl.focus();
		ctrl.setSelectionRange(pos, pos);
	} else if (ctrl.createTextRange) {
		var range = ctrl.createTextRange();
		range.collapse(true);
		range.moveEnd('character', pos);
		range.moveStart('character', pos);
		range.select();
	}
}

function checkLogin() {
	if ($('sessionmanager_url').value != '' && $('user_login').value != '')
		$('submitLogin').disabled = false;
	else
		$('submitLogin').disabled = true;
}
