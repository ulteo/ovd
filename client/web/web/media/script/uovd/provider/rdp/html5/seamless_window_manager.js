uovd.provider.rdp.html5.SeamlessWindowManager = function(session_management, node, windowFactory) {
	this.node = jQuery(node);
	this.session_management = session_management;
	this.windowFactory = windowFactory;
	this.windows = {};
	this.handler = jQuery.proxy(this.handleEvents, this);
	this.refreshTimer = null;

	/* Do NOT remove ovd.session.starting in destructor as it is used as a delayed initializer */
	this.session_management.addCallback("ovd.session.starting", this.handler);
}

uovd.provider.rdp.html5.SeamlessWindowManager.prototype.handleEvents = function(type, source, params) {
	if(type == "ovd.session.starting") {
		var session_mode = this.session_management.session.mode;

		if(session_mode == uovd.SESSION_MODE_APPLICATIONS) {
			/* register events listeners */
			this.session_management.addCallback("ovd.rdpProvider.seamless.in.*",    this.handler);
			this.session_management.addCallback("ovd.session.destroying",           this.handler);

			/* Refresh windows with a timeout */
			var self = this; /* closure */
			this.refreshTimer = setInterval( function() {
				for(var id in self.windows) {
					if(self.windows[id]) {
						self.windows[id].update();
					}
				}
			}, 100);
		}
	} else if(type == "ovd.rdpProvider.seamless.in.windowCreate") {
		var id = params["id"];
		this.windows[id] = this.windowFactory.create(params);
		this.node.append(this.windows[id].getNode());
	} else if(type == "ovd.rdpProvider.seamless.in.windowDestroy") {
		var id = params["id"];
		if(this.windows[id]) {
			this.windows[id].destroy();
			delete this.windows[id];
		}
	} else if(type == "ovd.rdpProvider.seamless.in.groupDestroy") {
		var group = params["id"];
		for(var id in this.windows) {
			if(this.windows[id]) {
				if(this.windows[id].getGroup() == group) {
					this.windows[id].destroy();
					delete this.windows[id];
				}
			}
		}
	} else if(type == "ovd.rdpProvider.seamless.in.windowPropertyChanged") {
		var id = params["id"];
		var property = params["property"];
		if(! this.windows[id]) { return; }

		switch(property) {
			case "title" :
				var title = params["value"];
				this.windows[id].setTitle(title);
				break;

			case "position" :
				var x = params["value"][0];
				var y = params["value"][1];
				this.windows[id].setPosition(x, y);
				break;

			case "size" :
				var w = params["value"][0];
				var h = params["value"][1];
				this.windows[id].setSize(w, h);
				break;

			case "state" :
				var state = params["value"];

				/* Handle visibility */
				if(state == "Normal" || state == "Maximized" || state == "Fullscreen") {
					this.windows[id].show();
				} else {
					this.windows[id].hide();
				}

				/* Handle size */
				if(state == "Maximized" || state == "Fullscreen") {
					this.windows[id].maximize();
				} else if(state == "Normal") {
					this.windows[id].restore();
				}
				break;

			case "focus" :
				var state = params["value"];
				if(state == true) {
					/* Avoid focus loop */
					if(this.windows[id].isFocused()) {
						return;
					}

					/* Set the window on the top */
					if(this.node.find("> *").length > 1) {
						var win = jQuery(this.windows[id].getNode()).detach();
						this.node.find(":last-child").after(win);
					}

					/* Remove focus from other(s) */
					for(var w_id in this.windows) {
						if(this.windows[w_id]) {
							/* Don't send blur events to window on the same desktop : already done server-side */
							if(this.windows[w_id].getServerId() != this.windows[id].getServerId()) {
								if(this.windows[w_id].isFocused()) {
									var parameters = {};
									parameters["id"] = w_id;
									parameters["server_id"] = this.windows[w_id].getServerId();
									parameters["property"] = "focus";
									parameters["value"] = false;
									this.session_management.fireEvent("ovd.rdpProvider.seamless.out.windowPropertyChanged", this, parameters);

									this.windows[w_id].blur();
								}
							}
						}
					}

					/* Set focus to the window */
					this.windows[id].focus();

				} else {
					this.windows[id].blur();
				}
				break;
		}
	} else if(type == "ovd.session.destroying" ) { /* Clean context */
		this.end();
	}
}

uovd.provider.rdp.html5.SeamlessWindowManager.prototype.end = function() {
	if(this.session_management.session.mode == uovd.SESSION_MODE_APPLICATIONS) {
		this.node.empty();
		/* Do NOT remove ovd.session.starting as it is used as a delayed initializer */
		this.session_management.removeCallback("ovd.rdpProvider.seamless.in.*",    this.handler);
		this.session_management.removeCallback("ovd.session.destroying",           this.handler);

		this.windows = {};
		clearInterval(this.refreshTimer);
		this.refreshTimer = null;
	}
}
