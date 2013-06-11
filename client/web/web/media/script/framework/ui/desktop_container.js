uovd.DesktopContainer = function(session_management, node) {
	this.node = jQuery(node);
	this.session_management = session_management;

	/* register events listeners */
	this.handler = this.handleEvents.bind(this);
	this.session_management.addCallback("ovd.rdpProvider.desktopPanel",    this.handler);
	this.session_management.addCallback("ovd.ajaxProvider.sessionEnd",     this.handler);
	this.session_management.addCallback("ovd.ajaxProvider.sessionSuspend", this.handler);
}

uovd.DesktopContainer.prototype.handleEvents = function(type, source, params) {
	if(type == "ovd.rdpProvider.desktopPanel") {
		var name = params["name"];
		var node = params["node"];
		this.node.append(node);
	}

	if(type == "ovd.ajaxProvider.sessionEnd" || type == "ovd.ajaxProvider.sessionSuspend" ) { /* Clean context */
		this.end();
	}
}

uovd.DesktopContainer.prototype.end = function() {
	this.node.empty();
}
