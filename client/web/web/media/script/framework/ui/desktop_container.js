function DesktopContainer(session_management, node) {
	this.node = null;
	this.session_management = session_management;

	if(node) {
		this.node = jQuery(node);
	}

	/* register events listeners */
	this.handler = this.handleEvents.bind(this);
	this.session_management.addCallback("ovd.rdpProvider.desktopPanel",    this.handler);
	this.session_management.addCallback("ovd.ajaxProvider.sessionEnd",     this.handler);
	this.session_management.addCallback("ovd.ajaxProvider.sessionSuspend", this.handler);
}

DesktopContainer.prototype.handleEvents = function(type, source, params) {
	if(type == "ovd.rdpProvider.desktopPanel") {
		var name = params["name"];
		var node = params["node"];

		if(!this.node) {
			this.node = jQuery("#"+session_management.parameters["session_type"]+"AppletContainer"); /* !!! */
		}

		this.node.append(node);
	}

	if(type == "ovd.ajaxProvider.sessionEnd" || type == "ovd.ajaxProvider.sessionSuspend" ) { /* Clean context */
		this.end();
	}
}

DesktopContainer.prototype.end = function() {
	this.node.empty();
}
