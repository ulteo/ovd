/**
 * Copyright (C) 2010-2012 Ulteo SAS
 * http://www.ulteo.com
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2010-2011
 * Author Julien LANGLOIS <julien@ulteo.com> 2011, 2012
 * Author Omar AKHAM <oakham@ulteo.com> 2011
 * Author Jocelyn DELALALANDE <j.delalande@ulteo.com> 2012
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

function hideSplash() {
	new Effect.Fade(jQuery('#splashContainer')[0]);
}

function showSplash() {
	new Effect.Appear(jQuery('#splashContainer')[0]);
}

function hideEnd() {
	new Effect.Fade(jQuery('#endContainer')[0]);
}

function showEnd() {
	if (jQuery('#endContainer')[0].visible())
		return;

	if (jQuery('#loginBox')[0] && jQuery('#loginBox')[0].visible())
		return;

	new Effect.Appear(jQuery('#endContainer')[0]);
}

function offContent(container) {
	jQuery('#'+container+'_ajax').html('<img src="media/image/show.png" width="9" height="9" alt="+" title="" />');
	jQuery('#'+container+'_content').hide();

	return true;
}

function onContent(container) {
	jQuery('#'+container+'_ajax').html('<img src="media/image/hide.png" width="9" height="9" alt="-" title="" />');
	jQuery('#'+container+'_content').show();

	return true;
}

function toggleContent(container) {
	if (jQuery('#'+container+'_content')[0].visible())
		offContent(container);
	else
		onContent(container);

	return true;
}

Event.observe(window, 'load', function() {
	refresh_body_size();

	jQuery('#lockWrap').hide();
	jQuery('#lockWrap').width(my_width);
	jQuery('#lockWrap').height(my_height);

	jQuery('#errorWrap').hide();
	jQuery('#okWrap').hide();
	jQuery('#infoWrap').hide();

	Event.observe(window, 'keypress', function() {
		if (jQuery('#errorWrap')[0].visible())
			hideError();
	});

	Event.observe(jQuery('#lockWrap')[0], 'click', function() {
		if (jQuery('#errorWrap')[0].visible())
			hideError();

		if (jQuery('#okWrap')[0].visible())
			hideOk();

		if (jQuery('#infoWrap')[0].visible())
			hideInfo();
	});

	if (jQuery('#lockWrap')[0]) {
		Event.observe(window, 'resize', function() {
			if (jQuery('#lockWrap')[0].visible()) {
				refresh_body_size();

				jQuery('#lockWrap').width(my_width);
				jQuery('#lockWrap').height(my_height);
			}
		});
	}

	if (jQuery('#desktopContainer')[0]) {
		Event.observe(window, 'resize', function() {
			if (jQuery('#desktopContainer')[0].visible()) {
				new Effect.Center(jQuery('#desktopContainer')[0]);

				if (Logger.has_instance())
					new Effect.Move(jQuery('#desktopContainer')[0], { x: 0, y: -75, duration: 0.01 });
			}
		});
	}
});

function showLock() {
	refresh_body_size();

	if (! jQuery('#lockWrap')[0].visible()) {
		jQuery('#lockWrap').width(my_width);
		jQuery('#lockWrap').height(my_height);

		jQuery('#lockWrap').show();
	}
}

function hideLock() {
	if (jQuery('#lockWrap')[0].visible() && (! jQuery('#errorWrap')[0].visible() && ! jQuery('#okWrap')[0].visible() && ! jQuery('#infoWrap')[0].visible())) {
		if (jQuery('#user_password')[0] && jQuery('#user_password')[0].visible() && jQuery('#user_password')[0].disabled == false) {
			jQuery('#user_password').prop('value','');
		}
		jQuery('#lockWrap').hide();
	}
}

function showError(errormsg) {
	hideError();

	hideOk();
	hideInfo();

	showLock();

	jQuery('#errorWrap').html('<div style="width: 16px; height: 16px; float: right;"><a href="javascript:;" onclick="hideError(); return false;"><img src="media/image/cross.png" width="16" height="16" alt="" title="" /></a></div>'+errormsg);
	jQuery('#errorWrap').css('padding', '10px');

	new Effect.Center(jQuery('#errorWrap')[0]);
	var elementDimensions = Element.getDimensions(jQuery('#errorWrap')[0]);
	jQuery('#errorWrap').width(elementDimensions.width);

	new Effect.Appear(jQuery('#errorWrap')[0]);

	Nifty('div#errorWrap');
}

function hideError() {
	jQuery('#errorWrap').hide();

	hideLock();

	jQuery('#errorWrap').html('');
	jQuery('#errorWrap').width('');
	jQuery('#errorWrap').height('');
}

function showOk(okmsg) {
	hideOK();

	hideError();
	hideInfo();

	showLock();

	jQuery('#okWrap').html('<div style="width: 16px; height: 16px; float: right;"><a href="javascript:;" onclick="hideOk(); return false;"><img src="media/image/cross.png" width="16" height="16" alt="" title="" /></a></div>'+okmsg);
	jQuery('#okWrap').css('padding', '10px');

	new Effect.Center(jQuery('#okWrap')[0]);
	var elementDimensions = Element.getDimensions(jQuery('#okWrap')[0]);
	jQuery('#okWrap').width(elementDimensions.width);

	new Effect.Appear(jQuery('#okWrap')[0]);

	Nifty('div#okWrap');

	setTimeout(function() {
		hideOk();
	}, 5000);
}

function hideOk() {
	jQuery('#okWrap').hide();

	hideLock();

	jQuery('#okWrap').html('');
	jQuery('#okWrap').width('');
	jQuery('#okWrap').height('');
}

function showInfo(infomsg) {
	hideInfo();

	hideError();
	hideOk();

	showLock();

	jQuery('#infoWrap').html('<div style="width: 16px; height: 16px; float: right;"><a href="javascript:;" onclick="hideInfo(); return false;"><img src="media/image/cross.png" width="16" height="16" alt="" title="" /></a></div>'+infomsg);
	jQuery('#infoWrap').css('padding', '10px');

	new Effect.Center(jQuery('#infoWrap')[0]);
	var elementDimensions = Element.getDimensions(jQuery('#infoWrap')[0]);
	jQuery('#infoWrap').width(elementDimensions.width);

	new Effect.Appear(jQuery('#infoWrap')[0]);

	Nifty('div#infoWrap');
}

function hideInfo() {
	jQuery('#infoWrap').hide();

	hideLock();

	jQuery('#infoWrap').html('');
	jQuery('#infoWrap').width('');
	jQuery('#infoWrap').height('');
}

function translateInterface(lang_) {
	jQuery.ajax({
			url: 'translate.php?differentiator='+Math.floor(Math.random()*50000)+'&lang='+lang_,
			type: 'GET',
			dataType: 'xml',
			success: function(xml) {
				if (xml == null)
					return;

				var items = new Hash();
				var translations = xml.getElementsByTagName('translation');
				for (var i = 0; i < translations.length; i++) {
					items.set(translations[i].getAttribute('id'), translations[i].getAttribute('string'));
				}

				applyTranslations(items);
				
				var js_translations = xml.getElementsByTagName('js_translation');
				for (var i = 0; i < js_translations.length; i++)
					i18n.set(js_translations[i].getAttribute('id'), js_translations[i].getAttribute('string'));
			}
		}
	);
}

function applyTranslations(translations) {
	translations.each(function(pair) {
		var obj = jQuery('#'+pair.key+'_gettext')[0];
		if (! obj)
			return;
		
		if (obj.nodeName.toLowerCase() == 'input')
			obj.value = pair.value;
		else
			obj.innerHTML = pair.value;
	});
	
	if (typeof window.updateSMHostField == 'function')
		updateSMHostField();
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
