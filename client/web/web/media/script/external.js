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

var UlteoOVD_start_External = Class.create({
	mode: null,

	web_client_url: null,
	application_id: null,
	path: null,
	extra_args: new Hash(),

	my_width: screen.width,
	my_height: screen.height,
	pos_top: 0,
	pos_left: 0,

	initialize: function(web_client_url_) {
		this.web_client_url = web_client_url_;
	},

	setLanguage: function(language_) {
		this.extra_args.set('language', language_);
	},

	setAuthPassword: function(login_, password_) {
		this.extra_args.set('login', login_);
		this.extra_args.set('password', password_);
	},

	setAuthToken: function(token_) {
		this.extra_args.set('token', token_);
	},

	start: function() {
		var url = this.web_client_url+'/external.php?mode='+this.mode;
		if (this.application_id != null)
			url += '&app='+this.application_id;
		if (this.path != null)
			url += '&path='+this.path;

		var extra_args = this.extra_args.keys();
		for (var i=0; i<extra_args.length; i++)
			url += '&'+extra_args[i]+'='+this.extra_args.get(extra_args[i]);

		this.openPopup(url);
	},

	openPopup: function(url_) {
		var date = new Date();
		var rand = Math.round(Math.random()*100)+date.getTime();

		var w = window.open(url_, 'Ulteo'+rand, 'width='+this.my_width+',height='+this.my_height+',top='+this.pos_top+',left='+this.pos_left+',toolbar=no,status=no,scrollbars=no,resizable=no,resizeable=no,fullscreen=no');

		return true;
	}
});

var UlteoOVD_start_Desktop = Class.create(UlteoOVD_start_External, {
	mode: 'desktop',

	my_width: screen.width,
	my_height: screen.height,
	pos_top: 0,
	pos_left: 0
});

var UlteoOVD_start_Application = Class.create(UlteoOVD_start_External, {
	mode: 'applications',

	my_width: 436,
	my_height: 270,
	pos_top: (screen.height-270),
	pos_left: (screen.width-436),

	initialize: function(web_client_url_, application_id_) {
		UlteoOVD_start_External.prototype.initialize.apply(this, [web_client_url_]);

		this.application_id = application_id_;
	},

	setPath: function(path_) {
		this.extra_args.set('file', path_);
		this.extra_args.set('file_type', 'native');
	},

	setPathHTTP: function(url_, path_) {
		this.extra_args.set('file', path_);
		this.extra_args.set('file_share', url_);
		this.extra_args.set('file_type', 'http');
	}
});
