StartApp = function(session_management) {
	this.session_management = session_management;
	this.start_app_interval = null;
	this.handler = this.handleEvents.bind(this);

	/* Do NOT remove ovd.session.statusChanged in destructor as it is used as a delayed initializer */
	this.session_management.addCallback("ovd.session.statusChanged", this.handler);
}

StartApp.prototype.handleEvents = function(type, source, params) {
	if(type == "ovd.session.statusChanged") {
		var from = params["from"];
		var to = params["to"];

		if(to == "logged" && this.session_management.parameters["session_type"] == "applications") {
			/* register events listeners */
			this.session_management.addCallback("ovd.ajaxProvider.sessionEnd",     this.handler);
			this.session_management.addCallback("ovd.ajaxProvider.sessionSuspend", this.handler);

			/* Set polling interval for start_app.php */
			this.start_app_interval = setInterval(this._check_start_app.bind(this), 2000);
		}
	}

	if(type == "ovd.ajaxProvider.sessionEnd" || type == "ovd.ajaxProvider.sessionSuspend" ) { /* Clean context */
		this.end();
	}
}

StartApp.prototype._check_start_app = function() {
	jQuery.ajax({
		url: "start_app.php?check=true&differentiator="+Math.floor(Math.random()*50000),
		type: "GET",
		contentType: "text/xml",
		success: this._parse_start_app.bind(this)
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
			self.session_management.fireEvent("ovd.rdpProvider.applicationProvider.applicationStartWithArgs", self, {"id":id, "args":{"type":type, "path":path, "share":share}});
		} else {
			self.session_management.fireEvent("ovd.rdpProvider.applicationProvider.applicationStart", self, {"id":id});
		}
	});
}


StartApp.prototype.end = function() {
	if(this.session_management.parameters["session_type"] == "applications") {
		/* Do NOT remove ovd.session.statusChanged as it is used as a delayed initializer */
		this.session_management.removeCallback("ovd.ajaxProvider.sessionEnd",      this.handler);
		this.session_management.removeCallback("ovd.ajaxProvider.sessionSuspend",  this.handler);

		if(this.start_app_interval != null) {
			clearInterval(this.start_app_interval);
			this.start_app_interval = null;
		}
	}
}

