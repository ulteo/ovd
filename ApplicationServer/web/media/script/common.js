/**
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Jeremy DESVAGES <jeremy@ulteo.com>
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

function clicMenu(oDiv) {
	if ($(oDiv).visible() != 1)
		_openMenuItem(oDiv);
	else
		_closeMenuItem(oDiv);
}

function _openMenuItem(oDiv) {
	$('appletContainer').style.visibility = 'hidden';
	$(oDiv).show();
}

function _closeMenuItem(oDiv) {
	$(oDiv).hide();
	$('appletContainer').style.visibility = 'visible';
}

function showLock() {
	if (!$('lockWrap').visible()) {
		$('lockWrap').style.width = document.body.clientWidth+'px';
		$('lockWrap').style.height = document.body.clientHeight+'px';

		$('lockWrap').show();
	}
}

function hideLock() {
	if ($('lockWrap').visible())
		$('lockWrap').hide();
}

function showError(errormsg) {
	hideOk();
	hideInfo();

	showLock();

	$('errorWrap').innerHTML = '<div style="width: 16px; height: 16px; float: right"><a href="javascript:;" onclick="hideError(); return false"><img src="../media/image/close.png" width="16" height="16" alt="fermer" title="Fermer" /></a></div>'+errormsg;

	Effect.Center($('errorWrap'));

	Effect.Appear($('errorWrap'));
}

function hideError() {
	$('errorWrap').hide();

	hideLock();

	$('errorWrap').innerHTML = '';
	$('errorWrap').style.width = '';
	$('errorWrap').style.height = '';
}

function showOk(okmsg) {
	hideInfo();

	showLock();

	$('okWrap').innerHTML = '<div style="width: 16px; height: 16px; float: right"><a href="javascript:;" onclick="hideOk(); return false"><img src="../media/image/close.png" width="16" height="16" alt="fermer" title="Fermer" /></a></div>'+okmsg;

	Effect.Center($('okWrap'));

	Effect.Appear($('okWrap'));
}

function hideOk() {
	$('okWrap').hide();

	hideLock();

	$('okWrap').innerHTML = '';
	$('okWrap').style.width = '';
	$('okWrap').style.height = '';
}

function showInfo(infomsg) {
	showLock();

	$('infoWrap').innerHTML = '<div style="width: 16px; height: 16px; float: right"><a href="javascript:;" onclick="hideInfo(); return false"><img src="../media/image/close.png" width="16" height="16" alt="fermer" title="Fermer" /></a></div>'+infomsg;

	Effect.Center($('infoWrap'));

	Effect.Appear($('infoWrap'));
}

function hideInfo() {
	$('infoWrap').hide();

	hideLock();

	$('infoWrap').innerHTML = '';
	$('infoWrap').style.width = '';
	$('infoWrap').style.height = '';
}

Event.observe(window, 'load', function() {
	Effect.Center($('splashContainer'));
	Effect.Center($('endContainer'));
	$('endContainer').style.top = parseInt($('endContainer').style.top)-50+'px';

	$('appletContainer').hide();
	$('splashContainer').show();
});
