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

var testDone = false;

function popupOpen(rand_) {
	var my_width = screen.width;
	var my_height = screen.height;
	var new_width = 0;
	var new_height = 0;
	var pos_top = 0;
	var pos_left = 0;

	//$('user_password').value = $('password').value;

	if ($('desktop_size').value == 'auto') {
		new_width = my_width;
		new_height = my_height;
	} else {
		buf = string2resol($('desktop_size').value);
		new_width = buf['width'];
		new_height = buf['height'];

		pos_top = ((screen.availHeight-new_height)/2);
		pos_left = ((screen.availWidth-new_width)/2);
	}

	var w = window.open('about:blank', 'Ulteo'+rand_, 'toolbar=no,status=no,top='+pos_top+',left='+pos_left+',width='+new_width+',height='+new_height+',scrollbars=no,resizable=no,resizeable=no,fullscreen=no');

	if ($('session_debug_false').checked)
		sessionStart();

	return w;
}

var resol_standard = {
	'4/3': ['1280x1024', '1152x864', '1024x768', '800x600'],
	'16/10': ['1440x900', '1280x800', '960x600']
};

function string2resol(str) {
	if (typeof(str) != 'string')
		return null;

	var buf = str.split('x');
	if (buf.length != 2)
		return null;

	return {'width': buf[0], 'height': buf[1]};
}

function isScreenProportion(prop) {
	var buf = prop.split('/');
	if (buf.length != 2)
		return false;
	var x = parseInt(buf[0]);
	var y = parseInt(buf[1]);

	return x*screen.height/screen.width == y;
}

function getAvailableResol() {
	for (i in resol_standard) {
		if (isScreenProportion(i))
			return resol_standard[i];
	}

	return resol_standard['4/3'];
}

function setAvailableSize(select_id) {
	var res = getAvailableResol();

	for (var i = 0; i < res.length; i++) {
		var r = string2resol(res[i]);

		if (r == null)
			return;

		if (typeof(res[i]) != 'string')
			return;

		if (typeof(r['width']) != 'string')
			return;

		if (typeof(r['height']) != 'string')
			return;

		//if (r != null && r['x'] < screen.width && r['y'] < screen.height) {
		if (r != null && r['width'] <= screen.availWidth && r['height'] <= screen.availHeight) {
			if ($(select_id)) {
				try {
					var buf = document.createElement('option');
					buf.setAttribute('value', res[i]);
					buf.innerHTML = res[i];
					$(select_id).appendChild(buf);
				} catch(e) {
					$(select_id).innerHTML += '<option value="'+res[i]+'">'+res[i]+'</option>';
				}
			}
		}
	}
}

var ulteo_applet_inited = null;
function appletInited(status_) {
	ulteo_applet_inited = status_;
}

function appletLoaded() {
	if (!testDone) {
		testDone = true;

		$('loading_div').style.display = 'none';
		//$('loading_button').style.display = 'none';
		$('launch_button').style.display = 'block';
	}
}

function testFailed(failCode) {
	if (!testDone) {
		testDone = true;

		$('loading_div').style.display = 'none';
		//$('loading_button').style.display = 'none';

		if (failCode == 1) {
			$('failed_button').value += ' (err01 no java)';
		} else if (failCode == 2) {
			$('failed_button').value += ' (err02 browser)';
		} else if (failCode == 3) {
			$('failed_button').value += ' (err03 firewall)';
		} else if (failCode == 4) {
			$('failed_button').value += ' (err04 java timed out)';
		} else if (failCode == 5) {
			$('failed_button').value += ' (err05 java failed)';
		} else if (failCode == -1) {
			$('failed_button').value += ' (err-1 sessions)';
		}

		$('failed_button').style.display = 'block';
	}
}

function badPing() { //errCode
	if (!testDone) {
		testDone = true;

		$('loading_div').style.display = 'none';
		//$('loading_button').style.display = 'none';

		/*if (errCode == 1) {
			$('warn_button').value += ' (warn01 ping)';
		} else if (errCode == 2) {
			$('warn_button').value += ' (warn02 ping)';
		}*/
		$('warn_button').value += ' (warn ping)';

		$('warn_button').style.display = 'block';
	}
}

function sessionLock() {
	if (!testDone) {
		testDone = true;

		$('loading_div').style.display = 'none';
		//$('loading_button').style.display = 'none';
		$('lock_button').style.display = 'block';
	}
}

function sessionStart() {
	if (testDone) {
		$('launch_button').style.display = 'none';
		$('failed_button').style.display = 'none';
		$('warn_button').style.display = 'none';
		$('started_button').style.display = 'block';
	}
}

function sessionStop() {
	testDone = false;
	$('launch_button').style.display = 'block';
	$('failed_button').style.display = 'none';
	$('warn_button').style.display = 'none';
	$('started_button').style.display = 'none';
}
