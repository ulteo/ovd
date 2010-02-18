/**
 * Copyright (C) 2010 Ulteo SAS
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

var Server = Class.create({
	id: '',
	fqdn: '',
	port: 0,
	username: '',
	password: '',

	connected: false,
	ready: false,

	initialize: function(id_, java_id_, fqdn_, port_, username_, password_) {
		this.id = id_;
		this.java_id = java_id_;
		this.fqdn = fqdn_;
		this.port = port_;
		this.username = username_;
		this.password = password_;
	},

	connect: function() {
		if (this.connected)
			return true;

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

			var session_status = daemon.get_session_status();
			if (session_status == 'unknown')
				daemon.do_ended();
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
