/**
 * Copyright (C) 2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Jeremy DESVAGES <jeremy@ulteo.com>
 * Author Julien LANGLOIS <julien@ulteo.com>
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

var UlteoOVD_start_Application = Class.create({
	web_client_url: null,
	application_id: null,
	path: null,
	extra_args: new Hash(),

	initialize: function(web_client_url_, application_id_) {
		this.web_client_url = web_client_url_;
		this.application_id = application_id_;
	},

	setAuthPassword: function(login_, password_) {
		this.extra_args.set('login', login_);
		this.extra_args.set('password', password_);
	},

	setAuthToken: function(token_) {
		this.extra_args.set('token', token_);
	},

	setPath: function(path_) {
		this.path = path_;
	},

	start: function() {
		var url = this.web_client_url+'/external.php?app='+this.application_id;
		if (this.path != null)
			url += '&path='+this.path;

		var extra_args = this.extra_args.keys();
		for (var i=0; i<extra_args.length; i++)
			url += '&'+extra_args[i]+'='+this.extra_args.get(extra_args[i]);

		this.openPopup(url);
	},

	openPopup: function(url_) {
		var my_width = 436;
		var my_height = 270;
		var new_width = 0;
		var new_height = 0;
		var pos_top = screen.height - 270;
		var pos_left = screen.width - 436;

		var date = new Date();
		var rand_ = Math.round(Math.random()*100)+date.getTime();

		var w = window.open(url_, 'Ulteo'+rand_, 'toolbar=no,status=no,top='+pos_top+',left='+pos_left+',width='+my_width+',height='+my_height+',scrollbars=no,resizable=no,resizeable=no,fullscreen=no');

		return true;
	}
});
