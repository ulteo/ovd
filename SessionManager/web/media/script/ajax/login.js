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

var form_ = '';
var window_ = '';
var date = new Date();
var rand_ = Math.round(Math.random()*100)+date.getTime();

function doLogin(this_) {
	form_ = this_;

	$('launch_button').disabled = true;

	var user_passwd = false;
	if ($('login_password')) {
		user_passwd = $('login_password').value;
		$('login_password').value = '';
	}

	var ret = new Ajax.Request(
		'ajax/login.php',
		{
			method: 'post',
			parameters: {
				do_login: 1,
				login: $('login_login').value,
				password: user_passwd
			},
			asynchronous: false,
			onSuccess: onLoginSuccess,
			onFailure: onLoginFailure
		}
	);

	setTimeout(function() {
		$('launch_button').disabled = false;
	}, 1000);

	setTimeout(function() {
		$('login_status').innerHTML = '';
	}, 30000);

	if (parseInt(ret.getStatus()) != 200)
		return false;

	if ($('use_popup_true') && $('use_popup_true').checked)
		window_ = popupOpen(rand_);

	return true;
}

function onLoginSuccess(transport) {
	$('login_status').innerHTML = '<p class="msg_ok">'+transport.responseText+'</p>';

	if ($('use_popup_true') && $('use_popup_true').checked)
		$('startsession').target = 'Ulteo'+rand_;

	return true;
}

function onLoginFailure(transport) {
	$('login_status').innerHTML = '<p class="msg_error">'+transport.responseText+'</p>';

	sessionStop();

	window_.close();

	return false;
}
