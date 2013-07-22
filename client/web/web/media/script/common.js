/**
 * Copyright (C) 2010-2011 Ulteo SAS
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

var debug = false;
var explorer = false;

var startsession = false;

var session_mode = false;

var desktop_fullscreen = false;

function startSession() {
	disableLogin();

	explorer = false;

	startsession = false;

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

	if (! startsession)
		return false;

	return false;
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
	if ($('endContainer').visible())
		return;

	if ($('loginBox') && $('loginBox').visible())
		return;

	new Effect.Appear($('endContainer'));
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

	var buffer = xml.getElementsByTagName('session');
	if (buffer.length != 1) {
		enableLogin();
		return false;
	}
	session_node = buffer[0];

	var sessionmanager_host = session_node.getAttribute('sessionmanager');
	if (sessionmanager_host == '127.0.0.1' || sessionmanager_host == '127.0.1.1' || sessionmanager_host == 'localhost' || sessionmanager_host == 'localhost.localdomain')
		sessionmanager_host = window.location.hostname;
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

	var buffer = xml.getElementsByTagName('explorer');
	if (buffer.length == 1)
		explorer = true;

	startsession = true;

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

	setTimeout(function() {
		if (session_mode == 'Desktop')
			daemon = new Desktop('ulteo-applet.jar', 'org.ulteo.ovd.applet.Desktop', debug);
		else
			daemon = new Applications('ulteo-applet.jar', 'org.ulteo.ovd.applet.Applications', debug);

		daemon.sessionmanager = sessionmanager_host;

		daemon.explorer = explorer;
		if (daemon.explorer)
			$('fileManagerWrap').show();

		daemon.keymap = $('session_keymap').value;
		try {
			daemon.duration = parseInt(session_node.getAttribute('duration'));
		} catch(e) {}
		daemon.duration = parseInt(session_node.getAttribute('duration'));

		if (session_mode == 'Desktop' && desktop_fullscreen)
			daemon.fullscreen = true;

		var settings_node = session_node.getElementsByTagName('settings');
		if (settings_node.length > 0) {
			var setting_nodes = settings_node[0].getElementsByTagName('setting');
			daemon.parseSessionSettings(setting_nodes);
		}

		daemon.i18n['session_close_unexpected'] = i18n.get('session_close_unexpected');
		daemon.i18n['session_end_ok'] = i18n.get('session_end_ok');
		daemon.i18n['session_end_unexpected'] = i18n.get('session_end_unexpected');
		daemon.i18n['error_details'] = i18n.get('error_details');
		daemon.i18n['close_this_window'] = i18n.get('close_this_window');
		daemon.i18n['start_another_session'] = i18n.get('start_another_session');

		daemon.i18n['suspend'] = i18n.get('suspend');
		daemon.i18n['resume'] = i18n.get('resume');

		if (debug) {
			if (session_mode == 'Desktop')
				$('desktopModeContainer').style.height = daemon.my_height+'px';
			else
				$('applicationsModeContainer').style.height = daemon.my_height+'px';
		}

		daemon.prepare();
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

	startsession = false;

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

	synchronize(data_, sm_session_cookie);

	onStartSessionSuccess(xml);
}

function synchronize(data_, cookie_) {
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
				debug: ((debug)?1:0),
				xml: data_
			},
			requestHeaders: new Array('Forward-Cookie', cookie_)
		}
	);
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

	if ($('loginBox'))
		$('loginBox').hide();

	$('lockWrap').hide();
	$('lockWrap').style.width = my_width+'px';
	$('lockWrap').style.height = my_height+'px';

	$('errorWrap').hide();
	$('okWrap').hide();
	$('infoWrap').hide();

	if ($('newsWrap'))
		$('newsWrap').hide();

	Event.observe($('lockWrap'), 'click', function() {
		if ($('errorWrap').visible())
			hideError();

		if ($('okWrap').visible())
			hideOk();

		if ($('infoWrap').visible())
			hideInfo();

		if ($('newsWrap')) {
			if ($('newsWrap').visible())
				hideNews();
		}
	});

	testJava();

	if ($('loginBox')) {
		setTimeout(function() {
			new Effect.Appear($('loginBox'));
		}, 1000);
	}

	if ($('lockWrap')) {
		Event.observe(window, 'resize', function() {
			if ($('lockWrap').visible()) {
				refresh_body_size();

				$('lockWrap').style.width = my_width+'px';
				$('lockWrap').style.height = my_height+'px';
			}
		});
	}

	if ($('desktopAppletContainer')) {
		Event.observe(window, 'resize', function() {
			if ($('desktopAppletContainer').visible()) {
				new Effect.Center($('desktopAppletContainer'));

				if (daemon.debug)
					new Effect.Move($('desktopAppletContainer'), { x: 0, y: -75, duration: 0.01 });
			}
		});
	}
});

function showSystemTest() {
	showLock();

	new Effect.Center($('systemTestWrap'));
	var elementDimensions = Element.getDimensions($('systemTestWrap'));
	$('systemTestWrap').style.width = elementDimensions.width+'px';

	Event.observe(window, 'resize', function() {
		if ($('systemTestWrap').visible())
			new Effect.Center($('systemTestWrap'));
	});

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
	var elementDimensions = Element.getDimensions($('systemTestErrorWrap'));
	$('systemTestErrorWrap').style.width = elementDimensions.width+'px';

	Event.observe(window, 'resize', function() {
		if ($('systemTestErrorWrap').visible())
			new Effect.Center($('systemTestErrorWrap'));
	});

	new Effect.Appear($('systemTestErrorWrap'));
}

function testJava() {
	showSystemTest();

	var applet_params = new Hash();
	applet_params.set('onSuccess', 'appletSuccess');
	applet_params.set('onFailure', 'appletFailure');

	var applet = buildAppletNode('CheckSignedJava', 'org.ulteo.ovd.applet.CheckJava', 'ulteo-applet.jar', applet_params);
	$('testJava').appendChild(applet);
	testUlteoApplet();
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
			afterAppletTests();
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

function afterAppletTests() {
	checkLogin();
	manageKeymap();
	hideSystemTest();
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
	if ($('lockWrap').visible() && (! $('errorWrap').visible() && ! $('okWrap').visible() && ! $('infoWrap').visible())) {
		if ($('user_password') && $('user_password').visible() && $('user_password').disabled == false) {
			$('user_password').value = '';
		}
		$('lockWrap').hide();
	}
}

function showError(errormsg) {
	hideError();

	hideOk();
	hideInfo();

	showLock();

	$('errorWrap').innerHTML = '<div style="width: 16px; height: 16px; float: right;"><a href="javascript:;" onclick="hideError(); return false;"><img src="media/image/cross.png" width="16" height="16" alt="" title="" /></a></div>'+errormsg;
	$('errorWrap').style.padding = '10px';

	new Effect.Center($('errorWrap'));
	var elementDimensions = Element.getDimensions($('errorWrap'));
	$('errorWrap').style.width = elementDimensions.width+'px';

	new Effect.Appear($('errorWrap'));

	Nifty('div#errorWrap');
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

	$('okWrap').innerHTML = '<div style="width: 16px; height: 16px; float: right;"><a href="javascript:;" onclick="hideOk(); return false;"><img src="media/image/cross.png" width="16" height="16" alt="" title="" /></a></div>'+okmsg;
	$('okWrap').style.padding = '10px';

	new Effect.Center($('okWrap'));
	var elementDimensions = Element.getDimensions($('okWrap'));
	$('okWrap').style.width = elementDimensions.width+'px';

	new Effect.Appear($('okWrap'));

	Nifty('div#okWrap');

	setTimeout(function() {
		hideOk();
	}, 5000);
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

	$('infoWrap').innerHTML = '<div style="width: 16px; height: 16px; float: right;"><a href="javascript:;" onclick="hideInfo(); return false;"><img src="media/image/cross.png" width="16" height="16" alt="" title="" /></a></div>'+infomsg;
	$('infoWrap').style.padding = '10px';

	new Effect.Center($('infoWrap'));
	var elementDimensions = Element.getDimensions($('infoWrap'));
	$('infoWrap').style.width = elementDimensions.width+'px';

	new Effect.Appear($('infoWrap'));

	Nifty('div#infoWrap');
}

function hideInfo() {
	$('infoWrap').hide();

	hideLock();

	$('infoWrap').innerHTML = '';
	$('infoWrap').style.width = '';
	$('infoWrap').style.height = '';
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

function translateInterface(lang_) {
	new Ajax.Request(
		'translate.php',
		{
			method: 'post',
			parameters: {
				lang: lang_
			},
			onSuccess: function(transport) {
				var xml = transport.responseXML;
				if (xml == null)
					return;

				var translations = xml.getElementsByTagName('translation');
				for (var i = 0; i < translations.length; i++) {
					var obj = $(translations[i].getAttribute('id')+'_gettext');
					if (! obj)
						continue;

					if (obj.nodeName.toLowerCase() == 'input')
						obj.value = translations[i].getAttribute('string');
					else
						obj.innerHTML = translations[i].getAttribute('string');
				}

				var js_translations = xml.getElementsByTagName('js_translation');
				for (var i = 0; i < js_translations.length; i++)
					i18n.set(js_translations[i].getAttribute('id'), js_translations[i].getAttribute('string'));
				
				if (typeof window.updateSMHostField == 'function')
					updateSMHostField();
			}
		}
	);
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

function buildAppletNode(name, code, archive, extra_params) {
	var applet_node = document.createElement('applet');
	applet_node.setAttribute('id', name);
	applet_node.setAttribute('width', '1');
	applet_node.setAttribute('height', '1');
	applet_node.setAttribute('style', 'position: absolute; top: 0px; left: 0px;');

	var params = new Hash();
	params.set('name', name);
	params.set('code', code);
	params.set('codebase', 'applet/');
	params.set('archive', archive);
	params.set('cache_archive', archive);
	params.set('cache_archive_ex', archive+';preload');
	params.set('mayscript', 'true');

	var keys;
	var i;

	keys = params.keys();
	for (i=0; i<keys.length; i++) {
		var key = keys[i];
		var value = params.get(key);

		var param_node = document.createElement('param');
		param_node.setAttribute('name', key);
		param_node.setAttribute('value', value);
		applet_node.appendChild(param_node);
		applet_node.setAttribute(key, value);
	}

	keys = extra_params.keys();
	for (i=0; i<keys.length; i++) {
		var key = keys[i];
		var value = extra_params.get(key);

		var param_node = document.createElement('param');
		param_node.setAttribute('name', key);
		param_node.setAttribute('value', value);
		applet_node.appendChild(param_node);
	}

	return applet_node;
}

function startExternalSession(mode_) {
	new Ajax.Request(
		'login.php',
		{
			method: 'post',
			parameters: {
				mode: mode_,
				language: client_language,
				keymap: client_keymap,
				timezone: getTimezoneName(),
				debug: 0
			},
			onSuccess: function(transport) {
				onStartExternalSessionSuccess(transport.responseXML);
			},
			onFailure: function() {
				onStartExternalSessionFailure();
			}
		}
	);

	return false;
}

function onStartExternalSessionSuccess(xml_) {
	var xml = xml_;

	var buffer = xml.getElementsByTagName('response');
	if (buffer.length == 1) {
		try {
			showError(i18n.get(buffer[0].getAttribute('code')));
		} catch(e) {}
		return false;
	}

	var buffer = xml.getElementsByTagName('error');
	if (buffer.length == 1) {
		try {
			if (typeof i18n.get(buffer[0].getAttribute('error_id')) != 'undefined')
				showError(i18n.get(buffer[0].getAttribute('error_id')));
			else
				showError(i18n.get('internal_error'));
		} catch(e) {}
		return false;
	}

	var buffer = xml.getElementsByTagName('session');
	if (buffer.length != 1)
		return false;
	session_node = buffer[0];

	try {
		session_mode = session_node.getAttribute('mode');
		session_mode = session_mode.substr(0, 1).toUpperCase()+session_mode.substr(1, session_mode.length-1);
	} catch(e) {}

	startsession = true;

	setTimeout(function() {
		if (session_mode == 'Desktop')
			daemon = new Desktop('ulteo-applet.jar', 'org.ulteo.ovd.applet.Desktop', false);
		else
			daemon = new External('ulteo-applet.jar', 'org.ulteo.ovd.applet.Applications', false);

		daemon.keymap = client_keymap;
		try {
			daemon.duration = parseInt(session_node.getAttribute('duration'));
		} catch(e) {}
		daemon.duration = parseInt(session_node.getAttribute('duration'));
		daemon.multimedia = ((session_node.getAttribute('multimedia') == 1)?true:false);
		daemon.redirect_client_printers = ((session_node.getAttribute('redirect_client_printers') == 1)?true:false);
		try {
			daemon.redirect_client_drives = session_node.getAttribute('redirect_client_drives');
		} catch(e) {}

		var settings_node = session_node.getElementsByTagName('settings');
		if (settings_node.length > 0) {
			var setting_nodes = settings_node[0].getElementsByTagName('setting');
			daemon.parseSessionSettings(setting_nodes);
		}

		daemon.i18n['session_close_unexpected'] = i18n.get('session_close_unexpected');
		daemon.i18n['session_end_ok'] = i18n.get('session_end_ok');
		daemon.i18n['session_end_unexpected'] = i18n.get('session_end_unexpected');
		daemon.i18n['error_details'] = i18n.get('error_details');
		daemon.i18n['close_this_window'] = i18n.get('close_this_window');
		daemon.i18n['start_another_session'] = i18n.get('start_another_session');

		daemon.i18n['suspend'] = i18n.get('suspend');
		daemon.i18n['resume'] = i18n.get('resume');

		daemon.prepare();
		daemon.loop();
	}, 2500);

	return true;
}

function onStartExternalSessionFailure() {
	showError(i18n.get('internal_error'));

	startsession = false;

	return false;
}

function getWebClientBaseURL() {
	var url = window.location.href;
	return url.replace(/\/[^\/]*$/, "")+"/";
}
