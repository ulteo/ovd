
/* ---- Show/Hide UI elements -----*/

function showLogin() {
	jQuery('#main').fadeIn();
}

function hideLogin() {
	jQuery('#main').fadeOut();
}

function showLock() {
	jQuery('#lock').show();
	jQuery('#overlay').fadeIn();
}

function hideLock() {
	jQuery('#lock').hide();
	jQuery('#overlay').hide();
}

function showSplash() {
	jQuery('#splashContainer').fadeIn();
}

function hideSplash() {
	jQuery('#splashContainer').fadeOut();
}

function showSystemTest() {
	jQuery('#systemTest').show();
}

function hideSystemTest() {
	jQuery('#systemTest').hide();
}

function showNews(title_, content_) {
	var message = '<div style="width: 100%; height: 75%; overflow: auto;">'+
	               content_.replace(new RegExp("\n", "g"), '<br />')+
	              '</div>';
	jQuery('#newsTitle').html(title_);
	jQuery('#newsContent').html(message);
	jQuery('#news').show();
}

function hideNews() {
	jQuery('#news').hide();
}

function showMainContainer() {
	jQuery('#sessionContainer').fadeIn();
}

function hideMainContainer() {
	jQuery('#sessionContainer').fadeOut();
}

function showEnd() {
	jQuery('#endContainer').fadeIn();
}

function hideEnd() {
	jQuery('#endContainer').fadeOut();
}

function showSystemTestError(message) { 
	jQuery("#systemTestErrorMessage").html(message);	
	jQuery('#systemTestError').show();
}

function hideSystemTestError(message) { 
	jQuery('#systemTestError').hide();
}

function showLoginError(message) {
	jQuery('#loginError').html(message);
}

function hideLoginError() {
	jQuery('#loginError').html("");
}

function showError(errormsg) {
	var message = '<div style="width: 16px; height: 16px; float: right; margin-right: 20px;">'+
	              	'<a href="javascript:;" onclick="hideError(); return false;">'+
	              		'<img src="media/image/cross.png" width="16" height="16" alt="" title="" />'+
	              	'</a>'+
	              '</div>'+
	              errormsg;
	jQuery('#error').html(message).show();
	jQuery('#notification').show();
}

function hideError() {
	jQuery('#notification').fadeOut(400, function() {
		jQuery('#error').hide();
	});
}

function showOk(okmsg) {
	var message = '<div style="width: 16px; height: 16px; float: right; margin-right: 20px;">'+
	              	'<a href="javascript:;" onclick="hideOk(); return false;">'+
	              		'<img src="media/image/cross.png" width="16" height="16" alt="" title="" />'+
	              	'</a>'+
	              '</div>'+
	              okmsg;
	jQuery('#ok').html(message).show();
	jQuery('#notification').show();
}

function hideOk() {
	jQuery('#notification').fadeOut(400, function() {
		jQuery('#ok').hide();
	});
}

function showInfo(infomsg) {
	var message = '<div style="width: 16px; height: 16px; float: right; margin-right: 20px;">'+
	              	'<a href="javascript:;" onclick="hideInfo(); return false;">'+
	              		'<img src="media/image/cross.png" width="16" height="16" alt="" title="" />'+
	              	'</a>'+
	              '</div>'+
	              infomsg;
	jQuery('#info').html(message).show();
	jQuery('#notification').show();
}

function hideInfo() {
	jQuery('#notification').fadeOut(400, function() {
		jQuery('#info').hide();
	});
}

/* ------- Generate end messages ------ */

function generateEnd_internal(error) {
	if( ! jQuery('#endContent > *')[0]) {
		var buf = jQuery(document.createElement('div')).css({'font-size' : '1.1em', 'font-weight' : 'bold', 'color' : '#686868'});
		var end_message = null;

		if(error) {
			var end_message = jQuery(document.createElement('div')).prop('id', 'endMessage');

			var end_message_title = jQuery(document.createElement('div'));
			end_message_title.addClass("msg_error");
			end_message_title.html(i18n['session_end_unexpected']);

			var end_message_details = jQuery(document.createElement('div'));

			var end_message_details_link = jQuery(document.createElement('a'))
			end_message_details_link.prop("href", "javascript:;");
			end_message_details_link.css("padding", "10px");
			end_message_details_link.html(i18n['error_details']);
			end_message_details.append(end_message_details_link);

			var end_message_text = jQuery(document.createElement('div'));
			end_message_text.html(error);
			end_message_text.hide();

			end_message.append(end_message_title);
			end_message.append(end_message_details);
			end_message.append(end_message_text);

			end_message_details_link.on("click", function() {
				end_message_text.slideToggle();
			});
		} else {
			var end_message = jQuery(document.createElement('span')).prop('id', 'endMessage').html(i18n['session_end_ok']);
		}

		var close_container = jQuery(document.createElement('div')).css('margin-top', '10px');
		var close_text = jQuery(document.createElement('span')).html(i18n['start_another_session']);
		close_text.prop("id", "close_text");
		close_container.append(close_text);

		buf.append(end_message);
		buf.append(close_container);
		jQuery('#endContent').append(buf);

		jQuery("#close_text a").click( function() {
			if (window.ovd.defaults.force_sso) {
				document.location.reload();
				return;
			}
			hideEnd();
			showLogin();
			pullLogin();

			setTimeout( function() {
				/* Wait for animation end */
				resetEnd();
			}, 2000);
		});
	}
}

function generateEnd_external(error) {
	if( ! jQuery('#endContent > *')[0]) {
		var buf = jQuery(document.createElement('div')).css({'font-size' : '1.1em', 'font-weight' : 'bold', 'color' : '#686868'});
		var end_message = null;

		if(error) {
			var end_message = jQuery(document.createElement('div')).prop('id', 'endMessage');

			var end_message_title = jQuery(document.createElement('div'));
			end_message_title.addClass("msg_error");
			end_message_title.html(i18n['session_end_unexpected']);

			var end_message_details = jQuery(document.createElement('div'));

			var end_message_details_link = jQuery(document.createElement('a'))
			end_message_details_link.prop("href", "javascript:;");
			end_message_details_link.css("padding", "10px");
			end_message_details_link.html(i18n['error_details']);
			end_message_details.append(end_message_details_link);

			var end_message_text = jQuery(document.createElement('div'));
			end_message_text.html(error);
			end_message_text.hide();

			end_message.append(end_message_title);
			end_message.append(end_message_details);
			end_message.append(end_message_text);

			end_message_details_link.on("click", function() {
				end_message_text.slideToggle();
			});
		} else {
			var end_message = jQuery(document.createElement('span')).prop('id', 'endMessage').html(i18n['session_end_ok']);
		}

		buf.append(end_message);
		jQuery('#endContent').append(buf);
	}
}

function resetEnd() {
	jQuery('#endContent').empty();
}

/* ------- Customize panels ------ */

function configureUI(mode) {
	var session_params = ovd.framework.session_management.parameters;
	var session_settings = ovd.framework.session_management.session.settings;
	if(mode == uovd.SESSION_MODE_APPLICATIONS) {
		/* Configure page layout */
		(function() {
			/* Hide desktops */
			/* do not use .hide() or applet wil not load */
			jQuery('#desktopContainer').width(1).height(1).css("overflow", "hidden");

			/* Set background*/
			jQuery('#sessionContainer').css("background", "");

			/* Show applications mode components */
			jQuery("#applicationsHeader").show();
			jQuery("#applicationsContainer").show();
			jQuery("#windowsContainer").show();

			/* Set name */
			jQuery('#user_displayname').html(session_settings.user_displayname);
		})();

		/* Suport suspend ? */
		(function() {
			if(session_settings["persistent"]) {
				jQuery('#suspend_button').show();
			}
		})();
	} else {
		/* Configure page layout */
		(function() {
			/* Show desktop */
			jQuery('#desktopContainer').width("100%").height("100%");

			/* Set background*/
			jQuery('#sessionContainer').css("background", "#000");

			/* Hide applications mode components */
			jQuery("#applicationsHeader").hide();
			jQuery("#applicationsContainer").hide();
			jQuery("#windowsContainer").hide();
		})();
	}
}

function initSplashConnection() {
	jQuery("#unloading_ovd_gettext").hide();
	jQuery("#loading_ovd_gettext").show();
	jQuery('#progressBarContent').css("width", "0%");
}

function initSplashDisconnection() {
	jQuery("#loading_ovd_gettext").hide();
	jQuery("#unloading_ovd_gettext").show();
	jQuery('#progressBarContent').css("width", "100%");
}

function disableLogin() {
  jQuery('#submitButton').hide();
  jQuery('#submitLoader').show();
}

function enableLogin() {
  jQuery('#submitButton').show();
  jQuery('#submitLoader').hide();
}

function pullMainContainer() {
	jQuery('#sessionContainer').animate({top:0}, 800);
}

function pushMainContainer() {
	jQuery('#sessionContainer').animate({top:"-200%"}, 800);
}

function pullLogin() {
	jQuery('#main').show().animate({top:0}, 800);
}

function pushLogin() {
	jQuery('#main').animate({top:"-200%"}, 800);
}


/* ------- Translate UI ------ */

function translateInterface(lang_) {
	jQuery.ajax({
			url: 'translate.php?differentiator='+Math.floor(Math.random()*50000)+'&lang='+lang_,
			type: 'GET',
			dataType: 'xml',
			success: function(xml) {
				if (xml == null)
					return;

				var items = {};
				var translations = xml.getElementsByTagName('translation');
				for (var i = 0; i < translations.length; i++) {
					items[translations[i].getAttribute('id')] = translations[i].getAttribute('string');
				}

				var js_translations = xml.getElementsByTagName('js_translation');
				for (var i = 0; i < js_translations.length; i++)
					i18n[js_translations[i].getAttribute('id')] = js_translations[i].getAttribute('string');

				applyTranslations(items);
			}
		}
	);
}

function applyTranslations(translations) {
	for(key in translations) {
		var value = translations[key];

		var obj = jQuery('#'+key+'_gettext')[0];
		if (! obj)
			continue;
		
		if (obj.nodeName.toLowerCase() == 'input')
			obj.value = value;
		else
			obj.innerHTML = value;
	}

	jQuery('#sessionmanager_host').attr('placeholder', i18n['sessionmanager_host_example']);
}

/* ------- Other ------ */

function confirmLogout() {
	var framework = window.ovd.framework; /* shorten names */
	var defaults = window.ovd.defaults;

	var confirm_mode = defaults.confirm_logout;
	var running_apps = framework.listeners.application_counter.get();

	if(confirm_mode == 'always' || (confirm_mode == 'apps_only' && running_apps > 0)) {
		/* Ask confirmation */
		if(confirm(i18n['want_logout'].replace('#', running_apps)))
			framework.session_management.stop();
	} else {
		/* Logout without asking */
		framework.session_management.stop();
	}
}

function getWebClientBaseURL() {
	/*
		Using any window.location.href as:
		  * http://host/ovd/
		  * http://host/ovd/index.php
		  * http://host/ovd/external.php?args1=val/ue1
		Return: http://host/ovd/
	*/
	var url = window.location.href;
	url = url.replace(/\?[^\?]*$/, "");
	return url.replace(/\/[^\/]*$/, "")+"/";
}

function set_component_orientation(language_) {
	var rtl_languages = ["ar", "he", "fa", "ur", "yi", "dv"];
	var lang = language_.substr(0,2).toLowerCase();
	
	orientation = "ltr";
	for(var i=0; i<rtl_languages.length; i++) {
		if (lang == rtl_languages[i]) {
			orientation = "rtl";
			break;
		}
	}
	
	document.dir = orientation;
}
