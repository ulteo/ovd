function SeamlessLauncher(session_management, node) {
	this.node = jQuery(node);
	this.session_management = session_management;
	this.applications = {}; /* application id as index */
	this.content = {}; /* application id as index */

	if(this.session_management.parameters["session_type"] == "applications") {
		/* register events listeners */
		this.session_management.addCallback("ovd.session.statusChanged",        this.handleEvents.bind(this));
		this.session_management.addCallback("ovd.session.server.statusChanged", this.handleEvents.bind(this));
	}
}

SeamlessLauncher.prototype.handleEvents = function(type, source, params) {
	if(type == "ovd.session.statusChanged") {
		var from = params["from"];
		var to = params["to"];
		var session = source;

		if(to == "ready") {
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
        div.append(document.createTextNode(this.applications[id].name));

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
}
