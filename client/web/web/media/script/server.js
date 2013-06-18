/**
 * Copyright (C) 2010-2013 Ulteo SAS
 * http://www.ulteo.com
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2010
 * Author Jocelyn DELALALANDE <j.delalande@ulteo.com> 2012
 * Author Julien LANGLOIS <julien@ulteo.com> 2012
 * Author Wojciech LICHOTA <wojciech.lichota@stxnext.pl> 2013
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
	
	status_changed_callbacks: null, // Array

	/** Constructor : initialized with a <server> XML node.
	 *
	 * @param id_       Index of the server in servers list
	 * @param java_id_  Same (?)
	 * @param xml_      Credentials and params supplied by the webclient server, to 
   *                  be provided to the RDP applet for RDP auth.
	 */
	initialize: function(id_, java_id_, fqdn_, port_, username_, password_, xml_) {
		this.id = id_;
		this.java_id = java_id_;
		this.xml = xml_;

		this.fqdn = fqdn_;
		this.port = port_;
		this.username = username_;
		this.password = password_;
		
		this.status_changed_callbacks = new Array();
	},

	/// In case we use the token auth.
	setToken: function(token_) {
		this.token = token_;
	},

	connect: function() {
		if (this.connected)
			return true;

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
		else if (this.connected && status_ == 'ready') {
			this.ready = true;
		}
		else if (status_ == 'disconnected') {
			this.ready = false;
			this.connected = false;
		} else if (status_ == 'failed') {
			this.ready = false;
			this.connected = false;
		}

		for (var i=0; i < this.status_changed_callbacks.length; i++)
			this.status_changed_callbacks[i](this, status_);

		return true;
	},
	
	add_status_changed_callback: function(callback_) {
		this.status_changed_callbacks.push(callback_);
	}
});

Server.DEFAULT_RDP_PORT = 3389;

function serverStatus(java_id_, status_) {
	var servers = daemon.servers.values();
	for (var i=0; i < servers.length; i++)
		if (servers[i].java_id == java_id_)
			return servers[i].setStatus(status_);

	return false;
}

var WebappServer = Class.create({
	id: '',
	xml: null,

	base_url: '',
	server_url: '',
	token: null,
	username: '',
	password: '',

	connected: false,
	ready: false,
	
	status_changed_callbacks: null, // Array

	/** Constructor : initialized with a <server> XML node.
	 *
	 * @param id_       Index of the server in servers list
	 * @param base_url_ Communication url of webapp server
	 * @param username_ Credentials for loggin to webapp server
	 * @param password_ Credentials for loggin to webapp server
	 */
	initialize: function(id_, base_url_, server_url_, username_, password_) {
		this.id = id_;

		this.base_url = base_url_;
		this.server_url = server_url_;
		this.username = username_;
		this.password = password_;
		
		this.status_changed_callbacks = new Array();
	},

	connect: function() {
		if (this.connected)
			return true;
		Logger.debug('[server] connecting to ' + this.server_url);

		var tag = document.createElement('script'); tag.type = 'text/javascript'; tag.async = true;
		tag.src = this.server_url + '/connect?id=' + this.id + '&user=' + this.username + '&pass=' + this.password;
		var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(tag, s);

		this.setStatus('connected');
		return true;
	},


	disconnect: function() {
		if (! this.ready)
			return true;

		this.setStatus('disconnected');
		return true;
	},

	setStatus: function(status_) {
		if (status_ == 'connected') {
			this.connected = true;
		} else if (status_ == 'ready') {
			this.ready = true;
		} else if (status_ == 'disconnected') {
			this.ready = false;
		} else if (status_ == 'failed') {
			this.ready = false;
		}

		for (var i=0; i < this.status_changed_callbacks.length; i++)
			this.status_changed_callbacks[i](this, status_);

		return true;
	},
	
	add_status_changed_callback: function(callback_) {
		this.status_changed_callbacks.push(callback_);
	}
});

function webappServerStatus(id_, status_) {
	var servers = daemon.webapp_servers.values();
	for (var i=0; i < servers.length; i++){
		if (servers[i].id == id_) {
			Logger.debug('[server] update server id=' + id_ + ' with status ' + status_);
			return servers[i].setStatus(status_);
		}
	}

	return false;
}