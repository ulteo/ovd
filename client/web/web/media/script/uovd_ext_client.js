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

Event.observe(window, 'load', function() {
	var test = new JavaTester();
	test.perform();
});

function startExternalSession(mode_) {
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
