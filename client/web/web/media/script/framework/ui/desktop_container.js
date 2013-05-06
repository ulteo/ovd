function DesktopContainer(session_management, node) {
	this.node = jQuery(node);
	this.session_management = session_management;

	/* register events listeners */
	var self = this; /* closure */
	this.session_management.addCallback("ovd.rdpProvider.desktopPanel", function(type, source, params) {
		self.handleEvents(type, source, params);
	});                                         
}

DesktopContainer.prototype.handleEvents = function(type, source, params) {
	if(type == "ovd.rdpProvider.desktopPanel") {
		var name         = params["name"];
		var desktop_node = params["node"];

		this.node.append(desktop_node);
	}
}
