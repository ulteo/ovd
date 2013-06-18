SeamlessLauncher = function(session_management, node) {
	this.node = jQuery(node);
	this.session_management = session_management;
	this.applications = {}; /* application id as index */
	this.content = {}; /* application id as index */
	this.handler = this.handleEvents.bind(this);

	/* Do NOT remove ovd.session.starting in destructor as it is used as a delayed initializer */
	this.session_management.addCallback("ovd.session.starting", this.handler);
}

SeamlessLauncher.prototype.handleEvents = function(type, source, params) {
	if(type == "ovd.session.starting") {
		var session_type = this.session_management.parameters["session_type"];
		var session = this.session_management.session;

		if(session_type == uovd.SESSION_MODE_APPLICATIONS) {
			/* register events listeners */
			this.session_management.addCallback("ovd.session.server.statusChanged",                  this.handler);
			this.session_management.addCallback("ovd.rdpProvider.applicationProvider.statusChanged", this.handler);
			this.session_management.addCallback("ovd.session.destroying",                            this.handler);

			var servers = session.servers;

			/* Get application list */
			for(var i=0 ; i<servers.length ; ++i) {
				var server = servers[i];
				for(var j=0 ; j<server.applications.length ; ++j) {
					this.applications[server.applications[j].id] = server.applications[j];
				}
			}

			/* Create launchers */
			var table = jQuery(document.createElement("table"));
			var tbody = jQuery(document.createElement("tbody"));

			for(var id in this.applications) {
				var tr = jQuery(document.createElement("tr"));
				tr.prop("id", "application_"+id);
				tr.prop("className", "applicationLauncherDisabled");

				var td_img = jQuery(document.createElement("td"))
				var img = jQuery(document.createElement("img"));
				img.addClass("application_icon");
				img.prop("src", "icon.php?id="+id);
				td_img.append(img);

				var td_name = jQuery(document.createElement("td"));
				td_name.addClass("application_name");
				td_name.html(this.applications[id].name+" ");

				var td_count = jQuery(document.createElement("td"));
				var count = jQuery(document.createElement("span"));
				count.addClass("application_instance_counter");
				td_count.append(count);

				tr.append(td_img, td_name, td_count);

				this.content[id] = {"node":tr, "event":null};
				tbody.append(tr);
			}

			table.append(tbody);
			this.node.append(table);
		}
	}

	if(type == "ovd.session.server.statusChanged") {
		var from = params["from"];
		var to = params["to"];
		var server = source;

		if(to == uovd.SERVER_STATUS_CONNECTED) {
			/* Activate launchers */
			for(var i = 0 ; i<server.applications.length ; ++i) {
				var id = server.applications[i].id;
				var item =  this.content[id];

				var node = new jQuery(document.createElement("a"));
				node.prop("href", "javascript:;");
				node.html(this.applications[id].name);
				
				item["node"].find(":eq(2)").empty();
				item["node"].find(":eq(2)").append(node);
				
				var self = this; /* closure */
				item["event"] = function () {
					var appId = jQuery(this).parent().parent().prop("id").split("_")[1];
					self.session_management.fireEvent("ovd.log", self, {"message":"Start application "+appId, "level":"debug"});
					self.session_management.fireEvent("ovd.rdpProvider.applicationProvider.applicationStart", self, {"id":appId});
				}
				node.click(item["event"]);
				item["node"].prop("className", "applicationLauncherEnabled");
			}
		}

		if(to == uovd.SERVER_STATUS_DISCONNECTED) {
			/* Deactivate launchers */
			for(var i = 0 ; i<server.applications.length ; ++i) {
				var id = server.applications[i].id;
				var item =  this.content[id];

				item["node"].off('click');
				item["node"].prop("className", "applicationLauncherDisabled");
				item["node"].find(":eq(2)").html(this.applications[id].name);
			}
		}
	}

	if(type == "ovd.rdpProvider.applicationProvider.statusChanged") {
		var from = params['from'];
		var to = params['to'];
		var application = params['application'];

		if(to == uovd.APPLICATION_STARTED) {
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

		if(to == uovd.APPLICATION_STOPPED) {
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

	if(type == "ovd.session.destroying") { /* Clean context */
		this.end();
	}
}

SeamlessLauncher.prototype.end = function() {
	if(this.session_management.parameters["session_type"] == uovd.SESSION_MODE_APPLICATIONS) {
		this.node.empty();
		/* Do NOT remove ovd.session.starting as it is used as a delayed initializer */
		this.session_management.removeCallback("ovd.session.server.statusChanged",                  this.handler);
		this.session_management.removeCallback("ovd.rdpProvider.applicationProvider.statusChanged", this.handler);
		this.session_management.removeCallback("ovd.session.destroying",                            this.handler);

		this.applications = {};
		this.content = {};
	}
}
