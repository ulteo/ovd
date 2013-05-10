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

/* Global ovd daemon instance */
var daemon = null;

/* Load NiftyCorners */
NiftyLoad = function() {
	Nifty('div.rounded');
}

/* Bind events after DOM load */
Event.observe(window, 'load', function() {

	/* Perform the Java Test */
	var test = new JavaTester();
	test.add_finished_callback(on_java_test_finished);
	test.perform();

	/* Center containers at startup */
	new Effect.Center(jQuery('#splashContainer')[0]);
	new Effect.Center(jQuery('#endContainer')[0]);
	new Effect.Center(jQuery('#iframeWrap')[0]);

	/* Keep containers centered after a window resize */
	Event.observe(window, 'resize', function() {
		new Effect.Center(jQuery('#splashContainer')[0]);
		new Effect.Center(jQuery('#desktopFullscreenContainer')[0]);
		new Effect.Center(jQuery('#endContainer')[0]);
		new Effect.Center(jQuery('#iframeWrap')[0]);
	});

	/* Hide panels */
	jQuery('#desktopModeContainer').hide();
	jQuery('#desktopAppletContainer').hide();
	jQuery('#applicationsModeContainer').hide();
	jQuery('#applicationsAppletContainer').hide();
	jQuery('#fileManagerWrap').hide();
	jQuery('#debugContainer').hide();
	jQuery('#debugLevels').hide();
	jQuery('#loginBox').hide();
	jQuery('#newsWrap').hide();

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
		daemon.suspend();
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

	session_mode = jQuery('#session_mode').prop('value');
	session_mode = session_mode.substr(0, 1).toUpperCase()+session_mode.substr(1, session_mode.length-1);

	desktop_fullscreen = false;
	if (jQuery('#desktop_fullscreen_true')[0] && jQuery('#desktop_fullscreen_true').prop('checked'))
		desktop_fullscreen = true;

	if (! jQuery('#use_local_credentials_true')[0] || ! jQuery('#use_local_credentials_true').prop('checked')) {
		try {
			var doc = document.implementation.createDocument("", "", null);
		} catch(e) {
			var doc = new ActiveXObject("Microsoft.XMLDOM");
		}

		var session_node = doc.createElement("session");
		session_node.setAttribute("mode", jQuery('#session_mode').prop('value'));
		session_node.setAttribute("language", jQuery('#session_language').prop('value'));
		session_node.setAttribute("timezone", getTimezoneName());

		var user_node = doc.createElement("user");
		user_node.setAttribute("login", jQuery('#user_login').prop('value'));
		user_node.setAttribute("password", jQuery('#user_password').prop('value'));
		session_node.appendChild(user_node);
		doc.appendChild(session_node);

		if( ! OPTION_USE_PROXY ) {
			jQuery.ajax({
					url: '/ovd/client/start.php',
					type: 'POST',
					dataType: "xml",
					contentType: 'text/xml',
					data: (new XMLSerializer()).serializeToString(doc),
					success: function(xml) {
						onStartSessionSuccess(xml);
					},
					error: function() {
						onStartSessionFailure();
					}
				}
			);
		} else {
			jQuery.ajax({
					url: 'proxy.php',
					type: 'POST',
					dataType: "xml",
					headers: {
						"X-Ovd-Service" : 'start'
					},
					contentType: 'text/xml',
					data: (new XMLSerializer()).serializeToString(doc),
					success: function(xml) {
						onStartSessionSuccess(xml);
					},
					error: function() {
						onStartSessionFailure();
					}
				}
			);
		}
	} else {
		jQuery('#CheckSignedJava')[0].ajaxRequest(jQuery('#sessionmanager_host').prop('value'), jQuery('#session_mode').prop('value'), jQuery('#session_language').prop('value'), getTimezoneName(), 'onStartSessionJavaRequest');
		return false;
	}

	return false;
}

function hideLogin() {
	new Effect.Move(jQuery('#loginBox')[0], { x: 0, y: -1000 });
	setTimeout(function() {
		jQuery('#loginBox').hide();
	}, 1000);
}

function showLogin() {
	jQuery('#loginBox').show();
	new Effect.Move(jQuery('#loginBox')[0], { x: 0, y: 1000 });

	if (debug) {
		Logger.del_instance();
		debug = false;
	}
}

function disableLogin() {
	jQuery('#submitButton').hide();
	jQuery('#submitLoader').show();
}

function enableLogin() {
	jQuery('#submitButton').show();
	jQuery('#submitLoader').hide();
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

	if (session_mode == 'Desktop') {
		new Effect.Move(jQuery('#desktopModeContainer')[0], { x: 0, y: -my_height, mode: 'absolute' });
		setTimeout(function() {
			jQuery('#desktopModeContainer').show();
		}, 2000);
	} else {
		new Effect.Move(jQuery('#applicationsModeContainer')[0], { x: 0, y: -my_height, mode: 'absolute' });
		setTimeout(function() {
			jQuery('#applicationsModeContainer').show();
		}, 2000);
	}

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
		if (session_mode == 'Desktop')
			new Effect.Move(jQuery('#desktopModeContainer')[0], { x: 0, y: my_height });
		else
			new Effect.Move(jQuery('#applicationsModeContainer')[0], { x: 0, y: my_height });
		
		setTimeout(function() {
			hideSplash();
		}, 2000);
	});

	// <server> nodes
	if (! daemon.parse_list_servers(xml)) {
		try {
			showError(i18n.get('internal_error'));
		} catch(e) {}
		
		enableLogin();
		return false;
	}

	daemon.prepare();

	setTimeout(function() {

		if (debug) {
			if (session_mode == 'Desktop')
				jQuery('#desktopModeContainer').height(daemon.my_height);
			else
				jQuery('#applicationsModeContainer').height(daemon.my_height);
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

function showNews(title_, content_) {
	hideNews();

	hideInfo();
	hideError();
	hideOk();

	showLock();

	jQuery('#newsWrap_title').html(title_);
	refresh_body_size();
	var reg = new RegExp("\n", "g");
	jQuery('#newsWrap_content').html('<div style="width: 100%; height: '+parseInt(my_height*(75/100))+'px; overflow: auto;">'+content_.replace(reg, '<br />')+'</div>');

	new Effect.Center(jQuery('#newsWrap')[0]);

	new Effect.Appear(jQuery('#newsWrap')[0]);
}

function hideNews() {
	jQuery('#newsWrap').hide();

	hideLock();

	jQuery('#newsWrap_title').html('');
	jQuery('#newsWrap_content').html('');
	jQuery('#newsWrap').width('750px');
	jQuery('#newsWrap').height('');
}

function showIFrame(url_) {
	showLock();

	jQuery('#iframeContainer').prop('src', url_);

	new Effect.Appear(jQuery('#iframeWrap')[0]);
}

function hideIFrame() {
	jQuery('#iframeWrap').hide();

	jQuery('#iframeContainer').prop('src', 'about:blank');

	hideLock();
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
	var nb_apps_ = daemon.nb_running_apps();
	if (confirm_ == 'always' || (confirm_ == 'apps_only' && nb_apps_ > 0)) {
		if (!confirm(i18n.get('want_logout').replace('#', nb_apps_)))
			return false;
	}
	
	daemon.logout();
	return false;
}
