function SeamlessLauncher(session_management, node) {
	this.node = jQuery(node);
	this.session_management = session_management;
	this.applications = {}; /* application id as index */
	this.content = {}; /* application id as index */
	this.handler = this.handleEvents.bind(this);

	/* Do NOT remove ovd.session.statusChanged in destructor as it is used as a delayed initializer */
	this.session_management.addCallback("ovd.session.statusChanged", this.handler);
}

SeamlessLauncher.prototype.handleEvents = function(type, source, params) {
	if(type == "ovd.session.statusChanged") {
		var from = params["from"];
		var to = params["to"];
		var session_type = this.session_management.parameters["session_type"];
		var session = source;

		if(to == "ready" && session_type == "applications") {
			/* register events listeners */
			this.session_management.addCallback("ovd.session.server.statusChanged",                  this.handler);
			this.session_management.addCallback("ovd.rdpProvider.applicationProvider.statusChanged", this.handler);
			this.session_management.addCallback("ovd.ajaxProvider.sessionEnd",                       this.handler);
			this.session_management.addCallback("ovd.ajaxProvider.sessionSuspend",                   this.handler);

			var servers = session.servers;

			/* Get application list */
			for(var i=0 ; i<servers.length ; ++i) {
				var server = servers[i];
				for(var j=0 ; j<server.applications.length ; ++j) {
					this.applications[server.applications[j].id] = server.applications[j];
				}
			}

			/* Create launchers */
			for(var id in this.applications) {
				var img = jQuery(document.createElement("img"));
				img.prop("src", "icon.php?id="+id);
				var div = jQuery(document.createElement("div"));
				div.prop("id", "application_"+id);
				div.prop("className", "applicationLauncherDisabled");
				div.append(img);
				div.append(document.createTextNode(this.applications[id].name+" "));
				div.append(jQuery(document.createElement("span")).addClass("application_instance_counter"));

				this.content[id] = {"node":div, "event":null};
				this.node.append(div);
			}
		}
	}

	if(type == "ovd.session.server.statusChanged") {
		var from = params["from"];
		var to = params["to"];

		if(to == "connected") {
			/* Activate launchers */
			for(var id in this.applications) {
				var self = this; /* closure */
				var item =  this.content[id];
				item["event"] = function () {
					var appId = jQuery(this).prop("id").split("_")[1];
					self.session_management.fireEvent("ovd.log", self, {"message":"Start application "+appId, "level":"debug"});
					self.session_management.fireEvent("ovd.rdpProvider.applicationProvider.applicationStart", self, {"id":appId});
				}
				item["node"].click(item["event"]);
				item["node"].prop("className", "applicationLauncherEnabled");
			}
		}

		if(to == "disconnected") {
			/* Deactivate launchers */
			for(var id in this.applications) {
				var self = this; /* closure */
				var item =  this.content[id];
				item["node"].off('click');
				item["node"].prop("className", "applicationLauncherDisabled");
			}
		}
	}

	if(type == "ovd.rdpProvider.applicationProvider.statusChanged") {
		var from = params['from'];
		var to = params['to'];
		var application = params['application'];

		if(to == "started") {
			var id = application.id;
			var node = this.content[id]['node'].find(".application_instance_counter");
			var count = node.html() || 0;
			var next = 0;

			try{
				next = parseInt(count) + 1;
			} catch(e) {}

			if(next == 0) {
				node.html("");
			} else {
				node.html(next);
			}
		}

		if(to == "stopped") {
			var id = application.id;
			var node = this.content[id]['node'].find(".application_instance_counter");
			var count = node.html() || 0;
			var next = 0;

			try{
				next = parseInt(count) - 1;
			} catch(e) {}

			if(next == 0) {
				node.html("");
			} else {
				node.html(next);
			}
		}
	}

	if(type == "ovd.ajaxProvider.sessionEnd" || type == "ovd.ajaxProvider.sessionSuspend" ) { /* Clean context */
		this.end();
	}
}

SeamlessLauncher.prototype.end = function() {
	if(this.session_management.parameters["session_type"] == "applications") {
		this.node.empty();
		/* Do NOT remove ovd.session.statusChanged as it is used as a delayed initializer */
		this.session_management.removeCallback("ovd.session.server.statusChanged",                  this.handler);
		this.session_management.removeCallback("ovd.rdpProvider.applicationProvider.statusChanged", this.handler);
		this.session_management.removeCallback("ovd.ajaxProvider.sessionEnd",                       this.handler);
		this.session_management.removeCallback("ovd.ajaxProvider.sessionSuspend",                   this.handler);

		this.applications = {};
		this.content = {};
	}
}
