/* HTML5 RDP Provider */

function Html5RdpProvider() {
	this.initialize();
	this.guac_client   = null;
	this.guac_display  = null;
	this.guac_canvas   = null;
	this.guac_tunnel   = null;
	this.guac_mouse    = null;
	this.guac_keyboard = null;
}

Html5RdpProvider.prototype = new RdpProvider();

Html5RdpProvider.prototype.connectCommon = function(callback) {
	/* This function's goal is to minimize the code by mutualizing
	   the common parts between applications and desktop modes in HTML5
	*/

	var server = this.session_management.session.servers[0];

	/* It is mandatory to specify the server adress if the ssl gateway is used */
	/* 
	server.fqdn = "10.42.1.143";
	server.port = 3389;
	*/  

	var url = "/ovd/guacamole/ovdlogin?"
	url+="server="+server.fqdn+"&";
	url+="username="+server.login+"&";
	url+="password="+server.password+"&";
	url+="width="+this.session_management.parameters["width"]+"&";
	url+="height="+this.session_management.parameters["height"]+"";

	var self = this; /* closure */
	jQuery.ajax({
		url: url,
		type: "GET",
		success: function(xml) {
			/* Add the servers status callback to global namespace */
			window.serverStatus = function(id, status) {
				self.session_management.session.servers[id].setStatus(status);
			}

			/* Connect */
			self.guac_tunnel = new HTTPTunnel("/ovd/guacamole/tunnel");
			self.guac_client = new Guacamole.Client(self.guac_tunnel);
			self.guac_client.connect("id=DEFAULT");

			/* Display */
			self.guac_display = self.guac_client.getDisplay();
			self.guac_canvas  = self.guac_display.firstChild.firstChild.firstChild;

			/* bind mouse events */
			self.guac_mouse = new Guacamole.Mouse(self.guac_display);
			self.guac_mouse.onmousedown = self.guac_mouse.onmouseup = self.guac_mouse.onmousemove = function(mouseState) {
				self.guac_client.sendMouseState(mouseState);
			};

			/* bind keyboard events */
			self.guac_keyboard= new Guacamole.Keyboard(document);
			self.guac_keyboard.onkeydown = function (keysym) {
				self.guac_client.sendKeyEvent(1, keysym);
			};
			self.guac_keyboard.onkeyup = function (keysym) {
				self.guac_client.sendKeyEvent(0, keysym);
			};

			/* Handle desktop|applications mode specificities */
			callback(self);

			/* Notify main panel insertion */
			self.session_management.fireEvent("ovd.rdpProvider.desktopPanel", self, {"name":"Desktop", "node":self.guac_display});
		},
		error: function( xhr, status ) {
			console.log("Error : "+status);
		}
	});
}

Html5RdpProvider.prototype.connectDesktop = function() {
	var self = this; /* closure */
	this.connectCommon( function() {
		/* nothing more to do for desktop mode */
	});
}

Html5RdpProvider.prototype.connectApplications = function() {
	var self = this; /* closure */
	this.connectCommon( function() {
		/* Hide main canvas */
		jQuery(self.guac_canvas).width("1").height("1");
		
		/* set handler for seamrdp channel */
		var seamless_instructionHandler = new Html5SeamlessHandler(self);

		/* set application_provider */
		var application_provider = new Html5ApplicationProvider(self);
	});
}

Html5RdpProvider.prototype.disconnect_implementation = function() {
	this.guac_client.disconnect();
}
