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

/* Load NiftyCorners */
NiftyLoad = function() {
	Nifty('div.rounded');
}

Event.observe(window, 'load', function() {
	/* Center containers at startup */
	new Effect.Center(jQuery('#endContainer')[0]);
	if (session_mode == uovd.SESSION_MODE_DESKTOP) {
		new Effect.Center(jQuery('#splashContainer')[0]);
	}

	/* Hide panels */
	jQuery('#sessionContainer').hide();

	/* Show splash */
	jQuery('#splashContainer').show();

	/* Translate strings */
	applyTranslations(i18n_tmp);

  /* Configure the debug panel */
	if(debug_mode) {
		Event.observe(jQuery('#level_debug')[0],   'click', function() { Logger.toggle_level('debug'); });
		Event.observe(jQuery('#level_info')[0],    'click', function() { Logger.toggle_level('info'); });
		Event.observe(jQuery('#level_warning')[0], 'click', function() { Logger.toggle_level('warning'); });
		Event.observe(jQuery('#level_error')[0],   'click', function() { Logger.toggle_level('error'); });
		Event.observe(jQuery('#clear_button')[0],  'click', function() { Logger.clear(); });
	}

	/* handle status modifications */
	session_management.addCallback("ovd.session.statusChanged", function(type, source, params) {
		var from = params["from"];
		var to = params["to"];

		if(to == uovd.SESSION_STATUS_READY) {
			jQuery('#sessionContainer').fadeIn(1000);
		}
	});

	session_management.addCallback("ovd.session.starting", function(type, source, params) {
		/* Configure unload */
		window.onbeforeunload = function(e) { return i18n.get('window_onbeforeunload'); };
		jQuery(window).unload( function() { session_management.stop(); } );
	});

	session_management.addCallback("ovd.session.destroying", function(type, source, params) {
		generateEnd_external();
		jQuery('#splashContainer').hide();
		jQuery('#endContainer').show();
		jQuery('#sessionContainer').fadeOut(1000);

		/* Configure unload */
		window.onbeforeunload = function(e) {};
		jQuery(window).off('unload');
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

	/* handle client insertion */
	new DesktopContainer(session_management, "#desktopContainer");

	/* window manager */
	new SeamlessWindowManager(session_management, "#windowsContainer", new uovd.provider.rdp.html5.SeamlessWindowFactory());

	/* Session-based start_app support */
	new StartApp(session_management);

	/* webapps launcher */
	new WebAppsPopupLauncher(session_management);

	/* Create or Join a session */
	checkExternalSession( function() {
		window.close();
	}, function() {
		session_management.start();
	});
});

function checkExternalSession(active_callback, inactive_callback) {
	/* Set parameters */
	var parameters = {};
	parameters["session_manager"] = SESSIONMANAGER;
	parameters["username"] = session_user;
	parameters["password"] = session_pass;
	parameters["session_type"] = session_mode;
	parameters["language"] = window.client_language;
	parameters["keymap"] = window.user_keymap;
	parameters["rdp_input_method"] = window.rdp_input_method;
	parameters["timezone"] = getTimezoneName();
	parameters["width"] = window.innerWidth;
	parameters["height"] = window.innerHeight;
	parameters["fullscreen"] = false;
	parameters["debug"] = window.debug_mode;
	parameters["local_integration"] = window.local_integration;

	if(parameters["session_manager"] == "127.0.0.1") {
		parameters["session_manager"] = location.hostname;
	}

	if(session_token != '') {
		parameters["token"] = session_token;
	}

	if (session_app != '') {
		if (session_mode == 'desktop') {
			parameters["no_desktop"] = '1';
		}

		var application = {};
		application["id"] = session_app;

		if (session_file != '' && session_file_type != '' && session_file_share != '' ) {
			application["file_location"] = session_file;   /* The name to be given to the copy of the resource */
			application["file_type"] = session_file_type;  /* The resource type : native/sharedfolder/http */
			application["file_path"] = session_file_share; /* The path to the resource */
		}

		parameters["application"] = application;
	}

	session_management.setParameters(parameters);

	/* Set providers */
	var http_provider = new uovd.provider.http.Proxy("proxy.php");
	var rdp_provider = new uovd.provider.rdp.Html5();
	var webapps_provider = new uovd.provider.webapps.Jsonp();

	session_management.setRdpProvider(rdp_provider);
	session_management.setAjaxProvider(http_provider);
	session_management.setWebAppsProvider(webapps_provider);
	
	/* Check session status */
	var parse_status = function(xml) {
		try {
			var xml_root = jQuery(xml).find(":root");
			if(xml_root.prop("nodeName") == "session") {
				switch(xml_root.attr("status")) {
					case uovd.SESSION_STATUS_CREATING :
					case uovd.SESSION_STATUS_CREATED :
					case uovd.SESSION_STATUS_INITED :
					case uovd.SESSION_STATUS_READY :
					case uovd.SESSION_STATUS_WAIT_DESTROY :
					case uovd.SESSION_STATUS_DESTROYING :
					case uovd.SESSION_STATUS_DESTROYED :
						/* Wait and retry */
						setTimeout( function() {
							http_provider.sessionStatus_implementation(parse_status);
						}, 1000);
						return;

					case uovd.SESSION_STATUS_ERROR :
					case uovd.SESSION_STATUS_UNKNOWN :
					case uovd.SESSION_STATUS_DISCONNECTED :
						/* No active session */
						inactive_callback();
						return;

					default :
						/* Active session */
						active_callback();
						return;
				}
			}
		} catch(e) {}

		/* Bad XML response : treat-it as "no session" */
		inactive_callback();
	}

	http_provider.sessionStatus_implementation(parse_status);
}
