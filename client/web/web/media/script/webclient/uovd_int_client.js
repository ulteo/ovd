/**
 * Copyright (C) 2012-2014 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2012
 * Author David PHAM-VAN <d.pham-van@ulteo.com> 2012, 2013, 2014
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
	initialize_defaults();

	/* Initialize framework */
	initialize_framework();

	/* Test framework providers */
	initialize_tests();

	/* Initialize settings */
	initialize_settings();

	/* Initialize UI components */
	initialize_ui();
});

function startSession() {
	/* shorten names */
	var framework = window.ovd.framework;
	var settings = window.ovd.settings;
	var defaults = window.ovd.defaults;

	framework.session_management.setParameters(settings);
	framework.session_management.setAjaxProvider(framework.http_providers[settings.http_provider]);
	framework.session_management.setRdpProvider(framework.rdp_providers[settings.rdp_provider]);
	framework.session_management.setWebAppsProvider(framework.webapps_providers[settings.webapps_provider]);
	framework.session_management.start();
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

	/* Update keymap */
	jQuery('#session_keymap > option:selected').prop("selected", false);
	var keymap = jQuery('#session_keymap > option[value="'+settings.keymap+'"]');
	if(keymap[0]) { keymap.prop("selected", true); }                                    /* Set the detected keymap */
	else { jQuery('#session_keymap > option[value="en-us"]').prop("selected", true); }; /* If not supported, default to english */

	/* Update session mode */
	if(settings.mode == uovd.SESSION_MODE_DESKTOP) {
			jQuery('#advanced_settings_applications').hide();
			jQuery('#advanced_settings_desktop').show();
	}
	if(settings.mode == uovd.SESSION_MODE_APPLICATIONS) {
			jQuery('#advanced_settings_applications').show();
			jQuery('#advanced_settings_desktop').hide();
	}

	/* Update InputMethod */
	if(settings.rdp_input_method == "scancode") {
		jQuery("#session_keymap").prop('disabled', false);
	} else {
		jQuery("#session_keymap").prop('disabled', true);
	}

	/* Update RDP providers */
	var nb_rdp_providers = 0;
	for(var i in framework.tests) {
		var result = framework.tests[i];
		var option = jQuery("#rdp_mode option[value='"+i+"']");

		if(result != false) { /* true or null */
			/* Test succeded or in-progress */
			nb_rdp_providers++;
		}

		if(result != true) { /* false or null */
			/* Test failed or in-progress */
			/* Disable the option */
			option.prop("disabled", true).prop("selected", false);
		}

		if(result == true) {
			/* Test succeded */

			/* If the option is disabled */
			if(option.prop('disabled')) {
				/* Enable the option */
				option.prop("disabled", false);

				/* Select it if it is the default value */
				if(i == defaults.rdp_provider) {
					option.prop("selected", "selected");
				}
			}
		}
	}

	/* Ensure that a value is selected */
	if(jQuery("#rdp_mode").val() == null) {
		var remain_rdp = jQuery("#rdp_mode > option[disabled!='disabled']");
		if(remain_rdp[0]) {
			jQuery(remain_rdp[0]).prop("selected", "selected");
		}
	}

	/* Set the new value */
	settings.rdp_provider = jQuery('#rdp_mode').val();

	if(nb_rdp_providers == 0) {
		/* No supported RDP provider */
		hideSystemTest();
		showSystemTestError("No RDP provider");
	}

	/* Update HTTP providers */
	if(defaults.gateway) {
		if(defaults.use_proxy) { settings.http_provider = "proxy"; }
		else { settings.http_provider = "direct"; }
	}

	if(defaults.force_use_local_credentials || settings.use_local_credentials) {
		if(! framework.rdp_providers.java || ! framework.tests.java) {
			showLoginError("Use local credentials : Java support required");
			settings.use_local_credentials = false;
			jQuery("#use_local_credentials_1").removeAttr('checked');
			jQuery("#use_local_credentials_0").prop("checked", "checked")
		} else {
			settings.http_provider = "java";
			settings.use_local_credentials = true;
			jQuery("#use_local_credentials_0").removeAttr('checked');
			jQuery("#use_local_credentials_1").prop("checked", "checked")
		}
	}

	/* Initialize the SM Host field */
	if(settings.sessionmanager == 'localhost' || settings.sessionmanager == '127.0.0.1') {
		settings.sessionmanager = location.host;
	}
	window.updateSMHostField = function() {
		if (jQuery('#sessionmanager_host').css('color') == 'grey') { jQuery('#sessionmanager_host').prop('value', i18n['sessionmanager_host_example']) };
	}

	/* CheckLogin */
	if(settings.use_local_credentials) {
		jQuery('#user_login').hide();
		jQuery('#user_login_local').show();
		jQuery('#password_row').hide();

		if(jQuery('#user_login_local').html() == "") {
			var detection = function(detected) {
				if(detected) {
					jQuery('#user_login_local').html(detected);
				} else {
					/* !!! erreur */
				}
			};

			var java = framework.rdp_providers.java;
			if(java) { setTimeout(jQuery.proxy(java.getUserLogin, java, detection), 1); }
			else { detection(); }
		}
	} else if(settings.force_sso) {
		jQuery('#user_login').prop('disabled', true);
		jQuery('#password_row').hide();
	} else {
		jQuery('#user_login').show();
		jQuery('#user_login_local').hide();
		jQuery('#password_row').show();
	}

	/* Enable or disable "connect" button */
	if(/* Session manager OK ? */
	   settings.sessionmanager != '' &&
	   settings.sessionmanager != i18n['sessionmanager_host_example'] &&
		 /* RDP provider available ? */
		 nb_rdp_providers > 0 &&
	   /* User login or local_credentials valid ? */
		 (settings.login || settings.use_local_credentials || settings.force_sso) ) {
		jQuery('#connect_gettext').removeAttr('disabled');
	} else {
		jQuery('#connect_gettext').prop('disabled', true);
	}
}

function initialize_defaults() {
	/* shorten names */
	var defaults = window.ovd.defaults;

	/* set default from session/php/ ... */
	defaults.login                 = jQuery('#user_login').val();
	defaults.password              = jQuery('#user_password').val();
	defaults.mode                  = jQuery('#session_mode').val();
	defaults.language              = jQuery('#session_language').val();
	defaults.keymap                = jQuery('#session_keymap').val();
	defaults.timezone              = getTimezoneName();
	defaults.width                 = jQuery(window).innerWidth();
	defaults.height                = jQuery(window).innerHeight();
	defaults.fullscreen            = jQuery("#desktop_fullscreen_1").prop('checked');
	defaults.debug                 = jQuery("#debug_1").prop('checked');
	defaults.use_local_credentials = jQuery("#use_local_credentials_1").prop('checked');
	defaults.rdp_provider          = jQuery('#rdp_mode').val();
	defaults.rdp_input_method      = jQuery('#session_input_method').val();
	defaults.http_provider         = "proxy";
	defaults.webapps_provider			 = "jsonp";
	defaults.wc_url                = getWebClientBaseURL();
}

function initialize_settings() {
	/* Set "settings" namespace */
	window.ovd.settings = {};

	/* shorten names */
	var framework = window.ovd.framework;
	var settings = window.ovd.settings;
	var defaults = window.ovd.defaults;

	/* set settings from session/php/ ... */
	settings.sessionmanager        = jQuery('#sessionmanager_host').val()
	settings.login                 = jQuery('#user_login').val();
	settings.password              = jQuery('#user_password').val();
	settings.mode                  = jQuery('#session_mode').val();
	settings.language              = jQuery('#session_language').val();
	settings.keymap                = jQuery('#session_keymap').val();
	settings.timezone              = getTimezoneName();
	settings.width                 = jQuery(window).innerWidth();
	settings.height                = jQuery(window).innerHeight();
	settings.fullscreen            = jQuery("#desktop_fullscreen_1").prop('checked');
	settings.debug                 = jQuery("#debug_1").prop('checked');
	settings.use_local_credentials = jQuery("#use_local_credentials_1").prop('checked');
	settings.rdp_provider          = jQuery('#rdp_mode').val();
	settings.http_provider         = "proxy";
	settings.webapps_provider      = "jsonp";
	settings.wc_url                = defaults.wc_url;

	/* Settings needed by the framework */
	if(defaults.local_integration) { settings.local_integration = defaults.local_integration; }
	if(defaults.rdp_input_method)  { settings.rdp_input_method = defaults.rdp_input_method; }
	if(defaults.force_sso)         { settings.force_sso = defaults.force_sso; }

	/* Keymap autodetect */
	if(defaults.keymap_autodetect && ! defaults.force_keymap) {
		var detection = function(detected) {
			if(!detected) { detected = jQuery('#session_language').prop('value'); /* !!! user agent */ }
			settings.keymap = detected;
			validate_settings();
		};

		if(framework.rdp_providers.java) { framework.rdp_providers.java.getDetectedKeyboardLayout(detection); }
		else { detection();	}
	}

	validate_settings();
}

function initialize_ui() {
	/* shorten names */
	var framework = window.ovd.framework;
	var settings = window.ovd.settings;
	var defaults = window.ovd.defaults;

	/* Bind resize events */
	jQuery(window).resize(function() {
		settings.width  = jQuery(window).innerWidth();
		settings.height = jQuery(window).innerHeight();
	});

	/* Bind UI events (not forms) */
	jQuery('#logout_link').on('click', function() { confirmLogout(); });
	jQuery('#iframeLink').on('click', function() { hideLock() ; hideIFrame(); });
	jQuery('#newsHideLink').on('click', function() { hideLock() ; hideNews(); });
	jQuery('#suspend_link').on('click', function() { framework.session_management.suspend(); });
	jQuery('#advanced_settings_gettext').on('click', function () {
		if(jQuery('#advanced_settings').filter(":visible")[0]) {
			jQuery('#advanced_settings').slideUp(400);
			jQuery('#advanced_settings_status').prop('class', 'image_show_png');
		} else {
			jQuery('#advanced_settings').slideDown(400);
			jQuery('#advanced_settings_status').prop('class', 'image_hide_png');
		}
	});
	jQuery('#startsession').on('submit', function() {
		hideLoginError();
		disableLogin();
		startSession();
	});

	/* Bind foms UI events */
	jQuery('#user_login').on('keyup change', function() { settings.login = jQuery(this).val(); validate_settings(); });
	jQuery('#user_password').on('keyup change', function() { settings.password = jQuery(this).val(); validate_settings(); });
	jQuery('#session_mode').on('click change', function() { settings.mode = jQuery(this).val(); validate_settings(); });
	jQuery('#rdp_mode').on('click change', function() { settings.rdp_provider = jQuery(this).val(); validate_settings(); });
	jQuery('#debug_1, #debug_0').on('click change', function() { settings.debug = (jQuery(this).val() == 1) ? true : false; validate_settings(); });
	jQuery('#desktop_fullscreen_1, #desktop_fullscreen_0').on('click change', function() { settings.fullscreen = (jQuery(this).val() == 1) ? true : false; validate_settings(); });
	jQuery('#use_local_credentials_1, #use_local_credentials_0').on('click change', function() { settings.use_local_credentials = (jQuery(this).val() == 1) ? true : false; validate_settings(); });
	jQuery('#session_keymap').on('change keyup', function() { settings.keymap = jQuery(this).val(); validate_settings(); });
	jQuery('#session_input_method').on('change keyup', function() { settings.rdp_input_method = jQuery(this).val(); validate_settings(); });
	jQuery('#sessionmanager_host').on('keyup change', function() { settings.sessionmanager = jQuery(this).val(); validate_settings(); });
	jQuery('#sessionmanager_host').on('focus blur', function(e) {
		var example = i18n['sessionmanager_host_example'];
		if(e.type == "focus") { if (jQuery(this).val() == example) jQuery('#sessionmanager_host').css('color', 'black').val('');
		} else {                if (jQuery(this).val() == '') jQuery('#sessionmanager_host').css('color', 'grey').val(example); }
		settings.sessionmanager = jQuery(this).val();
	});
	
	jQuery('#session_language').on('change keyup', function() {
		settings.language = jQuery(this).val();
		
		var url = jQuery('#session_language').find(":selected").css("background-image");
		if (! url) {
			url = "";
		}
		
		/* Update flag */
		jQuery('#session_language').css({"background-image": url});
		
		/* Translate UI */
		translateInterface(settings.language);
		set_component_orientation(settings.language);
		
		validate_settings();
	});
	
	var url = jQuery('#session_language').find(":selected").css("background-image");
	if (! url) {
		url = "";
	}
	
	/* Init flag flag */
	jQuery('#session_language').css({"background-image": url});

	/* Initialize the SM Host field */
	if (jQuery('#sessionmanager_host').val() == '') { jQuery('#sessionmanager_host').css('color', 'grey').val(i18n['sessionmanager_host_example']); }
	window.updateSMHostField = function() {
		if (jQuery('#sessionmanager_host').css('color') != 'grey')
			return;
		jQuery('#sessionmanager_host').prop('value', i18n['sessionmanager_host_example']);
	}

	/* Login/Password */
	if(defaults.force_use_local_credentials) {
		settings.login = ""; 
		jQuery('#user_login').val("");
		jQuery('#user_login').val("").prop("disabled", true);
		jQuery('#user_password').val("").prop("disabled", true);
	}

	/* Translate text */
	applyTranslations(i18n_tmp);
	set_component_orientation(settings.language);
}

function initialize_framework() {
	/* Set "framework" namespace */
	window.ovd.framework = {};
	window.ovd.framework.listeners = {};
	window.show_time_restriction_windows = false;

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
		java: window.ovd.framework.http_providers["java"],
		html5: new uovd.provider.rdp.Html5()
	};

	framework.webapps_providers = {
		jsonp: new uovd.provider.webapps.Jsonp()
	};

	/* Setup Handlers */

	/* handle status modifications */
	framework.session_management.addCallback("ovd.session.starting", function(type, source, params) {
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

	framework.session_management.addCallback("ovd.session.statusChanged", function(type, source, params) {
		var from = params["from"];
		var to = params["to"];

		if(to == uovd.SESSION_STATUS_READY) {
			var mode = framework.session_management.session.mode;
			configureUI(mode);
			pullMainContainer();
		}
		
		if(to == uovd.SESSION_STATUS_LOGGED) {
			hideSplash();
		}
	});
	
	framework.session_management.addCallback("ovd.session.timeRestriction", function(type, source, params) {
		if (params["when"] > 10) { // HARDCODED 10 value related to next session_status call. Must define by sessionmanagement constant/var
			return;
		}
		
		if (window.show_time_restriction_windows == true) {
			return;
		}
		
		window.show_time_restriction_windows = true;
		setTimeout(function() {
			alert(i18n.get('session_time_restriction_expire').replace('%MINUTES%', params["when"]));
		}, 100);
	});

	framework.session_management.addCallback("ovd.session.destroying", function(type, source, params) {
		hideMainContainer();
		showSplash();
	});

	framework.session_management.addCallback("ovd.session.destroyed", function(type, source, params) {
		hideMainContainer();
		hideSplash();
		generateEnd_internal();
		showEnd();
	});

	/* handle errors */
	framework.session_management.addCallback("ovd.session.error", function(type, source, params) {
		var code = params["code"];
		var from = params["from"];
		var message = params["message"];

		if(code == "bad_xml") {
			showLoginError(i18n['internal_error']);
			enableLogin();
			return;
		}

		if(from == "start" || from == "session_status") { /* = xml 'response' || 'error' */
			var message = i18n[code] || i18n['internal_error'];
			showLoginError(message);
			enableLogin();
			return;
		}
  });

	/* handle crash */
	framework.session_management.addCallback("ovd.rdpProvider.crash", function(type, source, params) {
		generateEnd_internal(params["message"]);
	});

	/* logger */
	framework.listeners.logger = new Logger(framework.session_management, "body");
	/* handle progress bar */
	framework.listeners.progress_bar = new ProgressBar(framework.session_management, '#progressBarContent');
	/* handle client insertion */
	framework.listeners.desktop_container = new DesktopContainer(framework.session_management, "#desktopContainer");
	/* handle menu insertion */
	framework.listeners.menu_container = new MenuContainer(framework.session_management, "#menuContainer");
	/* applications launcher */
	framework.listeners.seamless_launcher = new SeamlessLauncher(framework.session_management, "#appsContainer", "#headerLogo");
	/* applications taskbar */
	framework.listeners.seamless_taskbar = new SeamlessTaskbar(framework.session_management, "#appsContainer");
	/* news display */
	framework.listeners.news = new News(framework.session_management, "#newsList");
	/* webapps launcher */
	framework.listeners.web_apps_popup_launcher = new WebAppsPopupLauncher(framework.session_management);
	/* window manager */
	framework.listeners.seamless_window_manager = new uovd.provider.rdp.html5.SeamlessWindowManager(framework.session_management, "#windowsContainer", new uovd.provider.rdp.html5.SeamlessWindowFactory());
	/* ajaxplorer file manager */
	framework.listeners.ajaxplorer = new Ajaxplorer(framework.session_management, "#fileManagerContainer", "#appsContainer");
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

  /* 2 : Test RDP providers */
	var nb_rdp_providers = 0;
	framework.tests = {};

	function ok(i) {
		framework.tests[i] = true;
		hideLock();
		hideSystemTest();
		jQuery('#loginBox').fadeIn();
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
			setTimeout(test, 500);
			//provider.testCapabilities(success, failure);
		})(i);
	}

	if(nb_rdp_providers == 0) {
		/* No RDP providers -> no tests ! */
		validate_settings();
	}
}

function synchronize() {
	var settings = window.ovd.settings;

	var parameters = {};
	parameters["login"]                 = settings.login;
	parameters["mode"]                  = settings.mode;
	parameters["type"]                  = settings.rdp_provider;
	parameters["language"]              = settings.language;
	parameters["keymap"]                = settings.keymap;
	parameters["debug"]                 = settings.debug;
	parameters["desktop_fullscreen"]    = settings.fullscreen;
	parameters["use_local_credentials"] = settings.use_local_credentials;
	parameters["input_method"]          = settings.rdp_input_method;

	/* Dont' push the WC ip : replace it by "localhost" */
	if(settings.sessionmanager == window.location.host) { parameters["sessionmanager_host"]   = "127.0.0.1"; }
	else { parameters["sessionmanager_host"]   = settings.sessionmanager; }

	jQuery.ajax({
		url: "synchronize.php",
		type: "POST",
		data: parameters
	});
}
