uovd.provider.rdp.html5.ClipboardHandler = function(rdp_provider) {
	this.rdp_provider = rdp_provider;
	this.connections = this.rdp_provider.connections;
	this.handler = jQuery.proxy(this.handleEvents, this);
	this.textarea = null;

	/* Install instruction hook */
	var self = this; /* closure */
	for(var i=0 ; i<this.connections.length ; ++i) {
		(function(server_id) {
			self.connections[server_id].guac_tunnel.addInstructionHandler("clipboard", jQuery.proxy(self.handleOrders, self, server_id));
		})(i);
	}

	this.rdp_provider.session_management.addCallback("ovd.rdpProvider.clipboard.*",   this.handler);
	this.rdp_provider.session_management.addCallback("ovd.session.started",           this.handler);
	this.rdp_provider.session_management.addCallback("ovd.session.destroying",        this.handler);
}

uovd.provider.rdp.html5.ClipboardHandler.prototype.handleOrders = function(server_id, opcode, parameters) {
	if(opcode == "clipboard") {
		var value = base64_decode(parameters[0]);
		this.rdp_provider.session_management.fireEvent("ovd.rdpProvider.clipboard.in", this, {"value":value});
	}
}

uovd.provider.rdp.html5.ClipboardHandler.prototype.handleEvents = function(type, source, params) {
	var self = this; /* closure */
	if(type == "ovd.rdpProvider.clipboard.out") {
		/* Send clipboard value */
		for(var i=0 ; i<this.connections.length ; ++i) {
			var tunnel = this.connections[i].guac_tunnel
			var message = base64_encode(params["value"]);
			tunnel.sendMessage("clipboard", message);
		}
	} else if(type == "ovd.rdpProvider.clipboard.in") {
		/* Read clipboard value */
		var value = params["value"];

		if(this.textarea != null) {
			if(value != this.textarea.val()) {
				/* Update textfield */
				this.textarea.val(value);

				/* Notify */
				ovd.framework.session_management.fireEvent("ovd.rdpProvider.menu.notify", document, {"message":"⎘", "duration":2000, "interval":1000});

				/* Keep other servers in sync */
				this.rdp_provider.session_management.fireEvent("ovd.rdpProvider.clipboard.out", this, {"value":value});
			}
		}
	} else if(type == "ovd.session.started") {
		/* Build menu UI element */
		this.textarea = jQuery(document.createElement("textarea"));
		this.textarea.css({
			"display": "block",
			"box-sizing": "border-box",
			"width":"100%",
			"height":"100%",
			"margin":0,
			"padding":0,
			"border": "none",
			"resize": "none"
		});

		/* Send new clipboard value on change */
		this.textarea.on("change blur", function() {
			self.rdp_provider.session_management.fireEvent("ovd.rdpProvider.clipboard.out", this, {"value":self.textarea.val()});
		});

		/* Append UI elements */
		this.rdp_provider.session_management.fireEvent("ovd.rdpProvider.menu", this, {"node":this.textarea, "type":"Clipboard"});
	} else if(type == "ovd.session.destroying") {
		/* Remove instruction hook */
		for(var i=0 ; i<this.connections.length ; ++i) {
			(function(server_id) {
				self.connections[server_id].guac_tunnel.removeInstructionHandler("clipboard");
			})(i);
		}

		this.textarea.off("change");
		this.textarea.off("blur");
		this.textarea = null;

		this.rdp_provider.session_management.removeCallback("ovd.rdpProvider.clipboard.*", this.handler);
		this.rdp_provider.session_management.removeCallback("ovd.session.started",         this.handler);
		this.rdp_provider.session_management.removeCallback("ovd.session.destroying",      this.handler);
	}
}
