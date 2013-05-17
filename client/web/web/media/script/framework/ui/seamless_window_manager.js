function SeamlessWindowManager(session_management, node, windowFactory) {
	this.node = jQuery(node);
	this.session_management = session_management;
	this.windowFactory = windowFactory;
	this.windows = {};

	if(this.session_management.parameters["session_type"] == "applications") {
		/* register events listeners */
		this.handler = this.handleEvents.bind(this);
		this.session_management.addCallback("ovd.rdpProvider.windowCreate",     this.handler);
		this.session_management.addCallback("ovd.rdpProvider.windowDestroy",    this.handler);
		this.session_management.addCallback("ovd.rdpProvider.windowProperties", this.handler);
		this.session_management.addCallback("ovd.rdpProvider.windowUpdate",     this.handler);
		this.session_management.addCallback("ovd.session.server.statusChanged", this.handler);
		this.session_management.addCallback("ovd.ajaxProvider.sessionEnd",      this.handler);
	}
}

SeamlessWindowManager.prototype.handleEvents = function(type, source, params) {
	if(type == "ovd.rdpProvider.windowCreate") {
		var id = params["id"];
		if(id) {
			this.windows[id] = this.windowFactory.create(params);
			this.node.append(this.windows[id].getNode());
		}
	} else if(type == "ovd.rdpProvider.windowDestroy") {
		var id = params["id"];
		if(id) {
			if(this.windows[id]) {
				jQuery(this.windows[id].getNode()).remove();
				this.windows[id].destroy();
				this.windows[id] = null;
			}
		}
	} else if(type == "ovd.rdpProvider.windowProperties") {
		var id = params["id"];
		if(id) {
			if(this.windows[id]) {
				this.windows[id].properties(params);
			}
		}
	} else if(type == "ovd.rdpProvider.windowUpdate") {
		var id = params["id"];
		if(id) {
			if(this.windows[id]) {
				this.windows[id].update(params);
			}
		}
	} else if(type == "ovd.session.server.statusChanged") {
		var from = params["from"];
		var to = params["to"];
		if(to == "disconnected") {
			/* Server disconnected : unmap all windows */
			for(id in this.windows) {
				if(id && this.windows[id]) {
					jQuery(this.windows[id].getNode()).remove();
					this.windows[id].destroy();
					this.windows[id] = null;
				}
			}
		}
	} else if(type == "ovd.ajaxProvider.sessionEnd") {
		this.end();
	}
}

SeamlessWindowManager.prototype.end = function() {
	if(this.session_management.parameters["session_type"] == "applications") {
		this.node.empty();
		this.session_management.removeCallback("ovd.rdpProvider.windowCreate",     this.handler);
		this.session_management.removeCallback("ovd.rdpProvider.windowDestroy",    this.handler);
		this.session_management.removeCallback("ovd.rdpProvider.windowProperties", this.handler);
		this.session_management.removeCallback("ovd.rdpProvider.windowUpdate",     this.handler);
		this.session_management.removeCallback("ovd.session.server.statusChanged", this.handler);
		this.session_management.removeCallback("ovd.ajaxProvider.sessionEnd",      this.handler);
	}
}
