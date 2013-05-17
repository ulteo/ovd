function DesktopContainer(session_management, node) {
	this.node = jQuery(node);
	this.desktop = null;
	this.session_management = session_management;

	/* register events listeners */
	var self = this; /* closure */
	this.session_management.addCallback("ovd.*", function(type, source, params) {
		self.handleEvents(type, source, params);
	});                                         
}

DesktopContainer.prototype.handleEvents = function(type, source, params) {
	if(type == "ovd.rdpProvider.desktopPanel") {
		var name     = params["name"];
		this.desktop = params["node"];

		this.node.append(this.desktop);
	}

	if(type == "ovd.ajaxProvider.sessionEnd") { /* Clean content even if the sessionEnd request failed */
		if(this.desktop) {
			jQuery(this.desktop).remove();
			this.desktop = null;
		}
	}
}
