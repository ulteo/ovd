/**
 * Copyright (C) 2012 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2012
 * Author David PHAM-VAN <d.pham-van@ulteo.com> 2012
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

var debug = false;

var session_mode = false;

var desktop_fullscreen = false;

var switchsettings_lock = false;

Event.observe(window, 'load', function() {
	$('loginBox').hide();

	$('newsWrap').hide();

	Event.observe($('lockWrap'), 'click', function() {
		if ($('iframeWrap').visible())
			hideIFrame();

		if ($('newsWrap')) {
			if ($('newsWrap').visible())
				hideNews();
		}
	});

	var test = new JavaTester();
	test.add_finished_callback(on_java_test_finished);
	test.perform();

	setTimeout(function() {
		new Effect.Appear($('loginBox'));
	}, 1000);
	
	new Effect.Center($('iframeWrap'));
});

function startSession() {
	if ($('errorWrap').visible()) {
		hideError();
		return false;
	}

	disableLogin();

	debug = false;
	if ($('debug_true') && $('debug_true').checked)
		debug = true;

	session_mode = $('session_mode').value;
	session_mode = session_mode.substr(0, 1).toUpperCase()+session_mode.substr(1, session_mode.length-1);

	desktop_fullscreen = false;
	if ($('desktop_fullscreen_true') && $('desktop_fullscreen_true').checked)
		desktop_fullscreen = true;

	if (! $('use_local_credentials_true') || ! $('use_local_credentials_true').checked) {
		new Ajax.Request(
			'login.php',
			{
				method: 'post',
				parameters: {
					requested_host: window.location.hostname,
					requested_port: ((window.location.port !=  '')?window.location.port:'443'),
					sessionmanager_host: $('sessionmanager_host').value,
					login: $('user_login').value,
					password: $('user_password').value,
					mode: $('session_mode').value,
					language: $('session_language').value,
					keymap: $('session_keymap').value,
					timezone: getTimezoneName(),
					desktop_fullscreen: ((desktop_fullscreen)?1:0),
					debug: ((debug)?1:0)
				},
				onSuccess: function(transport) {
					onStartSessionSuccess(transport.responseXML);
				},
				onFailure: function() {
					onStartSessionFailure();
				}
			}
		);
	} else {
		$('CheckSignedJava').ajaxRequest($('sessionmanager_host').value, $('session_mode').value, $('session_language').value, getTimezoneName(), 'onStartSessionJavaRequest');
		return false;
	}

	return false;
}

function hideLogin() {
	new Effect.Move($('loginBox'), { x: 0, y: -1000 });
	setTimeout(function() {
		$('loginBox').hide();
	}, 1000);
}

function showLogin() {
	$('loginBox').show();
	new Effect.Move($('loginBox'), { x: 0, y: 1000 });

	if (debug) {
		Logger.del_instance();
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

/// Parses login.php XML answer
function onStartSessionSuccess(xml_) {
	var xml = xml_;

	var buffer = xml.getElementsByTagName('response');
	if (buffer.length == 1) {
		try {
			showError(i18n.get(buffer[0].getAttribute('code')));
		} catch(e) {}
		enableLogin();
		return false;
	}

	// Response Error handling
	var buffer = xml.getElementsByTagName('error');
	if (buffer.length == 1) {
		try {
			if (typeof i18n.get(buffer[0].getAttribute('error_id')) != 'undefined') {
				var errormsg = i18n.get(buffer[0].getAttribute('error_id'));
				try {
					var errormore = buffer[0].getAttribute('more');
					if (errormore != null)
						errormsg += ' ('+errormore+')';
				} catch(e) {}
				showError(errormsg);
			} else
				showError(i18n.get('internal_error'));
		} catch(e) {}
		enableLogin();
		return false;
	}

	var buffer = xml.getElementsByTagName('popup');
	if (buffer.length == 1) {
		try {
			var url = buffer[0].getAttribute('location');
			showIFrame(url);
		} catch(e) {}
		enableLogin();
		return false;
	}

	// No <session> tag is not an invalid answer
	var buffer = xml.getElementsByTagName('session');
	if (buffer.length != 1) {
		enableLogin();
		return false;
	}
	session_node = buffer[0];

	var sessionmanager_host = session_node.getAttribute('sessionmanager');
	if (sessionmanager_host == '127.0.0.1' || sessionmanager_host == '127.0.1.1' || sessionmanager_host == 'localhost' || sessionmanager_host == 'localhost.localdomain')
		sessionmanager_host = window.location.hostname;

  // HTTPS detection (ugly)
	if (sessionmanager_host.indexOf(':') == -1)
		sessionmanager_host += ':443';

	try {
		session_mode = session_node.getAttribute('mode');
		session_mode = session_mode.substr(0, 1).toUpperCase()+session_mode.substr(1, session_mode.length-1);
	} catch(e) {}

	var buffer = xml.getElementsByTagName('setting');
	for (var i = 0; i < buffer.length; i++) {
		try {
			if (buffer[i].getAttribute('name') == 'user_displayname')
				$('user_displayname').innerHTML = buffer[i].getAttribute('value');
		} catch(e) {}
	}

	$('user_password').value = '';

	var explorer = false;
	var buffer = xml.getElementsByTagName('explorer');
	if (buffer.length == 1)
		explorer = true;

	hideLogin();
	showSplash();

	if (session_mode == 'Desktop') {
		new Effect.Move($('desktopModeContainer'), { x: 0, y: -my_height, mode: 'absolute' });
		setTimeout(function() {
			$('desktopModeContainer').show();
		}, 2000);
	} else {
		new Effect.Move($('applicationsModeContainer'), { x: 0, y: -my_height, mode: 'absolute' });
		setTimeout(function() {
			$('applicationsModeContainer').show();
		}, 2000);
	}

	if (session_mode == 'Desktop')
		daemon = new Desktop(debug);
	else
		daemon = new Portal(debug);

	daemon.sessionmanager = sessionmanager_host;

	daemon.explorer = explorer;
	if (daemon.explorer)
		$('fileManagerWrap').show();

	daemon.keymap = $('session_keymap').value;
	try {
		var duration = parseInt(session_node.getAttribute('duration'));
		if (! isNaN(duration))
			daemon.duration = duration;
	} catch(e) {}

	if (session_mode == 'Desktop' && desktop_fullscreen)
		daemon.fullscreen = true;

	var settings_node = session_node.getElementsByTagName('settings');
	if (settings_node.length > 0) {
		var setting_nodes = settings_node[0].getElementsByTagName('setting');
		daemon.parseSessionSettings(setting_nodes);
	}

	daemon.add_session_ready_callback(function onSessionReady(d_) {
		if (session_mode == 'Desktop')
			new Effect.Move($('desktopModeContainer'), { x: 0, y: my_height });
		else
			new Effect.Move($('applicationsModeContainer'), { x: 0, y: my_height });
		
		setTimeout(function() {
			hideSplash();
		}, 2000);
	});

	daemon.prepare();

	// <server> nodes
	if (! daemon.parse_list_servers(xml)) {
		try {
			showError(i18n.get('internal_error'));
		} catch(e) {}
		
		enableLogin();
		return false;
	}

	setTimeout(function() {

		if (debug) {
			if (session_mode == 'Desktop')
				$('desktopModeContainer').style.height = daemon.my_height+'px';
			else
				$('applicationsModeContainer').style.height = daemon.my_height+'px';
		}
		
		daemon.loop();
	}, 2500);

	setTimeout(function() {
		enableLogin();
	}, 5000);

	return true;
}

function onStartSessionFailure() {
	showError(i18n.get('internal_error'));

	enableLogin();

	return false;
}

function onStartSessionJavaRequest(http_code_, content_type_, data_, cookies_) {
	if (http_code_ != 200) {
		onStartSessionFailure();
		return false;
	}

	try {
		if (window.DOMParser) {
			parser = new DOMParser();
			xml = parser.parseFromString(data_, 'text/xml');
		} else { // Internet Explorer
			xml = new ActiveXObject('Microsoft.XMLDOM');
			xml.async = 'false';
			xml.loadXML(data_);
		}
	} catch(e) {
		showError(i18n.get('internal_error'));
		enableLogin();
		return false;
	}

	try {
		var sm_session_cookie;
		for (var i = 0; i < cookies_.length; i++)
			sm_session_cookie = cookies_[i];
	} catch(e) {}

	synchronize(sm_session_cookie);

	onStartSessionSuccess(xml);
}

function synchronize(cookie_) {
	new Ajax.Request(
		'synchronize.php',
		{
			method: 'post',
			asynchronous: false,
			parameters: {
				sessionmanager_host: $('sessionmanager_host').value,
				mode: $('session_mode').value,
				language: $('session_language').value,
				keymap: $('session_keymap').value,
				timezone: getTimezoneName(),
				debug: ((debug)?1:0)
			},
			requestHeaders: new Array('Forward-Cookie', cookie_)
		}
	);
}

function on_java_test_finished(test) {
	checkLogin();
	manageKeymap();
}

function showNews(title_, content_) {
	hideNews();

	hideInfo();
	hideError();
	hideOk();

	showLock();

	$('newsWrap_title').innerHTML = title_;
	refresh_body_size();
	var reg = new RegExp("\n", "g");
	$('newsWrap_content').innerHTML = '<div style="width: 100%; height: '+parseInt(my_height*(75/100))+'px; overflow: auto;">'+content_.replace(reg, '<br />')+'</div>';

	new Effect.Center($('newsWrap'));

	new Effect.Appear($('newsWrap'));
}

function hideNews() {
	$('newsWrap').hide();

	hideLock();

	$('newsWrap_title').innerHTML = '';
	$('newsWrap_content').innerHTML = '';
	$('newsWrap').style.width = '750px';
	$('newsWrap').style.height = '';
}

function showIFrame(url_) {
	showLock();

	$('iframeContainer').src = url_;

	new Effect.Appear($('iframeWrap'));
}

function hideIFrame() {
	$('iframeWrap').hide();

	$('iframeContainer').src = 'about:blank';

	hideLock();
}

function updateFlag(id_) {
	$('session_language_flag').src = 'media/image/flags/'+id_+'.png';
}

function updateKeymap(id_) {
	if (id_ == null)
		return false;
	
	if (! $('session_keymap'))
		return false;
	
	for (var i = 0; i < $('session_keymap').length; i++) {
		if ($('session_keymap')[i].value == id_) {
			$('session_keymap')[i].selected = 'selected';
			return true;
		}
	}
	
	return false;
}


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
	if (! $('user_login') || ! $('user_login_local') || ! $('password_row'))
		return;

	if ($('use_local_credentials_true') && $('use_local_credentials_true').checked) {
		$('user_login_local').innerHTML = $('CheckSignedJava').getUserLogin();
		if ($('user_password'))
			$('user_password').disabled = true;
		
		$('user_login').hide();
		$('user_login_local').show();
		$('password_row').hide();
	} else {
		if ($('user_password'))
			$('user_password').disabled = false;
		
		$('user_login').show();
		$('user_login_local').hide();
		$('password_row').show();
	}

	if ($('sessionmanager_host').value != '' && $('sessionmanager_host').value != i18n.get('sessionmanager_host_example') && ($('user_login').value != '' || ($('use_local_credentials_true') && $('use_local_credentials_true').checked)))
		$('connect_gettext').disabled = false;
	else
		$('connect_gettext').disabled = true;
}

function manageKeymap() {
	var keymapSet = true;
	if (OPTION_KEYMAP_AUTO_DETECT == true)
		keymapSet = false;
	
	if (! keymapSet) {
		var detected = null;
		try {
			detected = $('CheckSignedJava').getDetectedKeyboardLayout();
		} catch(e) {}
		
		keymapSet = updateKeymap(detected);
		if (! keymapSet) {
			if (! $('session_language'))
				return false;
			
			detected = $('session_language').value;
			
			keymapSet = updateKeymap(detected);
			if (! keymapSet) {
				detected = detected.substr(0, 2);
				
				keymapSet = updateKeymap(detected);
				if (! keymapSet)
					return false;
			}
		}
	}
	
	return true;
}

function checkSessionMode() {
	if ($('session_mode').value == 'desktop') {
		if ($('advanced_settings_applications'))
			$('advanced_settings_applications').hide();
		if ($('advanced_settings_desktop'))
			$('advanced_settings_desktop').show();
	} else if ($('session_mode').value == 'applications') {
		if ($('advanced_settings_desktop'))
			$('advanced_settings_desktop').hide();
		if ($('advanced_settings_applications'))
			$('advanced_settings_applications').show();
	}
}

function confirmLogout(confirm_) {
	var nb_apps_ = daemon.nb_running_apps();
	if (confirm_ == 'always' || (confirm_ == 'apps_only' && nb_apps_ > 0)) {
		if (!confirm(i18n.get('want_logout').replace('#', nb_apps_)))
			return false;
	}
	
	daemon.logout();
	return false;
}
