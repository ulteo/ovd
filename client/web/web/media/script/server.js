/**
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Jeremy DESVAGES <jeremy@ulteo.com>
 * Author Jocelyn DELALALANDE <j.delalande@ulteo.com> 2012
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


/** Represents a RDP server
 *
 * Manages authentication against server. There are two auth modes available : 
 *   - login+password
 *   - token
 */
var Server = Class.create({
	id: '',
	java_id: '',
	xml: null,

	fqdn: '',
	port: 0,
	token: null,
	username: '',
	password: '',

	connected: false,
	ready: false,

	/** Constructor : initialized with a <server> XML node.
	 *
	 * @param id_       Index of the server in servers list
	 * @param java_id_  Same (?)
	 * @param xml_      Credentials and params supplied by the webclient server, to 
   *                  be provided to the RDP applet for RDP auth.
	 */
	initialize: function(id_, java_id_, xml_) {
		this.id = id_;
		this.java_id = java_id_;
		this.xml = xml_;

		this.fqdn = xml_.getAttribute('fqdn');
		this.port = xml_.getAttribute('port');
		this.username = xml_.getAttribute('login');
		this.password = xml_.getAttribute('password');
	},

	/// In case we use the token auth.
	setToken: function(token_) {
		this.token = token_;
	},

	connect: function() {
		if (this.connected)
			return true;

		if (daemon.mode == 'applications') {
			var serialized;

			try {
				// XMLSerializer exists in current Mozilla browsers
				serializer = new XMLSerializer();
				serialized = serializer.serializeToString(this.xml);
			} catch (e) {
				// Internet Explorer has a different approach to serializing XML
				serialized = this.xml.xml;
			}

			$('ulteoapplet').serverPrepare(this.java_id, serialized);
		}

		if (this.token != null)
			$('ulteoapplet').serverConnect(this.java_id, this.fqdn, this.port, this.token, this.username, this.password);
		else
			$('ulteoapplet').serverConnect(this.java_id, this.fqdn, this.port, this.username, this.password);

		return true;
	},

	disconnect: function() {
		if (! this.connected)
			return true;

		$('ulteoapplet').serverDisconnect(this.java_id, this.fqdn, this.port, this.username, this.password);

		return true;
	},

	setStatus: function(status_) {
		if (status_ == 'connected')
			this.connected = true;
		else if (this.connected && status_ == 'ready')
			this.ready = true;
		else if (status_ == 'disconnected') {
			this.ready = false;
			this.connected = false;

			daemon.break_loop();
			daemon.sessionmanager_request_time = 2000;
			daemon.loop();

			if (daemon.mode == 'desktop' && ! daemon.is_stopped())
				daemon.logout();
		} else if (status_ == 'failed') {
			this.ready = false;
			this.connected = false;

			daemon.logout();
		}

		var applications = daemon.liaison_server_applications.get(this.id);
		for (var i=0; i < applications.length; i++) {
			var application = daemon.applications.get(applications[i]);
			application.update();
		}

		return true;
	}
});

function serverStatus(java_id_, status_) {
	var servers = daemon.servers.values();
	for (var i=0; i < servers.length; i++)
		if (servers[i].java_id == java_id_)
			return servers[i].setStatus(status_);

	return false;
}
