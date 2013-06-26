StartApp = function(session_management) {
	this.session_management = session_management;
	this.start_app_interval = null;
	this.handler = jQuery.proxy(this.handleEvents, this);

	/* Do NOT remove ovd.session.started in destructor as it is used as a delayed initializer */
	this.session_management.addCallback("ovd.session.started", this.handler);
}

StartApp.prototype.handleEvents = function(type, source, params) {
	if(type == "ovd.session.started") {
		var session_mode = this.session_management.parameters["mode"];

		if(session_mode == uovd.SESSION_MODE_APPLICATIONS) {
			/* register events listeners */
			this.session_management.addCallback("ovd.session.destroying", this.handler);

			/* Set polling interval for start_app.php */
			this.start_app_interval = setInterval(jQuery.proxy(this._check_start_app, this), 2000);
		}
	}

	if(type == "ovd.session.destroying" ) { /* Clean context */
		this.end();
	}
}

StartApp.prototype._check_start_app = function() {
	jQuery.ajax({
		url: "start_app.php?check=true&differentiator="+Math.floor(Math.random()*50000),
		type: "GET",
		contentType: "text/xml",
		success: jQuery.proxy(this._parse_start_app, this)
	});
}

StartApp.prototype._parse_start_app = function(xml) {
	var self = this; /* closure */
	jQuery(xml).find("start_app").each( function() {
		var node = jQuery(this);
		var id = node.attr("id");
		var file = node.find("file");

		if(file[0]) {
			var type = file.attr('type');
			var path = file.attr('path');
			var share = file.attr('share');
			self.session_management.fireEvent("ovd.applicationsProvider.applicationStartWithArgs", self, {"id":id, "args":{"type":type, "path":path, "share":share}});
		} else {
			self.session_management.fireEvent("ovd.applicationsProvider.applicationStart", self, {"id":id});
		}
	});
}


StartApp.prototype.end = function() {
	if(this.session_management.parameters["mode"] == uovd.SESSION_MODE_APPLICATIONS) {
		/* Do NOT remove ovd.session.started as it is used as a delayed initializer */
		this.session_management.removeCallback("ovd.session.destroying", this.handler);

		if(this.start_app_interval != null) {
			clearInterval(this.start_app_interval);
			this.start_app_interval = null;
		}
	}
}

