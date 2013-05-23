function Ajaxplorer(session_management, node) {
	this.node = jQuery(node);
	this.session_management = session_management;
	this.start_app_interval = null;

	/* register events listeners */
	if(this.session_management.parameters["session_type"] == "applications") {
		this.handler = this.handleEvents.bind(this);
		this.session_management.addCallback("ovd.ajaxProvider.sessionStart",   this.handler);
		this.session_management.addCallback("ovd.ajaxProvider.sessionEnd",     this.handler);
		this.session_management.addCallback("ovd.ajaxProvider.sessionSuspend", this.handler);
	}
}

Ajaxplorer.prototype.handleEvents = function(type, source, params) {
	if(type == "ovd.ajaxProvider.sessionStart") {
		var state = params["state"];
		var self = this; /* closure */
		if(state == "success") {
			var serialized;
			try {
				// XMLSerializer exists in current Mozilla browsers
				serializer = new XMLSerializer();
				serialized = serializer.serializeToString(this.session_management.session.xml);
			} catch (e) {
				// Internet Explorer has a different approach to serializing XML
				serialized = this.session_management.session.xml;
			}

			jQuery.ajax({
				url: "/ovd/ajaxplorer.php",
				type: "POST",
				dataType: "xml",
				contentType: "text/xml",
				data: serialized,
				success: function(xml) {
					var ajaxplorer = jQuery(xml).find("ajaxplorer");
					var status = ajaxplorer.attr("status");

					if(status == "ok") {
						/* Instert ajaxplorer */
						self._show_ajaxplorer_ui();

						/* Set polling interval for start_app.php */
						self.start_app_interval = setInterval(self._check_start_app.bind(self), 2000);
					}
				},
			});
		}
	}

	if(type == "ovd.ajaxProvider.sessionEnd" || type == "ovd.ajaxProvider.sessionSuspend" ) { /* Clean context */
		this.end();
	}
}

Ajaxplorer.prototype._show_ajaxplorer_ui = function() {
	var iframe = jQuery(document.createElement("iframe"));
	iframe.width("100%").height("100%");
	iframe.css("border", "none");
	iframe.prop("src", "ajaxplorer/");
	this.node.append(iframe);
}

Ajaxplorer.prototype._check_start_app = function() {
	jQuery.ajax({
		url: "start_app.php?check=true&differentiator="+Math.floor(Math.random()*50000),
		type: "GET",
		contentType: "text/xml",
		success: this._parse_start_app.bind(this)
	});
}

Ajaxplorer.prototype._parse_start_app = function(xml) {
	var self = this; /* closure */
	jQuery(xml).find("start_app").each( function() {
		var node = jQuery(this);
		var id = node.attr("id");
		var file = node.find("file");

		if(file) {
			var type = file.attr('type');
			var path = file.attr('path');
			var share = file.attr('share');
			self.session_management.fireEvent("ovd.rdpProvider.applicationProvider.applicationStartWithArgs", self, {"id":id, "args":{"type":type, "path":path, "share":share}});
		} else {
			self.session_management.fireEvent("ovd.rdpProvider.applicationProvider.applicationStart", self, {"id":id});
		}
	});
}


Ajaxplorer.prototype.end = function() {
	if(this.session_management.parameters["session_type"] == "applications") {
		this.node.empty();
		this.session_management.removeCallback("ovd.ajaxProvider.sessionStart",    this.handler);
		this.session_management.removeCallback("ovd.ajaxProvider.sessionEnd",      this.handler);
		this.session_management.removeCallback("ovd.ajaxProvider.sessionSuspend",  this.handler);
		if(this.start_app_interval != null) {
			clearInterval(this.start_app_interval);
			this.start_app_interval = null;
		}
	}
}
