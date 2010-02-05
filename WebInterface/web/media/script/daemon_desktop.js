/**
 * Copyright (C) 2009-2010 Ulteo SAS
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

var Desktop = Class.create(Daemon, {
	mode: 'desktop',
	access_id: 'desktop',

	parse_do_started: function(transport) {
		$('splashContainer').hide();

		var applet_html_string = '<applet id="ulteoapplet" name="ulteoapplet" code="'+this.applet_main_class+'" codebase="applet/" archive="getopt-signed.jar,log4j-signed.jar,'+this.applet_version+'" cache_archive="getopt-signed.jar,log4j-signed.jar,'+this.applet_version+'" cache_archive_ex="getopt-signed.jar,log4j-signed.jar,'+this.applet_version+';preload" mayscript="true" width="'+this.my_width+'" height="'+this.my_height+'"> \
			<param name="name" value="ulteoapplet" /> \
			<param name="code" value="'+this.applet_main_class+'" /> \
			<param name="codebase" value="applet/" /> \
			<param name="archive" value="getopt-signed.jar,log4j-signed.jar,'+this.applet_version+'" /> \
			<param name="cache_archive" value="getopt-signed.jar,log4j-signed.jar,'+this.applet_version+'" /> \
			<param name="cache_archive_ex" value="getopt-signed.jar,log4j-signed.jar,'+this.applet_version+';preload" /> \
			<param name="mayscript" value="true" /> \
			\
			<param name="server" value="'+this.session_server+'" /> \
			<param name="port" value="3389" /> \
			<param name="username" value="'+this.session_login+'" /> \
			<param name="password" value="'+this.session_password+'" /> \
		</applet>';

		$('desktopAppletContainer').show();
		$('desktopAppletContainer').innerHTML = applet_html_string;

		return true;
	}
});
