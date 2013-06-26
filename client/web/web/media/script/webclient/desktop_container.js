DesktopContainer = function(session_management, node) {
	this.node = jQuery(node);
	this.session_management = session_management;

	/* register events listeners */
	this.handler = jQuery.proxy(this.handleEvents, this);
	this.session_management.addCallback("ovd.rdpProvider.desktopPanel", this.handler);
	this.session_management.addCallback("ovd.session.destroying",       this.handler);
}

DesktopContainer.prototype.handleEvents = function(type, source, params) {
	var self = this; /* closure */

	if(type == "ovd.rdpProvider.desktopPanel") {
		var type = params["type"];
		var node = params["node"];

		if(type == "Desktop" && node != null) {
			/* This is a canvas or applet */
			this.node.append(node);
		}

		if(type == "Fullscreen") {
			/* Fullscreen mode without panel : insert message */
			var logout_header = jQuery("#applicationsHeader").clone();
			logout_header.find("#logout_link").click( function() {
				self.session_management.stop();
			});
			logout_header.find("#suspend_link").click( function() {
				self.session_management.suspend();
			});
			logout_header.show();

			var fullscreen_message = jQuery(document.createElement('div'));
			fullscreen_message.width("100%").height("100%")
			fullscreen_message.css("position", "absolute");
			fullscreen_message.css("top", "30%");

			var fullscreen_message_container = jQuery("#fullScreenMessage").clone();
			fullscreen_message_container.show();
			fullscreen_message_container.find("a").click( function() {
				source.request_fullscreen();
			});

			fullscreen_message.append(fullscreen_message_container);

			var background = jQuery(document.createElement('div'));
			background.css("background", "#DDD");
			background.css("color", "#333");
			background.width("100%");
			background.height("100%");

			background.append(logout_header);
			background.append(fullscreen_message);
			jQuery('#desktopContainer').append(background);

			if(node != null) {
				this.node.append(node);
			}
		}
	}

	if(type == "ovd.session.destroying" ) { /* Clean context */
		this.end();
	}
}

DesktopContainer.prototype.end = function() {
	this.node.empty();
}
