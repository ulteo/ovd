SeamlessTaskbar = function(session_management, node) {
	this.node = jQuery(node);
	this.list = null;
	this.seamless_wm = null;
	this.session_management = session_management;
	this.content = {}; /* application id as index */
	this.handler = jQuery.proxy(this.handleEvents, this);

	/* Do NOT remove ovd.session.starting in destructor as it is used as a delayed initializer */
	this.session_management.addCallback("ovd.session.starting", this.handler);
}

SeamlessTaskbar.prototype.handleEvents = function(type, source, params) {
	if(type == "ovd.session.starting") {
		var session_mode = this.session_management.session.mode;
		var session = this.session_management.session;

		this.seamless_wm = window.ovd.framework.listeners.seamless_window_manager;
		if(! this.seamless_wm) {
			return;
		}

		if(session_mode == uovd.SESSION_MODE_APPLICATIONS) {
			/* register events listeners */
			this.session_management.addCallback("ovd.rdpProvider.seamless.in.windowDestroy",         this.handler);
			this.session_management.addCallback("ovd.rdpProvider.seamless.in.windowPropertyChanged", this.handler);
			this.session_management.addCallback("ovd.session.destroying", this.handler);

			this.list = jQuery(document.createElement("ul"));
			this.node.append(this.list);
		}
	}

	if(type == "ovd.rdpProvider.seamless.in.windowDestroy") {
		this.remove(params["id"]);
	}

	if(type == "ovd.rdpProvider.seamless.in.windowPropertyChanged") {
		if(params["property"] == "state") {
			var state = params["value"];

			if(state == "Normal" || state == "Maximized" || state == "Fullscreen") {
				this.remove(params["id"]);
			} else {
				this.add(params["id"]);
			}
		}
	}

	if(type == "ovd.session.destroying") { /* Clean context */
		this.end();
	}
}

SeamlessTaskbar.prototype.add = function(id) {
	if(this.content[id]) {
		/* Do not add multiple times */
		return;
	}

	var seamless_window = this.seamless_wm.windows[id];

	if(!seamless_window) {
		/* No window in seamless_wm */
		return;
	}

	var attributes = seamless_window.getAttributes();

	if(attributes.indexOf("Popup") != -1 || attributes.indexOf("Tooltip") != -1) {
		/* Don't map popups and tooltips */
		return;
	}

	/* Create button */
	var li = jQuery(document.createElement("li"));
	li.prop("id", "application_"+id);
	li.prop("className", "applicationTaskbar");

	/* Icon from windows screenshot */
	var img = jQuery(document.createElement("img"));
	var src = seamless_window.getNode().toDataURL();
	img.addClass("application_icon");
	img.prop("src", src);

	/* Title */
	var p_name = jQuery(document.createElement("p"));
	p_name.addClass("application_name");
	p_name.html(seamless_window.getTitle());

	li.append(img, p_name);
	this.list.append(li);

	/* Click event */
	this.content[id] = {"node":li, "event":jQuery.proxy(function() {
		var params = {};
		params["id"] = id;
		params["server_id"] = this.seamless_wm.windows[id].getServerId();
		params["property"] = "state";
		params["value"] = "Normal";
		this.session_management.fireEvent("ovd.rdpProvider.seamless.out.windowPropertyChanged", this, params);

		/* !!! Hack to simulate an ack from seamless channel */
		this.session_management.fireEvent("ovd.rdpProvider.seamless.in.windowPropertyChanged", this, params);

	}, this)};

	this.content[id].node.on("click", this.content[id].event);
}

SeamlessTaskbar.prototype.remove = function(id) {
	if(this.content[id]) {
		this.content[id].node.remove();
		delete this.content[id];
	}
}

SeamlessTaskbar.prototype.end = function() {
	if(this.session_management.session.mode == uovd.SESSION_MODE_APPLICATIONS) {
		this.node.empty();
		/* Do NOT remove ovd.session.starting as it is used as a delayed initializer */
		this.session_management.removeCallback("ovd.rdpProvider.seamless.in.windowDestroy",         this.handler);
		this.session_management.removeCallback("ovd.rdpProvider.seamless.in.windowPropertyChanged", this.handler);
		this.session_management.removeCallback("ovd.session.destroying", this.handler);

		this.content = {};
		this.seamless_wm = null;
	}
}
