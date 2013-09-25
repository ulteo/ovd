/* HTML5 RDP Provider */

uovd.provider.rdp.Html5 = function() {
	this.initialize();
	this.connections = null;
}

uovd.provider.rdp.Html5.prototype = new uovd.provider.rdp.Base();

uovd.provider.rdp.Html5.prototype.connectDesktop = function() {
	var self = this; /* closure */

	/* Add the servers status callback */
	/* __MUST__ Be set before guac_client.connect */
	self.serverStatus = function(id, status) {
		var server = self.session_management.session.servers[id];

		if(server.type == uovd.SERVER_TYPE_LINUX || server.type == uovd.SERVER_TYPE_WINDOWS) {
			server.setStatus(status);
		}
	}

	/* Handle full screen */
	if(this.session_management.parameters["fullscreen"] == true) {
		this.connectDesktop_fullscreen();
	} else {
		this.connectDesktop_embeeded();
	}
}

uovd.provider.rdp.Html5.prototype.connectDesktop_fullscreen = function() {
	var self = this; /* closure */
	this.connections = new Array();
	var width = screen.width;
	var height = screen.height;

	/* Get the first RDP server */
	var server = null;
	for(var i=0 ; i<this.session_management.session.servers.length ; ++i) {
		var srv = this.session_management.session.servers[i];
		if(srv.type == uovd.SERVER_TYPE_LINUX || srv.type == uovd.SERVER_TYPE_WINDOWS) {
			server = srv;
			break;
		}
	}

	if(server == null) {
		self.session_management.fireEvent("ovd.rdpProvider.crash", self, {message:"No RDP server"});
		return;
	}

	/* Load server */
	var url = "/ovd/guacamole/ovdlogin?"
	url+="id=0&";
	url+="server="+server.fqdn+"&";
	url+="username="+server.login+"&";
	url+="password="+server.password+"&";
	url+="width="+width+"&";
	url+="height="+height+"";

	jQuery.ajax({
		url: url,
		type: "GET",
		success: function(xml) {
			var connection = {};

			/* Connect */
			connection.guac_tunnel = new Guacamole.ExtHTTPTunnel(self, "/ovd/guacamole/tunnel", 0);
			connection.guac_client = new Guacamole.Client(connection.guac_tunnel);
			connection.guac_client.onerror = function() {
				/* !!! */
				/* Must find a way to know if it is a real error or a simple session end */
				/*self.session_management.fireEvent("ovd.rdpProvider.crash", self, {message:"Tunnel error"});*/
			}
			connection.guac_client.connect("id=0");

			/* Display */
			connection.guac_display = connection.guac_client.getDisplay();
			connection.guac_canvas  = connection.guac_display.firstChild.firstChild.firstChild;

			/* bind mouse events */
			connection.guac_mouse = new Guacamole.Mouse(connection.guac_canvas);
			connection.guac_mouse.onmousedown = connection.guac_mouse.onmouseup = connection.guac_mouse.onmousemove = function(mouseState) {
				connection.guac_client.sendMouseState(mouseState);
			};

			/* bind keyboard events */
			connection.guac_keyboard= new Guacamole.Keyboard(document);
			connection.guac_keyboard.onkeydown = function (keysym) {
				connection.guac_client.sendKeyEvent(1, keysym);
			};
			connection.guac_keyboard.onkeyup = function (keysym) {
				connection.guac_client.sendKeyEvent(0, keysym);
			};

			/* Save server settings */
			self.connections.push(connection);

			/* Add the fullscreen request function */
			self.request_fullscreen = function() {
				jQuery(connection.guac_display).show();
				if(connection.guac_canvas.requestFullScreen)       { connection.guac_canvas.requestFullScreen(); return ; }
				if(connection.guac_canvas.webkitRequestFullScreen) { connection.guac_canvas.webkitRequestFullScreen(Element.ALLOW_KEYBOARD_INPUT); return ; }
				if(connection.guac_canvas.mozRequestFullScreen)    { connection.guac_canvas.mozRequestFullScreen(); return ; }
			};

			/* Hide display */
			jQuery(connection.guac_display).hide();

			/* Notify main panel insertion */
			self.session_management.fireEvent("ovd.rdpProvider.desktopPanel", self, {"type":"Fullscreen", "node":connection.guac_display});
		},
		error: function( xhr, status ) {
			self.session_management.fireEvent("ovd.rdpProvider.crash", self, {message:"ovdlogin failed"});
		}
	});
};

uovd.provider.rdp.Html5.prototype.connectDesktop_embeeded = function() {
	var self = this; /* closure */
	this.connections = new Array();
	var width = this.session_management.parameters["width"];
	var height = this.session_management.parameters["height"];

	/* Get the first RDP server */
	var server = null;
	for(var i=0 ; i<this.session_management.session.servers.length ; ++i) {
		var srv = this.session_management.session.servers[i];
		if(srv.type == uovd.SERVER_TYPE_LINUX || srv.type == uovd.SERVER_TYPE_WINDOWS) {
			server = srv;
			break;
		}
	}

	if(server == null) {
		self.session_management.fireEvent("ovd.rdpProvider.crash", self, {message:"No RDP server"});
		return;
	}

	/* Load server */
	var url = "/ovd/guacamole/ovdlogin?"
	url+="id=0&";
	url+="server="+server.fqdn+"&";
	url+="username="+server.login+"&";
	url+="password="+server.password+"&";
	url+="width="+width+"&";
	url+="height="+height+"";

	jQuery.ajax({
		url: url,
		type: "GET",
		success: function(xml) {
			var connection = {};

			/* Connect */
			connection.guac_tunnel = new Guacamole.ExtHTTPTunnel(self, "/ovd/guacamole/tunnel", 0);
			connection.guac_client = new Guacamole.Client(connection.guac_tunnel);
			connection.guac_client.onerror = function() {
				/* !!! */
				/* Must find a way to know if it is a real error or a simple session end */
				/*self.session_management.fireEvent("ovd.rdpProvider.crash", self, {message:"Tunnel error"});*/
			}
			connection.guac_client.connect("id=0");

			/* Display */
			connection.guac_display = connection.guac_client.getDisplay();
			connection.guac_canvas  = connection.guac_display.firstChild.firstChild.firstChild;

			/* bind mouse events */
			connection.guac_mouse = new Guacamole.Mouse(connection.guac_display);
			connection.guac_mouse.onmousedown = connection.guac_mouse.onmouseup = connection.guac_mouse.onmousemove = function(mouseState) {
				connection.guac_client.sendMouseState(mouseState);
			};

			/* bind keyboard events */
			connection.guac_keyboard= new Guacamole.Keyboard(document);
			connection.guac_keyboard.onkeydown = function (keysym) {
				connection.guac_client.sendKeyEvent(1, keysym);
			};
			connection.guac_keyboard.onkeyup = function (keysym) {
				connection.guac_client.sendKeyEvent(0, keysym);
			};

			/* Save server settings */
			self.connections.push(connection);

			/* Add the fullscreen request function */
			self.request_fullscreen = function() {};

			/* Notify main panel insertion */
			self.session_management.fireEvent("ovd.rdpProvider.desktopPanel", self, {"type":"Desktop", "node":connection.guac_display});
		},
		error: function( xhr, status ) {
			self.session_management.fireEvent("ovd.rdpProvider.crash", self, {message:"ovdlogin failed"});
		}
	});
};

uovd.provider.rdp.Html5.prototype.connectApplications = function() {
	var self = this; /* closure */
	var servers = this.session_management.session.servers;
	this.connections = new Array();

	/* Add the servers status callback */
	/* __MUST__ Be set before guac_client.connect */
	self.serverStatus = function(id, status) {
		var server = self.session_management.session.servers[id];

		if(server.type == uovd.SERVER_TYPE_LINUX || server.type == uovd.SERVER_TYPE_WINDOWS) {
			server.setStatus(status);
		}
	}

	/* Load servers */
	var chainLoader = function(index) {
		/* Recursion control */
		if(!servers[index]) {
			/* Stop recursion : Success ! */

			/* set handler for seamrdp channel */
			var seamless_instructionHandler = new uovd.provider.rdp.html5.SeamlessHandler(self);

			/* set applications_provider */
			var applications_provider = new uovd.provider.applications.Html5(self);

			/* Create a keyboard */
			var keyboard = new Guacamole.Keyboard(document);

			/* Share it among all connections */
			for(var i=0 ; i<self.connections.length ; ++i) {
				self.connections[i].guac_keyboard = keyboard;
			}

			/* /!\ The mouse is not handled per connection but per window /!\ */

			return;
		};

		var server = servers[index];

		if(server.type != uovd.SERVER_TYPE_LINUX && server.type != uovd.SERVER_TYPE_WINDOWS) {
			/* Don't try to connect the RDP client to a WebApp server ! */
			/* Call next chainLoader iteration */
			chainLoader(parseInt(index)+1);
			return;
		}

		var url = "/ovd/guacamole/ovdlogin?"
		url+="id="+index+"&";
		url+="server="+server.fqdn+"&";
		url+="username="+server.login+"&";
		url+="password="+server.password+"&";
		url+="width="+self.session_management.parameters["width"]+"&";
		url+="height="+self.session_management.parameters["height"]+"";

		jQuery.ajax({
			url: url,
			type: "GET",
			success: function(xml) {
				var connection = {};

				/* Connect */
				connection.guac_tunnel = new Guacamole.ExtHTTPTunnel(self, "/ovd/guacamole/tunnel", index);
				connection.guac_client = new Guacamole.Client(connection.guac_tunnel);
				connection.guac_client.onerror = function() {
					/* !!! */
					/* Must find a way to know if it is a real error or a simple session end */
					/*self.session_management.fireEvent("ovd.rdpProvider.crash", self, {message:"Tunnel error"});*/
				}
				connection.guac_client.connect("id="+index);

				/* Display */
				connection.guac_display = connection.guac_client.getDisplay();
				connection.guac_canvas  = connection.guac_display.firstChild.firstChild.firstChild;

				/* Save server settings */
				self.connections.push(connection);

				/* Hide main canvas */
				jQuery(connection.guac_canvas).hide();

				/* Notify main panel insertion */
				self.session_management.fireEvent("ovd.rdpProvider.desktopPanel", self, {"type":"Desktop", "node":connection.guac_display});

				/* Call next chainLoader iteration */
				chainLoader(parseInt(index)+1);
				return;
			},
			error: function( xhr, status ) {
				/* Stop recursion */
				self.session_management.fireEvent("ovd.rdpProvider.crash", self, {message:"ovdlogin failed"});
			}
		});
	};

	/* Call the chainLoader to connect to each server */
	chainLoader(0);
}

uovd.provider.rdp.Html5.prototype.disconnect_implementation = function() {
	for(var i = 0 ; i<this.connections.length ; ++i) {
		this.connections[i].guac_client.disconnect();
	}
}

uovd.provider.rdp.Html5.prototype.testCapabilities = function(onsuccess, onfailure) {
	var success = true;

	/* Test canvas support */
	var elem = document.createElement("canvas");
	if (elem != "[object HTMLCanvasElement]") {
		success = false;
	}

	/* Test XMLHttpRequest support */
	try {
		elem = eval("new XMLHttpRequest()");
		if (elem == null) {
			success = false;
		}
	} catch (e) {
		success = false;
	}

	if(success) {
		onsuccess();
	} else {
		onfailure();
	}
};
