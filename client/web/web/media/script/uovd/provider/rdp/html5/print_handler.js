uovd.provider.rdp.html5.PrintHandler = function(rdp_provider) {
	this.rdp_provider = rdp_provider;
	this.connections = this.rdp_provider.connections;
	this.handler = jQuery.proxy(this.handleEvents, this);
	this.ui = null;

	/* Install instruction hook */
	var self = this; /* closure */
	for(var i=0 ; i<this.connections.length ; ++i) {
		(function(server_id) {
			self.connections[server_id].guac_tunnel.addInstructionHandler("printjob", jQuery.proxy(self.handleOrders, self, server_id));
		})(i);
	}

	this.rdp_provider.session_management.addCallback("ovd.session.started",           this.handler);
	this.rdp_provider.session_management.addCallback("ovd.session.destroying",        this.handler);
}

uovd.provider.rdp.html5.PrintHandler.prototype.handleOrders = function(server_id, opcode, parameters) {
	if(opcode == "printjob") {
		/* HACK ! Need to change the Guacamole message format */
		var print_job_id = parameters[0].split("/").reverse()[1];
		var url = 'guacamole/printer/get/'+print_job_id;

		if(this.ui != null) {
			/* build node */
			var br = jQuery(document.createElement("br"));

			var text = jQuery(document.createElement("span"));
			text.html("&nbsp;- Print job : " +(new Date()).toLocaleString()+"&nbsp;-");

			var a_get = jQuery(document.createElement("a"));
			a_get.attr("href", url);
			a_get.attr("target", "_blank");
			a_get.html("&nbsp;&nbsp;&nbsp;get");

			var a_clear = jQuery(document.createElement("a"));
			a_clear.attr("href", "javascript:;");
			a_clear.html("&nbsp;&nbsp;&nbsp;clear");
			a_clear.on("click", function() {
				br.remove();
				text.remove();
				a_get.remove();
				a_clear.remove();
			});

			this.ui.append(text, a_get, a_clear, br);

			/* Notify */
			ovd.framework.session_management.fireEvent("ovd.rdpProvider.menu.notify", document, {"message":"&#9113;", "duration":10000, "interval":1000});
		}
	}
}

uovd.provider.rdp.html5.PrintHandler.prototype.handleEvents = function(type, source, params) {
	var self = this; /* closure */

	if(type == "ovd.session.started") {
		/* Build menu UI element */
		this.ui = jQuery(document.createElement("div"));
		this.ui.css({
			"box-sizing": "border-box",
			"width":"100%",
			"height":"100%",
			"background": "#FFF",
			"overflow": "auto"
		});

		/* Append UI elements */
		this.rdp_provider.session_management.fireEvent("ovd.rdpProvider.menu", this, {"node":this.ui, "type":"Printers"});
	} else if(type == "ovd.session.destroying") {
		/* Remove instruction hook */
		for(var i=0 ; i<this.connections.length ; ++i) {
			(function(server_id) {
				self.connections[server_id].guac_tunnel.removeInstructionHandler("printjob");
			})(i);
		}

		this.ui = null;

		this.rdp_provider.session_management.removeCallback("ovd.session.started",         this.handler);
		this.rdp_provider.session_management.removeCallback("ovd.session.destroying",      this.handler);
	}
}
