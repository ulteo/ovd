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

/* Global ovd daemon instance */
var daemon = null;

/* Load NiftyCorners */
NiftyLoad = function() {
	Nifty('div.rounded');
}

Event.observe(window, 'load', function() {

	/* Perform the Java Test */
	var test = new JavaTester();
	test.perform();

	/* Center containers at startup */
	new Effect.Center(jQuery('#endContainer')[0]);
	if (session_mode == 'desktop') {
		new Effect.Center(jQuery('#splashContainer')[0]);
	}

	/* Hide panels */
	jQuery('#desktopModeContainer').hide();
	jQuery('#desktopAppletContainer').hide();
	jQuery('#applicationsModeContainer').hide();
	jQuery('#applicationsAppletContainer').hide();

	/* Show splash */
	jQuery('#splashContainer').show();

	/* Translate strings */
	applyTranslations(i18n_tmp);

	/* Create or Join a session */
	checkExternalSession( function() {
		window.close();
	}, function() {
		startExternalSession(session_mode, session_user, session_pass, session_token,
		                     session_app, session_file, session_file_type, session_file_share);
	});

  /* Configure the debug panel */
	if(debug_mode) {
		Event.observe(jQuery('#level_debug')[0],   'click', function() { Logger.toggle_level('debug'); });
		Event.observe(jQuery('#level_info')[0],    'click', function() { Logger.toggle_level('info'); });
		Event.observe(jQuery('#level_warning')[0], 'click', function() { Logger.toggle_level('warning'); });
		Event.observe(jQuery('#level_error')[0],   'click', function() { Logger.toggle_level('error'); });
		Event.observe(jQuery('#clear_button')[0],  'click', function() { Logger.clear(); });
	}
});

function startExternalSession(mode_, app_, file_, file_type_, file_share_) {
	if( ! OPTION_USE_PROXY ) {
		new Ajax.Request(
			'login.php',
			{
				method: 'post',
				parameters: {
					requested_port: ((window.location.port !=  '')?window.location.port:'443'),
					mode: mode_,
					language: client_language,
					keymap: user_keymap,
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
	} else {
		try {
			var doc = document.implementation.createDocument("", "", null);
		} catch(e) {
			var doc = new ActiveXObject("Microsoft.XMLDOM");
		}

		var session_node = doc.createElement("session");
		session_node.setAttribute("mode", mode_);
		session_node.setAttribute("language", client_language);
		session_node.setAttribute("timezone", getTimezoneName());

		if (app_ != undefined) {
			var start = doc.createElement("start");
			var application = doc.createElement("application");
			application.setAttribute("id", app_);

			if (file_ != undefined && file_type_ != undefined && file_share_ != undefined ) {
				application.setAttribute("file_location", file_);   /* The name to be given to the copy of the resource */
				application.setAttribute("file_type", file_type_);  /* The resource type : native/sharedfolder/http */
				application.setAttribute("file_path", file_share_); /* The path to the resource */
			}

			start.appendChild(application);
			session_node.appendChild(start);
		}

	if( ! OPTION_USE_PROXY ) {
		jQuery.ajax({
				url: '/ovd/client/start.php',
				type: 'POST',
				dataType: "xml",
				contentType: 'text/xml',
				data: (new XMLSerializer()).serializeToString(doc),
				success: function(xml) {
					onStartExternalSessionSuccess(xml);
				},
				error: function() {
					onStartExternalSessionFailure();
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
					onStartExternalSessionSuccess(xml);
				},
				error: function() {
					onStartExternalSessionFailure();
				}
			}
		);
	}

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

	var sessionmanager = {'port': 443}; // Default SM & Gateway port
	if (GATEWAY_FIRST_MODE) {
		sessionmanager.host = window.location.hostname;
		if (window.location.port !=  '')
			sessionmanager.port = window.location.port;
	}
	else {
		var buf = SESSIONMANAGER;
		var sep = buf.lastIndexOf(":");
		if (sep == -1)
			sessionmanager.host = buf;
		else {
			sessionmanager.host = buf.substring(0, sep);
			sessionmanager.port = buf.substring(sep+1, buf.length);
		}
	}
	
	var session_mode = false;
	try {
		session_mode = session_node.getAttribute('mode');
		session_mode = session_mode.substr(0, 1).toUpperCase()+session_mode.substr(1, session_mode.length-1);
	} catch(e) {}

	if (session_mode == 'Desktop')
		daemon = new Desktop(debug_mode);
	else
		daemon = new External(debug_mode);

	daemon.sessionmanager = sessionmanager;
	daemon.keymap = user_keymap;
	try {
		var duration = parseInt(session_node.getAttribute('duration'));
		if (! isNaN(duration))
			daemon.duration = duration;
	} catch(e) {}
	
	daemon.multimedia = ((session_node.getAttribute('multimedia') == 1)?true:false);
	daemon.redirect_client_printers = ((session_node.getAttribute('redirect_client_printers') == 1)?true:false);
	daemon.redirect_smartcards_readers = ((session_node.getAttribute('redirect_smartcards_readers') == 1)?true:false);
	try {
		daemon.redirect_client_drives = session_node.getAttribute('redirect_client_drives');
	} catch(e) {}

	var settings_node = session_node.getElementsByTagName('settings');
	if (settings_node.length > 0) {
		var setting_nodes = settings_node[0].getElementsByTagName('setting');
		daemon.parseSessionSettings(setting_nodes);
	}

	if (! daemon.parse_list_servers(xml)) {
		try {
			showError(i18n.get('internal_error'));
		} catch(e) {}
		
		return false;
	}
	
	daemon.prepare();
	
	setTimeout(function() {
		daemon.loop();
	}, 2500);

	return true;
}

function onStartExternalSessionFailure() {
	showError(i18n.get('internal_error'));

	return false;
}
