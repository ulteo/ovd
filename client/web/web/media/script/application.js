/**
 * Copyright (C) 2009-2012 Ulteo SAS
 * http://www.ulteo.com
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2009-2010
 * Author Julien LANGLOIS <julien@ulteo.com> 2009-2010
 * Author David PHAM-VAN <d.pham-van@ulteo.com> 2012
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

var Application = Class.create({
	id: 0,
	name: '',
	server_id: 0,

	initialize: function(id_, name_, server_id_) {
		this.id = id_;
		this.name = name_;
		this.server_id = server_id_;
	},

	launch: function() {
		var server = daemon.servers.get(this.server_id);
		$('ulteoapplet').startApplication(++daemon.application_token, this.id, server.java_id);
		daemon.liaison_runningapplicationtoken_application.set(daemon.application_token, this.id);
	},

	launch_with_file: function(type_, path_, share_) {
		var server = daemon.servers.get(this.server_id);
		$('ulteoapplet').startApplicationWithFile(++daemon.application_token, this.id, server.java_id, type_, path_, share_);
		daemon.liaison_runningapplicationtoken_application.set(daemon.application_token, this.id);
	}
});

var Running_Application = Class.create(Application, {
	pid: '',
	status: -1,

	initialize: function(id_, name_, server_id_, pid_, status_) {
		Application.prototype.initialize.apply(this, [id_, name_, server_id_]);

		this.pid = pid_;
		this.status = status_;
	},

	update: function(status_) {
		if (status_ != this.status) {
			this.status = status_;
		}
	}
});

var ApplicationsPanel = Class.create({
	node: null,
	applications: null,

	initialize: function(node_) {
		node_.innerHTML = '';

		var table = new Element('table');
		var tbody = new Element('tbody');
		table.appendChild(tbody);
		node_.appendChild(table);

		this.applications = new Array();
		this.node = tbody;
 	},

	compare: function(a, b) {
		if (a.name < b.name)
			return -1;
		if (a.name > b.name)
			return 1;

		return 0;
	},

	add: function(app_) {
		this.applications.push(app_);
		this.applications.sort(this.compare);

		for (var i = 0; i < this.applications.length; i++) {
			var app = this.applications[i];
			if (app_ != app)
				continue;

			if (i+1 == this.applications.length)
				this.node.appendChild(app.getNode());
			else {
				var nextApp = this.applications[i+1];
				this.node.insertBefore(app.getNode(), nextApp.getNode());
			}
		}
	},

	del: function(app_) {
		this.applications = this.applications.without(app_);
		try {
			this.node.removeChild(app_.getNode());
		} catch(e) {}
	}
});

var ApplicationItem = Class.create({
	application: null,
	node: null,

	app_span: null,

	initialize: function(application_) {
		this.application = application_;
	},

	on_server_status_change: function(server_, status_) {
		this.repaintNode();
	},

	initNode: function() {
		var tr = new Element('tr');

		var td_icon = new Element('td');
		var icon = new Element('img');
		icon.setAttribute('src', this.getIconURL());
		icon.setAttribute('width', 32);
		td_icon.appendChild(icon);
		tr.appendChild(td_icon);

		var td_app = new Element('td');
		this.app_span = new Element('span');
		td_app.appendChild(this.app_span);
		tr.appendChild(td_app);

		var td_running = new Element('td');
		td_running.setAttribute('id', 'running_'+this.application.id);
		td_running.setAttribute('style', 'width: 15px; text-align: right; font-size: 0.9em; font-style: italic;');
		tr.appendChild(td_running);

		this.repaintNode();

		return tr;
	},

	repaintNode: function() {
		this.app_span.innerHTML = '';

		var server = daemon.servers.get(this.application.server_id);

		if (server.ready) {
			var node = new Element('a');
			node.observe('click', this.onClick.bind(this));
			node.setAttribute('href', 'javascript:;');
			node.innerHTML = this.application.name;
			this.app_span.appendChild(node);

			this.app_span.parentNode.parentNode.setAttribute('style', 'opacity: 1.00; filter: alpha(opacity=100); -moz-opacity: 1.00;');
		} else {
			var node = new Element('span');
			node.setAttribute('style', 'font-weight: bold;');
			node.innerHTML = this.application.name;
			this.app_span.appendChild(node);

			this.app_span.parentNode.parentNode.setAttribute('style', 'opacity: 0.40; filter: alpha(opacity=40); -moz-opacity: 0.40;');
		}

		return true;
	},

	getNode: function() {
		if (this.node == null)
			this.node = this.initNode();

		return this.node;
	},

 	onClick: function(event) {
		this.application.launch();
		event.stop();
	},

	getIconURL: function() {
		return 'icon.php?id='+this.application.id;
	}
});
