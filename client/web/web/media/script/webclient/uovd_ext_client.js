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

/* Bind events after DOM load */
jQuery(window).on("load", function() {
	/* Initialize defaults */
	/* Done by PHP */

	/* Initialize framework */
	initialize_framework();

	/* Test framework providers */
	initialize_tests();

	/* Initialize settings */
	initialize_settings();

	/* Initialize UI components */
	initialize_ui();
});

function initialize_framework() {
	/* Set "framework" namespace */
	window.ovd.framework = {};
	window.ovd.framework.listeners = {};

	var framework = window.ovd.framework; /* shorten names */

	/* Create session_managment */
	framework.session_management = new uovd.SessionManagement();

	/* Create all providers */
	framework.http_providers = {
		java: new uovd.provider.Java().set_applet_codebase("applet/"),
		proxy: new uovd.provider.http.Proxy("proxy.php"),
		direct: new uovd.provider.http.Direct()
	};

	framework.rdp_providers = {
		java: framework.http_providers["java"],
		html5: new uovd.provider.rdp.Html5()
	};

	framework.webapps_providers = {
		jsonp: new uovd.provider.webapps.Jsonp()
	};

	/* Setup Handlers */

	/* handle status modifications */
	framework.session_management.addCallback("ovd.session.starting", function(type, source, params) {
		/* Configure unload */
		window.onbeforeunload = function(e) { return i18n['window_onbeforeunload']; };
		jQuery(window).unload( function() { framework.session_management.stop(); } );

		showSplash();
		pushMainContainer();

		/* Wait for the animation end then show outside of the viewport */
		setTimeout(function() {
			showMainContainer();
		}, 2000);
	});

	framework.session_management.addCallback("ovd.session.statusChanged", function(type, source, params) {
		var from = params["from"];
		var to = params["to"];

		if(to == uovd.SESSION_STATUS_READY) {
			var mode = framework.session_management.session.mode;
			hideOk();
			hideInfo();
			hideError();
			configureUI(mode);
			pullMainContainer();
		}

		if(to == uovd.SESSION_STATUS_LOGGED) {
			hideSplash();
		}
	});

	framework.session_management.addCallback("ovd.session.destroying", function(type, source, params) {
		/* Configure unload */
		window.onbeforeunload = function(e) {};
		jQuery(window).off('unload');

		hideMainContainer();
	});

	framework.session_management.addCallback("ovd.session.destroyed", function(type, source, params) {
		hideSplash();
		generateEnd_external();
		showEnd();
	});

	/* handle errors */
	framework.session_management.addCallback("ovd.session.error", function(type, source, params) {
		var code = params["code"];
		var from = params["from"];
		var message = params["message"];

		if(code == "bad_xml") {
			showError(i18n['internal_error']);
			hideSplash();
			generateEnd_external();
			showEnd();
			return;
		}

		if(from == "start" || from == "session_status") { /* = xml 'response' || 'error' */
			var message = i18n[code] || i18n['internal_error'];
			showError(message);
			hideSplash();
			generateEnd_external();
			showEnd();
			return;
		}
  });

	/* handle crash */
	framework.session_management.addCallback("ovd.rdpProvider.crash", function(type, source, params) {
		generateEnd_external(params["message"]);
	});


	/* logger */
	framework.listeners.logger = new Logger(framework.session_management, "body");
	/* handle progress bar */
	framework.listeners.progress_bar = new ProgressBar(framework.session_management, '#progressBarContent');
	/* handle client insertion */
	framework.listeners.desktop_container = new DesktopContainer(framework.session_management, "#desktopContainer");
	/* handle menu insertion */
	framework.listeners.menu_container = new MenuContainer(framework.session_management, "#menuContainer");
	/* webapps launcher */
	framework.listeners.web_apps_popup_launcher = new WebAppsPopupLauncher(framework.session_management);
	/* applications taskbar */
	framework.listeners.seamless_taskbar = new SeamlessTaskbar(framework.session_management, "#appsContainer");
	/* window manager */
	framework.listeners.seamless_window_manager = new uovd.provider.rdp.html5.SeamlessWindowManager(framework.session_management, "#windowsContainer", new uovd.provider.rdp.html5.SeamlessWindowFactory());
	/* Session-based start_app support */
	framework.listeners.start_app = new StartApp(framework.session_management);
	/* application counter */
	framework.listeners.application_counter = new ApplicationCounter(framework.session_management);
}

function initialize_tests() {
	var framework = window.ovd.framework; /* shorten names */
	var defaults = window.ovd.defaults;

	/* Show 'test in progress' popup :
     closed when at least one RDP provider is valid */
	showLock();
	showSystemTest();

	/* 1 : Java/HTML5 enabled ? */

	if(! defaults.java_installed) {
		delete framework.http_providers.java;
		delete framework.rdp_providers.java;
	}

	if(! defaults.html5_installed) {
		delete framework.rdp_providers.html5;
	}

	/* !!! */
	if(defaults.gateway) { delete framework.rdp_providers.html5; }

  /* 2 : Test RDP providers */
	var nb_rdp_providers = 0;
	framework.tests = {};

	function ok(i) {
		framework.tests[i] = true;
		validate_settings();
	}

	function nok(i) {
		framework.tests[i] = false;
		delete framework.rdp_providers[i];
		validate_settings();
	}

	for(var i in framework.rdp_providers) {
		nb_rdp_providers++;
		(function(i) {
			var provider = framework.rdp_providers[i];
			var success  = jQuery.proxy(function(i) {  ok(i) }, this, i);
			var failure  = jQuery.proxy(function(i) { nok(i) }, this, i);
			var test     = jQuery.proxy(provider.testCapabilities, provider, success, failure);
			framework.tests[i] = null;
			setTimeout(test, 1);
			//provider.testCapabilities(success, failure);
		})(i);
	}

	if(nb_rdp_providers == 0) {
		/* No RDP providers -> no tests ! */
		validate_settings();
	}
}

function initialize_settings() {
	/* Set "settings" namespace */
	window.ovd.settings = {};

	/* shorten names */
	var framework = window.ovd.framework;
	var settings = window.ovd.settings;
	var defaults = window.ovd.defaults;

	/* set settings from session/php/ ... */
	settings.login                 = defaults.login;
	settings.password              = defaults.password;
	settings.mode                  = defaults.mode;
	settings.language              = defaults.language;
	settings.keymap                = defaults.keymap;
	settings.timezone              = getTimezoneName();
	settings.width                 = jQuery(window).innerWidth();
	settings.height                = jQuery(window).innerHeight();
	settings.fullscreen            = false;
	settings.use_local_credentials = defaults.force_use_local_credentials;
	settings.rdp_provider          = defaults.rdp_provider;
	settings.http_provider         = "proxy";
	settings.webapps_provider      = "jsonp";
	settings.wc_url                = getWebClientBaseURL();

	/* Settings needed by the framework */
	if(defaults.local_integration) { settings.local_integration = defaults.local_integration; }
	if(defaults.rdp_input_method)  { settings.rdp_input_method = defaults.rdp_input_method; }

	/* Handle localhost SM */
	if(! defaults.sessionmanager || defaults.sessionmanager == "127.0.0.1" || defaults.sessionmanager == "localhost") {
		settings.sessionmanager = location.hostname;
	} else {
		settings.sessionmanager = defaults.sessionmanager;
	}

	/* Delete unset token */
	if(defaults.token != '') {
		settings.token = defaults.token;
	}

	/* Handle applications start */
	if(defaults.application == '') {
		delete defaults.application;
	} else {
		if(defaults.mode == uovd.SESSION_MODE_DESKTOP) {
			settings.no_desktop = '1';
		}

		var application = {};
		application["id"] = defaults.application;

		if(defaults.file_location != '' && defaults.file_type != '' && defaults.file_path != '' ) {
			application["file_location"] = defaults.file_location;       /* The name to be given to the copy of the resource */
			application["file_type"]     = defaults.file_type;           /* The resource type : native/sharedfolder/http */
			application["file_path"]     = defaults.file_path;           /* The path to the resource */
		} else {
			delete defaults.file_location;
			delete defaults.file_type;
			delete defaults.file_path;
		}

		settings.application = application;
	}
}

function initialize_ui() {
	/* shorten names */
	var framework = window.ovd.framework;
	var settings = window.ovd.settings;
	var defaults = window.ovd.defaults;

	/* Translate text */
	applyTranslations(i18n_tmp);
	set_component_orientation(settings.language);

	/* Suspend and logout buttons */
	jQuery('#logout_link').on('click', function() { confirmLogout(); });
	jQuery('#suspend_link').on('click', function() { framework.session_management.suspend(); });
}

function validate_settings() {
	/* Wait for dependancies */
	/* All initialize_* functions must have been called before validating */
	if( ! window.ovd.defaults || ! window.ovd.framework || ! window.ovd.settings) {
		setTimeout(validate_settings, 1000);
		return;
	}

	/* shorten names */
	var framework = window.ovd.framework;
	var settings = window.ovd.settings;
	var defaults = window.ovd.defaults;

	/* Update RDP providers */
	var nb_rdp_providers = 0;
	var last_provider = null;
	for(var i in framework.tests) {
		last_provider = i ;
		nb_rdp_providers += framework.tests[i] == false ? 0 : 1 ;
	}

	if(nb_rdp_providers == 0) {
		/* No supported RDP provider */
		hideSystemTest();
		showSystemTestError("No RDP provider");
	} else {
		if(framework.tests[settings.rdp_provider] === null) {
			/* The requested mode test is in progress */
		} else if(framework.tests[settings.rdp_provider] === true) {
			/* The requested mode test succeeded */
			hideLock();
			hideSystemTest();
		} else {
			/* False or undefined */
			/* The requested mode failed */
			/* But at least on other RDP provider is available */
			hideLock();
			hideSystemTest();
			showInfo("The "+settings.rdp_provider+" mode is not available. Using "+last_provider+" instead");
			settings.rdp_provider = last_provider;
		}
	}

	/* Update HTTP providers */
	if(defaults.gateway) {
		if(defaults.use_proxy) { settings.http_provider = "proxy"; }
		else { settings.http_provider = "direct"; }
	}

	if(defaults.force_use_local_credentials || settings.use_local_credentials) {
		if(framework.tests.java == false) {
			/* Final error : Java needed */
			hideSystemTest();
			showSystemTestError("Local credentials not supported");
		}

		if(framework.tests.java == true) {
			settings.http_provider = "java";
		}
	}

	if(framework.tests[settings.rdp_provider] == true) {
		/* Can launck the session ? */
		if(/* Session manager OK ? */
			 settings.sessionmanager != '' &&
			 /* User login or local_credentials valid ? */
			 (settings.login || settings.use_local_credentials) ) {
				startSession();
		}
	}
};

function checkExternalSession(active_callback, inactive_callback) {
	/* shorten names */
	var framework = window.ovd.framework;
	var settings = window.ovd.settings;
	var defaults = window.ovd.defaults;

	/* Set parameters */
	framework.session_management.setParameters(settings);

	/* Set providers */
	framework.session_management.setHttpProvider(framework.http_providers[settings.http_provider]);
	framework.session_management.setRdpProvider(framework.rdp_providers[settings.rdp_provider]);
	framework.session_management.setWebAppsProvider(framework.webapps_providers[settings.webapps_provider]);

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
							framework.http_providers[settings.http_provider].sessionStatus_implementation(parse_status);
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

	framework.http_providers[settings.http_provider].sessionStatus_implementation(parse_status);
}

function startSession() {
	/* shorten names */
	var framework = window.ovd.framework;

	/* Avoid multiple calls */
	startSession = function() {};

	/* Create or Join a session */
	checkExternalSession( function() {
		window.close();
	}, function() {
		framework.session_management.start();
	});
}
