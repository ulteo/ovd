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

var session_management = new uovd.SessionManagement();

var debug = false;

var session_mode = false;

var desktop_fullscreen = false;

var switchsettings_lock = false;

var running_apps = 0;

/* Load NiftyCorners */
NiftyLoad = function() {
	Nifty('div.rounded');
}

/* Bind events after DOM load */
Event.observe(window, 'load', function() {
	/* Center containers at startup */
	new Effect.Center(jQuery('#splashContainer')[0]);
	new Effect.Center(jQuery('#endContainer')[0]);
	new Effect.Center(jQuery('#iframeWrap')[0]);

	/* Keep containers centered after a window resize */
	Event.observe(window, 'resize', function() {
		new Effect.Center(jQuery('#splashContainer')[0]);
		new Effect.Center(jQuery('#endContainer')[0]);
		new Effect.Center(jQuery('#iframeWrap')[0]);
	});

	/* Hide panels */
	jQuery('#sessionContainer').hide();
	jQuery('#debugContainer').hide();
	jQuery('#debugLevels').hide();
	jQuery('#loginBox').hide();
	jQuery('#newsWrap').hide();
	jQuery('#fullScreenMessage').hide();

	/* Fade-in the login panel */
	setTimeout(function() {
		new Effect.Appear(jQuery('#loginBox')[0]);
	}, 1000);

	/* Translate string and select flag icon */
	applyTranslations(i18n_tmp);
	updateFlag(jQuery('#session_language').prop('value'));

	/* Update translation on language change */
	Event.observe(jQuery('#session_language')[0], 'change', function() {
		translateInterface(jQuery('#session_language').prop('value'));
		updateFlag(jQuery('#session_language').prop('value'));
	});
	Event.observe(jQuery('#session_language')[0], 'keyup', function() {
		translateInterface(jQuery('#session_language').prop('value'));
		updateFlag(jQuery('#session_language').prop('value'));
	});

	/* Auth form validation (mostly for special auth methods) */
	Event.observe(jQuery('#sessionmanager_host')[0],         'keyup',  function() { checkLogin(); });
	Event.observe(jQuery('#sessionmanager_host')[0],         'change', function() { checkLogin(); });
	Event.observe(jQuery('#user_login')[0],                  'change', function() { checkLogin(); });
	Event.observe(jQuery('#user_login')[0],                  'keyup',  function() { checkLogin(); });
	Event.observe(jQuery('#use_local_credentials_true')[0],  'change', function() { checkLogin(); });
	Event.observe(jQuery('#use_local_credentials_true')[0],  'click',  function() { checkLogin(); });
	Event.observe(jQuery('#use_local_credentials_false')[0], 'change', function() { checkLogin(); });
	Event.observe(jQuery('#use_local_credentials_false')[0], 'click',  function() { checkLogin(); });

	/* Session form validation */
	Event.observe(jQuery('#session_mode')[0], 'change', function() { checkSessionMode(); });
	Event.observe(jQuery('#session_mode')[0], 'click',  function() { checkSessionMode(); });

	/* Check now to handle settings set by cookies */
	checkSessionMode();

	/* Form submit using "Enter" key */
	Event.observe(jQuery('#user_login')[0], 'keydown', function(event) {
		if (typeof event == 'undefined' || event.keyCode != 13)
			return;
		jQuery('#startsession')[0].submit();
	});
	Event.observe(jQuery('#user_password')[0], 'keydown', function(event) {
		if (typeof event == 'undefined' || event.keyCode != 13)
			return;
		jQuery('#startsession')[0].submit();
	});

	/* Submit event */
	Event.observe(jQuery('#startsession')[0], 'submit', function() {
		startSession();
	});

	/* Open "advanced settings" tab */
	Event.observe(jQuery('#advanced_settings_gettext')[0], 'click', function() {
		switchSettings();
	});

	/* Suspend */
	Event.observe(jQuery('#suspend_link')[0], 'click', function() {
		session_management.suspend();
	});

	/* Logout confirmation for "Applications" mode */
	Event.observe(jQuery('#logout_link')[0], 'click', function() {
		confirmLogout(confirm_logout);
	});

	/* Hide IFrame */
	Event.observe(jQuery('#iframeLink')[0], 'click', function() {
		hideIFrame();
	});

	/* Hide News */
	Event.observe(jQuery('#newsHideLink')[0], 'click', function() {
		hideNews();
	});

	/* Lock Wrap */
	Event.observe(jQuery('#lockWrap')[0], 'click', function() {
		if (jQuery('#iframeWrap')[0].visible()) hideIFrame();
		if (jQuery('#newsWrap')[0] && jQuery('#newsWrap')[0].visible()) hideNews();
	});

	/* Set focus to the first text field */
	if(focus_sm_textfield) {
		if (jQuery('#sessionmanager_host')[0] && jQuery('#sessionmanager_host')[0].visible()) jQuery('#sessionmanager_host')[0].focus();
	} else if(focus_pw_textfield) {
		if (jQuery('#user_password')[0] && jQuery('#user_password')[0].visible()) jQuery('#user_password')[0].focus();
	} else {
		if (jQuery('#user_login')[0] && jQuery('#user_login')[0].visible()) jQuery('#user_login')[0].focus();
	}

	/* Configure the SM Host field behaviour */
	if (jQuery('#sessionmanager_host').prop('value') == '') {
		jQuery('#sessionmanager_host').css('color', 'grey');
		jQuery('#sessionmanager_host').prop('value', i18n.get('sessionmanager_host_example'));
		if (jQuery('#sessionmanager_host')[0] && jQuery('#sessionmanager_host')[0].visible())
			setCaretPosition(jQuery('#sessionmanager_host')[0], 0);
	}
	Event.observe(jQuery('#sessionmanager_host')[0], 'focus', function() {
		if (jQuery('#sessionmanager_host').prop('value') == i18n.get('sessionmanager_host_example')) {
			jQuery('#sessionmanager_host').css('color', 'black');
			jQuery('#sessionmanager_host').prop('value', '');
		}
	});
	Event.observe(jQuery('#sessionmanager_host')[0], 'blur', function() {
		if (jQuery('#sessionmanager_host').prop('value') == '') {
			jQuery('#sessionmanager_host').css('color', 'grey');
			jQuery('#sessionmanager_host').prop('value', i18n.get('sessionmanager_host_example'));
		}
	});
	window.updateSMHostField = function() {
		if (jQuery('#sessionmanager_host').css('color') != 'grey')
			return;
		jQuery('#sessionmanager_host').prop('value', i18n.get('sessionmanager_host_example'));
	}

	/* Configure the debug panel */
	if(debug_mode) {
		Event.observe(jQuery('#level_debug')[0],   'click', function() { Logger.toggle_level('debug'); });
		Event.observe(jQuery('#level_info')[0],    'click', function() { Logger.toggle_level('info'); });
		Event.observe(jQuery('#level_warning')[0], 'click', function() { Logger.toggle_level('warning'); });
		Event.observe(jQuery('#level_error')[0],   'click', function() { Logger.toggle_level('error'); });
		Event.observe(jQuery('#clear_button')[0],  'click', function() { Logger.clear(); });
		switchSettings();
	}

	/* handle status modifications */
	session_management.addCallback("ovd.session.starting", function(type, source, params) {
		synchronize();
		hideLogin();
		showSplash();
		pushMainContainer();

		/* Wait for the animation end then show outside of the viewport */
		setTimeout(function() {
			showMainContainer();
			enableLogin();
		}, 2000);
	});

	session_management.addCallback("ovd.session.started", function(type, source, params) {
		var mode = session_management.parameters["session_type"];
		configureUI(mode);
		pullMainContainer();
	});

	session_management.addCallback("ovd.session.destroying", function(type, source, params) {
		hideMainContainer();
	});

	session_management.addCallback("ovd.session.destroyed", function(type, source, params) {
		hideSplash();
		generateEnd_internal();
		showEnd();
	});

	/* handle errors */
	session_management.addCallback("ovd.session.error", function(type, source, params) {
		var code = params["code"];
		var from = params["from"];
		var message = params["message"];

		if(code == "bad_xml") {
			showError(i18n.get('internal_error'));
			enableLogin();
			return;
		}

		if(from == "start" || from == "session_status") { /* = xml 'response' || 'error' */
			var message = i18n.get(code) || i18n.get('internal_error');
			showError(message);
			enableLogin();
			return;
		}
  });

	var java_provider = new uovd.provider.Java();
	java_provider.set_applet_codebase("applet/");
	
	window.rdp_providers = {};
	window.rdp_providers["java"]  = java_provider;
	window.rdp_providers["html5"] = new uovd.provider.rdp.Html5();

	/* handle progress bar */
	new ProgressBar(session_management, '#progressBarContent');

	/* handle client insertion */
	new DesktopContainer(session_management, "#desktopContainer");

	/* applications launcher */
	new SeamlessLauncher(session_management, "#appsContainer");

	/* window manager */
	new SeamlessWindowManager(session_management, "#windowsContainer", new uovd.provider.rdp.html5.SeamlessWindowFactory());

	/* ajaxplorer file manager */
	new Ajaxplorer(session_management, "#fileManagerContainer");

	/* Session-based start_app support */
	new StartApp(session_management);

	/* application counter */
	window.applicationCounter = new ApplicationCounter(session_management);
});

function startSession() {
	if (jQuery('#errorWrap')[0].visible()) {
		hideError();
		return false;
	}

	disableLogin();

	debug = false;
	if (jQuery('#debug_true')[0] && jQuery('#debug_true').prop('checked'))
		debug = true;

	desktop_fullscreen = false;
	if (jQuery('#desktop_fullscreen_true')[0] && jQuery('#desktop_fullscreen_true').prop('checked'))
		desktop_fullscreen = true;

	var use_local_credentials = false;
	if (jQuery('#use_local_credentials_true')[0] && jQuery('#use_local_credentials_true').prop('checked'))
		use_local_credentials = true;

	var parameters = {};
	parameters["session_manager"] = jQuery('#sessionmanager_host').prop('value');
	parameters["username"] = jQuery('#user_login').prop('value');
	parameters["password"] = jQuery('#user_password').prop('value');
	parameters["session_type"] = jQuery('#session_mode').prop('value');
	parameters["language"] = jQuery('#session_language').prop('value');
	parameters["keymap"] = jQuery('#session_keymap').prop('value');
	parameters["rdp_input_method"] = window.rdp_input_method;
	parameters["timezone"] = getTimezoneName();
	parameters["width"] = window.innerWidth;
	parameters["height"] = window.innerHeight;
	parameters["fullscreen"] = desktop_fullscreen;
	parameters["debug"] = debug;
	parameters["use_local_credentials"] = use_local_credentials;
	parameters["local_integration"] = window.local_integration;

	if(parameters["session_manager"] == "127.0.0.1") {
		parameters["session_manager"] = location.hostname;
	}

	switch(jQuery('#rdp_mode').prop('value')) {
		case "html5" :
			session_management.setRdpProvider(window.rdp_providers["html5"]);
			break;
		default :
			session_management.setRdpProvider(window.rdp_providers["java"]);
			break;
	}

	session_management.setParameters(parameters);
	session_management.setAjaxProvider(new uovd.provider.http.Proxy("proxy.php"));
	session_management.start();

	return false;
}

/// Parses login.php XML answer
function onStartSessionSuccess(xml_) {
	var xml = xml_;


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
	
	var sessionmanager = {'port': 443};  // Default SM & Gateway port
	if (GATEWAY_FIRST_MODE) {
		sessionmanager.host = window.location.hostname;
		if (window.location.port !=  '')
			sessionmanager.port = window.location.port; 
	}
	else {
		var buf = jQuery('#sessionmanager_host').prop('value');
		var sep = buf.lastIndexOf(":");
		if (sep == -1)
			sessionmanager.host = buf;
		else {
			sessionmanager.host = buf.substring(0, sep);
			sessionmanager.port = buf.substring(sep+1, buf.length);
		}
	}
	
	try {
		session_mode = session_node.getAttribute('mode');
		session_mode = session_mode.substr(0, 1).toUpperCase()+session_mode.substr(1, session_mode.length-1);
	} catch(e) {}

	var buffer = xml.getElementsByTagName('setting');
	for (var i = 0; i < buffer.length; i++) {
		try {
			if (buffer[i].getAttribute('name') == 'user_displayname')
				jQuery('#user_displayname').html(buffer[i].getAttribute('value'));
		} catch(e) {}
	}

	jQuery('#user_password').prop('value', '');

	var explorer = false;
	var buffer = xml.getElementsByTagName('explorer');
	if (buffer.length == 1)
		explorer = true;

	hideLogin();
	showSplash();

	new Effect.Move(jQuery('#sessionContainer')[0], { x: 0, y: -my_height, mode: 'absolute' });
	setTimeout(function() {
		jQuery('#sessionContainer').show();
	}, 2000);

	if (session_mode == 'Desktop')
		daemon = new Desktop(debug);
	else
		daemon = new Portal(debug);

	daemon.sessionmanager = sessionmanager;

	daemon.explorer = explorer;
	if (daemon.explorer)
		jQuery('#fileManagerWrap').show();

	daemon.keymap = jQuery('#session_keymap').prop('value');
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
		var mode = session_management.parameters["session_type"];
		showMainContainer();
	});

	// <server> nodes
	if (! daemon.parse_list_servers(xml)) {
		showInternalError();
		return false;
	}

	daemon.prepare();

	setTimeout(function() {

		if (debug) {
			jQuery('#sessionContainer').height(daemon.my_height);
		}
		
		daemon.loop();
	}, 2500);

	setTimeout(function() {
		enableLogin();
	}, 5000);

	return true;
}

function onStartSessionFailure() {
	showInternalError();
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
		showInternalError();
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
	jQuery.ajax({
			url: 'synchronize.php',
			type: 'POST',
			async: false,
			headers: {
				"Forward-Cookie" : cookie_
			},
			data: {
				sessionmanager_host: jQuery('#sessionmanager_host').prop('value'),
				mode: jQuery('#session_mode').prop('value'),
				language: jQuery('#session_language').prop('value'),
				keymap: jQuery('#session_keymap').prop('value'),
				timezone: getTimezoneName(),
				debug: ((debug)?1:0)
			}
		}
	);
}

function on_java_test_finished(test) {
	checkLogin();
	manageKeymap();
}

function updateFlag(id_) {
	if (!big_image_map) {
		jQuery('#session_language_flag').attr('src', 'media/image/flags/'+id_+'.png');
	} else {
		jQuery('#session_language_flag').addClass('image_'+id_+'_png');
	}
}

function updateKeymap(id_) {
	if (id_ == null)
		return false;
	
	if (! jQuery('#session_keymap')[0])
		return false;
	
	for (var i = 0; i < jQuery('#session_keymap')[0].length; i++) {
		if (jQuery('#session_keymap')[0][i].value == id_) {
			jQuery('#session_keymap')[0][i].selected = 'selected';
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

	if (jQuery('#advanced_settings')[0].visible()) {
		if (!big_image_map) {
			jQuery('#advanced_settings_status').html('<img src="media/image/show.png" width="12" height="12" alt="" title="" />');
		} else {
			jQuery('#advanced_settings_status').removeClass('image_hide_png').addClass('image_show_png');
		}
		new Effect.SlideUp(jQuery('#advanced_settings')[0], { duration: 0.4 });
	} else {
		if (!big_image_map) {
			jQuery('#advanced_settings_status').html('<img src="media/image/hide.png" width="12" height="12" alt="" title="" />');
		} else {
			jQuery('#advanced_settings_status').removeClass('image_show_png').addClass('image_hide_png');
		}
		new Effect.SlideDown(jQuery('#advanced_settings')[0], { duration: 0.4 });
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
	if (! jQuery('#user_login')[0] || ! jQuery('#user_login_local')[0] || ! jQuery('#password_row')[0])
		return;

	if (jQuery('#use_local_credentials_true')[0] && jQuery('#use_local_credentials_true').prop('checked')) {
		jQuery('#user_login_local').html(jQuery('#CheckSignedJava')[0].getUserLogin());
		if (jQuery('#user_password')[0])
			jQuery('#user_password').prop('disabled', 'true');
		
		jQuery('#user_login').hide();
		jQuery('#user_login_local').show();
		jQuery('#password_row').hide();
	} else {
		if (jQuery('#user_password')[0])
			jQuery('#user_password').removeProp('disabled');
		
		jQuery('#user_login').show();
		jQuery('#user_login_local').hide();
		jQuery('#password_row').show();
	}

	if (jQuery('#sessionmanager_host').prop('value') != '' && jQuery('#sessionmanager_host').prop('value') != i18n.get('sessionmanager_host_example') && (jQuery('#user_login').prop('value') != '' || (jQuery('#use_local_credentials_true')[0] && jQuery('#use_local_credentials_true').prop('checked'))))
		jQuery('#connect_gettext').removeProp('disabled');
	else
		jQuery('#connect_gettext').prop('disabled', 'true');
}

function manageKeymap() {
	var keymapSet = true;
	if (OPTION_KEYMAP_AUTO_DETECT == true)
		keymapSet = false;
	
	if (! keymapSet) {
		var detected = null;
		try {
			detected = jQuery('#CheckSignedJava')[0].getDetectedKeyboardLayout();
		} catch(e) {}
		
		keymapSet = updateKeymap(detected);
		if (! keymapSet) {
			if (! jQuery('#session_language')[0])
				return false;
			
			detected = jQuery('#session_language').prop('value');
			
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
	if (jQuery('#session_mode').prop('value') == 'desktop') {
		if (jQuery('#advanced_settings_applications')[0])
			jQuery('#advanced_settings_applications').hide();
		if (jQuery('#advanced_settings_desktop')[0])
			jQuery('#advanced_settings_desktop').show();
	} else if (jQuery('#session_mode').prop('value') == 'applications') {
		if (jQuery('#advanced_settings_desktop')[0])
			jQuery('#advanced_settings_desktop').hide();
		if (jQuery('#advanced_settings_applications')[0])
			jQuery('#advanced_settings_applications').show();
	}
}

function confirmLogout(confirm_) {
	var running_apps = window.applicationCounter.get();
	if (confirm_ == 'always' || (confirm_ == 'apps_only' && running_apps > 0))
		if (!confirm(i18n.get('want_logout').replace('#', running_apps)))
			return;
	
	session_management.stop();
}

function synchronize() {
	var parameters = {};
	parameters["login"] = jQuery('#user_login').prop('value');
	parameters["sessionmanager_host"] = jQuery('#sessionmanager_host').prop('value');
	parameters["mode"] = jQuery('#session_mode').prop('value');
	parameters["type"] = jQuery('#rdp_mode').prop('value');
	parameters["language"] = jQuery('#session_language').prop('value');
	parameters["keymap"] = jQuery('#session_keymap').prop('value');
	parameters["debug"] = debug;

	parameters["desktop_fullscreen"] = false;
	if (jQuery('#desktop_fullscreen_true')[0] && jQuery('#desktop_fullscreen_true').prop('checked'))
		parameters["desktop_fullscreen"] = true;

	parameters["use_local_credentials"] = false;
	if (jQuery('#use_local_credentials_true')[0] && jQuery('#use_local_credentials_true').prop('checked'))
		parameters["use_local_credentials"] = true;

	jQuery.ajax({
		url: "synchronize.php",
		type: "POST",
		data: parameters
	});
}
