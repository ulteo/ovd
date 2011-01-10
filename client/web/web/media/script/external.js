/**
 * Copyright (C) 2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Jeremy DESVAGES <jeremy@ulteo.com>
 * Author Julien LANGLOIS <julien@ulteo.com>
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

function startExternalSession() {
	explorer = false;

	startsession = false;

	debug = false;

	session_mode = 'applications';
	session_mode = session_mode.substr(0, 1).toUpperCase()+session_mode.substr(1, session_mode.length-1);

	desktop_fullscreen = false;

	new Ajax.Request(
		'login.php',
		{
			method: 'post',
			parameters: {
				sessionmanager_host: SESSIONMANAGER_HOST,
				login: USER_LOGIN,
				password: USER_PASSWORD,
				mode: 'applications',
				language: USER_LANGUAGE,
				keymap: USER_KEYMAP,
				timezone: USER_TIMEZONE,
				desktop_fullscreen: ((desktop_fullscreen)?1:0),
				debug: ((debug)?1:0)
			},
			asynchronous: false,
			onSuccess: function(transport) {
				onStartExternalSessionSuccess(transport.responseXML);
			},
			onFailure: function() {
				onStartExternalSessionFailure();
			}
		}
	);

	if (! startsession)
		return false;

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

	startsession = true;

	setTimeout(function() {
		daemon = new External('ulteo-applet.jar', 'org.ulteo.ovd.applet.Applications', debug);
		daemon.explorer = explorer;

		daemon.keymap = USER_KEYMAP;
		try {
			daemon.duration = parseInt(session_node.getAttribute('duration'));
		} catch(e) {}
		daemon.duration = parseInt(session_node.getAttribute('duration'));
		daemon.multimedia = ((session_node.getAttribute('multimedia') == 1)?true:false);
		daemon.redirect_client_printers = ((session_node.getAttribute('redirect_client_printers') == 1)?true:false);
		try {
			daemon.redirect_client_drives = session_node.getAttribute('redirect_client_drives');
		} catch(e) {}

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

function UlteoOVD_start_Application(web_client_url_, application_id_) {
	var url = web_client_url_+'/external.php?app='+application_id_;
	
	_UlteoOVDpopupOpen(url);
}

function UlteoOVD_start_Application_with_file(web_client_url_, application_id_, path_) {
	var url = web_client_url_+'/external.php?app='+application_id_+'path='+path_;
	
	_UlteoOVDpopupOpen(url);
}

function _UlteoOVDpopupOpen(url_) {
	var my_width = 436;
	var my_height = 270;
	var new_width = 0;
	var new_height = 0;
	var pos_top = screen.height - 270;
	var pos_left = screen.width - 436;
	
	var date = new Date();
	var rand_ = Math.round(Math.random()*100)+date.getTime();
	
	var w = window.open(url_, 'Ulteo'+rand_, 'toolbar=no,status=no,top='+pos_top+',left='+pos_left+',width='+my_width+',height='+my_height+',scrollbars=no,resizable=no,resizeable=no,fullscreen=no');
	
	return true;
}
