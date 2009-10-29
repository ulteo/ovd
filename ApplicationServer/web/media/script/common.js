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

Event.observe(window, 'load', function() {
	Effect.Center($('splashContainer'));
	Effect.Center($('endContainer'));
	$('endContainer').style.top = parseInt($('endContainer').style.top)-50+'px';

	$('appletContainer').hide();
	$('splashContainer').show();
});
