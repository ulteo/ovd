/**
 * Copyright (C) 2011 Ulteo SAS
 * http://www.ulteo.com
 * Author julien LANGLOIS <julien@ulteo.com>
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


function UlteoOVD_start_Application(web_client_url, application_id) {
	var url = web_client_url+'/external.php?app='+application_id;
	
	_UlteoOVDpopupOpen(url);
}

function UlteoOVD_start_Application_with_file(web_client_url, application_id, path) {
	var url = web_client_url+'/external.php?app='+application_id+'path='+path;
	
	_UlteoOVDpopupOpen(url);
}

function _UlteoOVDpopupOpen(url) {
	var my_width = 300;
	var my_height = 200;
	var new_width = 0;
	var new_height = 0;
	var pos_top = screen.height - 200;
	var pos_left = screen.width - 300;
	
	var date = new Date();
	var rand_ = Math.round(Math.random()*100)+date.getTime();
	
	var w = window.open(url, 'Ulteo'+rand_, 'toolbar=no,status=no,top='+pos_top+',left='+pos_left+',width='+my_width+',height='+my_height+',scrollbars=no,resizable=no,resizeable=no,fullscreen=no');
	
	return true;
}
