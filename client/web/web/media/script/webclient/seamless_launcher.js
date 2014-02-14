SeamlessLauncher = function(session_management, node, logo) {
	this.node = jQuery(node);
	this.logo = jQuery(logo);
	this.session_management = session_management;
	this.applications = {}; /* application id as index */
	this.content = {}; /* application id as index */
	this.handler = jQuery.proxy(this.handleEvents, this);

	/* Do NOT remove ovd.session.starting in destructor as it is used as a delayed initializer */
	this.session_management.addCallback("ovd.session.starting", this.handler);
}

SeamlessLauncher.prototype.showLauncher = function() {
	this.menuToggled = true;
	this.node.parent().addClass("menuToggled");
}

SeamlessLauncher.prototype.hideLauncher = function() {
	this.menuToggled = false;
	this.node.parent().removeClass("menuToggled");
}

SeamlessLauncher.prototype.handleEvents = function(type, source, params) {
	if(type == "ovd.session.starting") {
		var session_mode = this.session_management.session.mode;
		var session = this.session_management.session;

		if(session_mode == uovd.SESSION_MODE_APPLICATIONS) {
			/* register events listeners */
			this.session_management.addCallback("ovd.session.server.statusChanged",       this.handler);
			this.session_management.addCallback("ovd.applicationsProvider.statusChanged", this.handler);
			this.session_management.addCallback("ovd.rdpProvider.seamless.in.windowPropertyChanged", this.handler);
			this.session_management.addCallback("ovd.session.destroying",                 this.handler);

			var servers = session.servers;

			/* Get application list */
			for(var i=0 ; i<servers.length ; ++i) {
				var server = servers[i];
				for(var j=0 ; j<server.applications.length ; ++j) {
					this.applications[server.applications[j].id] = server.applications[j];
				}
			}

			/* Sort the list by name */
			var applications_sorted = new Array();
			for(var id in this.applications) { applications_sorted.push(this.applications[id]); }
			applications_sorted.sort(function(a, b) { var n1=a.name.toLowerCase(); var n2=b.name.toLowerCase(); return (n1>n2 ? 1 : (n1<n2 ? -1 : 0)); })

			/* URL basename for icons */
			var icon_webservice = (session.mode_gateway == true) ? "client/icon" : "icon.php";

			/* Create launchers */
			var ul = jQuery(document.createElement("ul"));

			for(var i=0 ; i<applications_sorted.length ; ++i) {
				var id = applications_sorted[i].id;

				var li = jQuery(document.createElement("li"));
				li.prop("id", "application_"+id);
				li.prop("className", "applicationLauncherDisabled");

				var img = jQuery(document.createElement("img"));
				img.addClass("application_icon");
				img.prop("src", icon_webservice+"?id="+id);

				var p_name = jQuery(document.createElement("p"));
				p_name.addClass("application_name");
				p_name.html(this.applications[id].name);

				var count = jQuery(document.createElement("span"));
				count.addClass("application_instance_counter");

				li.append(img, p_name, count);

				this.content[id] = {"node":li, "event":null};
				ul.append(li);
			}

			this.node.append(ul);
			
			this.logo.on("click.menuToggle", function() {
				if (this.menuToggled) {
					this.hideLauncher();
				} else {
					this.showLauncher();
				}
			}.bind(this));

		}
	}

	if(type == "ovd.session.server.statusChanged") {
		var from = params["from"];
		var to = params["to"];
		var server = source;

		if(to == uovd.SERVER_STATUS_READY) {
			/* Activate launchers */
			for(var i = 0 ; i<server.applications.length ; ++i) {
				var id = server.applications[i].id;
				var item =  this.content[id];
				var self = this; /* closure */
				item["event"] = function () {
					var appId = jQuery(this).prop("id").split("_")[1];
					self.session_management.fireEvent("ovd.log", self, {"message":"Start application "+appId, "level":"debug"});
					self.session_management.fireEvent("ovd.applicationsProvider.applicationStart", self, {"id":appId});
					self.content[appId]["node"].addClass("launching");
				}
				item["node"].click(item["event"]);
				item["node"].prop("className", "applicationLauncherEnabled");
			}
		}
	}
	
	if(type == "ovd.rdpProvider.seamless.in.windowPropertyChanged") {
		if(params["property"] == "state") {
			var state = params["value"];

			if(state == "Normal" || state == "Maximized" || state == "Fullscreen") {
				this.hideLauncher();
			}
		}
	}

	if(type == "ovd.applicationsProvider.statusChanged") {
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
				node.parent().addClass("launched");
				node.parent().removeClass("launching");
			}
			this.hideLauncher();
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
				node.parent().removeClass("launched");
				node.parent().removeClass("launching");
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
	if(this.session_management.session.mode == uovd.SESSION_MODE_APPLICATIONS) {
		this.node.empty();
		/* Do NOT remove ovd.session.starting as it is used as a delayed initializer */
		this.session_management.removeCallback("ovd.session.server.statusChanged",       this.handler);
		this.session_management.removeCallback("ovd.applicationsProvider.statusChanged", this.handler);
		this.session_management.removeCallback("ovd.rdpProvider.seamless.in.windowPropertyChanged", this.handler);
		this.session_management.removeCallback("ovd.session.destroying",                 this.handler);

		this.applications = {};
		this.content = {};
		this.hideLauncher();
		this.logo.off("click.menuToggle");
	}
}
