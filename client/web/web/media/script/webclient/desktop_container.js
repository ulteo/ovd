DesktopContainer = function(session_management, node) {
	this.node = jQuery(node);
	this.session_management = session_management;

	/* register events listeners */
	this.handler = this.handleEvents.bind(this);
	this.session_management.addCallback("ovd.rdpProvider.desktopPanel", this.handler);
	this.session_management.addCallback("ovd.session.destroying",       this.handler);
}

DesktopContainer.prototype.handleEvents = function(type, source, params) {
	if(type == "ovd.rdpProvider.desktopPanel") {
		var type = params["type"];
		var node = params["node"];

		if(type == "Desktop" && node != null) {
			/* This is a canvas or applet */
			this.node.append(node);
		}

		if(type == "Fullscreen") {
			/* Fullscreen mode without panel : insert message */

			var fullscreen_message = jQuery("#fullScreenMessage").clone();
			fullscreen_message.show();
			fullscreen_message.find("a").click( function() {
				source.request_fullscreen();
			});

			var background = jQuery(document.createElement('div'));
			background.css("background", "#DDD");
			background.css("color", "#333");
			background.width(window.innerWidth);
			background.height(window.innerHeight);
			background.append(fullscreen_message);
			jQuery('#desktopContainer').append(background);

			new Effect.Center(fullscreen_message[0]);

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
