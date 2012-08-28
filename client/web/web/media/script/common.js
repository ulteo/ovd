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
	new Effect.Fade($('splashContainer'));
}

function showSplash() {
	new Effect.Appear($('splashContainer'));
}

function hideEnd() {
	new Effect.Fade($('endContainer'));
}

function showEnd() {
	if ($('endContainer').visible())
		return;

	if ($('loginBox') && $('loginBox').visible())
		return;

	new Effect.Appear($('endContainer'));
}

function offContent(container) {
	$(container+'_ajax').innerHTML = '<img src="media/image/show.png" width="9" height="9" alt="+" title="" />';
	$(container+'_content').hide();

	return true;
}

function onContent(container) {
	$(container+'_ajax').innerHTML = '<img src="media/image/hide.png" width="9" height="9" alt="-" title="" />';
	$(container+'_content').show();

	return true;
}

function toggleContent(container) {
	if ($(container+'_content').visible())
		offContent(container);
	else
		onContent(container);

	return true;
}

Event.observe(window, 'load', function() {
	refresh_body_size();

	$('lockWrap').hide();
	$('lockWrap').style.width = my_width+'px';
	$('lockWrap').style.height = my_height+'px';

	$('errorWrap').hide();
	$('okWrap').hide();
	$('infoWrap').hide();

	Event.observe(window, 'keypress', function() {
		if ($('errorWrap').visible())
			hideError();
	});

	Event.observe($('lockWrap'), 'click', function() {
		if ($('errorWrap').visible())
			hideError();

		if ($('okWrap').visible())
			hideOk();

		if ($('infoWrap').visible())
			hideInfo();
	});

	if ($('lockWrap')) {
		Event.observe(window, 'resize', function() {
			if ($('lockWrap').visible()) {
				refresh_body_size();

				$('lockWrap').style.width = my_width+'px';
				$('lockWrap').style.height = my_height+'px';
			}
		});
	}

	if ($('desktopAppletContainer')) {
		Event.observe(window, 'resize', function() {
			if ($('desktopAppletContainer').visible()) {
				new Effect.Center($('desktopAppletContainer'));

				if (Logger.has_instance())
					new Effect.Move($('desktopAppletContainer'), { x: 0, y: -75, duration: 0.01 });
			}
		});
	}
});

function showLock() {
	refresh_body_size();

	if (! $('lockWrap').visible()) {
		$('lockWrap').style.width = my_width+'px';
		$('lockWrap').style.height = my_height+'px';

		$('lockWrap').show();
	}
}

function hideLock() {
	if ($('lockWrap').visible() && (! $('errorWrap').visible() && ! $('okWrap').visible() && ! $('infoWrap').visible())) {
		if ($('user_password') && $('user_password').visible() && $('user_password').disabled == false) {
			$('user_password').value = '';
		}
		$('lockWrap').hide();
	}
}

function showError(errormsg) {
	hideError();

	hideOk();
	hideInfo();

	showLock();

	$('errorWrap').innerHTML = '<div style="width: 16px; height: 16px; float: right;"><a href="javascript:;" onclick="hideError(); return false;"><img src="media/image/cross.png" width="16" height="16" alt="" title="" /></a></div>'+errormsg;
	$('errorWrap').style.padding = '10px';

	new Effect.Center($('errorWrap'));
	var elementDimensions = Element.getDimensions($('errorWrap'));
	$('errorWrap').style.width = elementDimensions.width+'px';

	new Effect.Appear($('errorWrap'));

	Nifty('div#errorWrap');
}

function hideError() {
	$('errorWrap').hide();

	hideLock();

	$('errorWrap').innerHTML = '';
	$('errorWrap').style.width = '';
	$('errorWrap').style.height = '';
}

function showOk(okmsg) {
	hideOK();

	hideError();
	hideInfo();

	showLock();

	$('okWrap').innerHTML = '<div style="width: 16px; height: 16px; float: right;"><a href="javascript:;" onclick="hideOk(); return false;"><img src="media/image/cross.png" width="16" height="16" alt="" title="" /></a></div>'+okmsg;
	$('okWrap').style.padding = '10px';

	new Effect.Center($('okWrap'));
	var elementDimensions = Element.getDimensions($('okWrap'));
	$('okWrap').style.width = elementDimensions.width+'px';

	new Effect.Appear($('okWrap'));

	Nifty('div#okWrap');

	setTimeout(function() {
		hideOk();
	}, 5000);
}

function hideOk() {
	$('okWrap').hide();

	hideLock();

	$('okWrap').innerHTML = '';
	$('okWrap').style.width = '';
	$('okWrap').style.height = '';
}

function showInfo(infomsg) {
	hideInfo();

	hideError();
	hideOk();

	showLock();

	$('infoWrap').innerHTML = '<div style="width: 16px; height: 16px; float: right;"><a href="javascript:;" onclick="hideInfo(); return false;"><img src="media/image/cross.png" width="16" height="16" alt="" title="" /></a></div>'+infomsg;
	$('infoWrap').style.padding = '10px';

	new Effect.Center($('infoWrap'));
	var elementDimensions = Element.getDimensions($('infoWrap'));
	$('infoWrap').style.width = elementDimensions.width+'px';

	new Effect.Appear($('infoWrap'));

	Nifty('div#infoWrap');
}

function hideInfo() {
	$('infoWrap').hide();

	hideLock();

	$('infoWrap').innerHTML = '';
	$('infoWrap').style.width = '';
	$('infoWrap').style.height = '';
}

function translateInterface(lang_) {
	new Ajax.Request(
		'translate.php',
		{
			method: 'get',
			parameters: {
				lang: lang_
			},
			onSuccess: function(transport) {
				var xml = transport.responseXML;
				if (xml == null)
					return;

				var translations = xml.getElementsByTagName('translation');
				for (var i = 0; i < translations.length; i++) {
					var obj = $(translations[i].getAttribute('id')+'_gettext');
					if (! obj)
						continue;

					if (obj.nodeName.toLowerCase() == 'input')
						obj.value = translations[i].getAttribute('string');
					else
						obj.innerHTML = translations[i].getAttribute('string');
				}

				var js_translations = xml.getElementsByTagName('js_translation');
				for (var i = 0; i < js_translations.length; i++)
					i18n.set(js_translations[i].getAttribute('id'), js_translations[i].getAttribute('string'));
				
				if (typeof window.updateSMHostField == 'function')
					updateSMHostField();
			}
		}
	);
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
