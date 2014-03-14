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
	if(server.token) {
		url+="token="+server.token+"&";
	} else {
		url+="server="+server.fqdn+"&";
		url+="port="+server.port+"&";
	}
	url+="mode="+this.session_management.session.mode+"&";
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
			connection.guac_canvas  = jQuery(connection.guac_client.getDisplay()).find("canvas").attr("style", null)[0];
			connection.guac_display = jQuery(document.createElement("div")).append(connection.guac_canvas)[0];

			/* Initialize inputs */
			connection.keyboard = new uovd.provider.rdp.html5.Keyboard(self, connection);
			connection.mouse = new uovd.provider.rdp.html5.Mouse(self, connection);

			/* Cursor support */
			connection.guac_client.oncursor = function(params) {
				var url = params["url"];
				var x = params["x"];
				var y = params["y"];
				jQuery(connection.guac_display).css("cursor", "url("+url+")"+x+" "+y+", auto");
			};

			/* Save server settings */
			self.connections.push(connection);

			/* set handler for clipboard channel */
			var clipboard_instructionHandler = new uovd.provider.rdp.html5.ClipboardHandler(self);

			/* set handler for printer channel */
			var print_instructionHandler = new uovd.provider.rdp.html5.PrintHandler(self);

			/* Add the fullscreen request function */
			self.request_fullscreen = function() {
				jQuery(connection.guac_display).show();
				if(connection.guac_display.requestFullScreen)       { connection.guac_display.requestFullScreen(); return ; }
				if(connection.guac_display.webkitRequestFullScreen) { connection.guac_display.webkitRequestFullScreen(Element.ALLOW_KEYBOARD_INPUT); return ; }
				if(connection.guac_display.mozRequestFullScreen)    { connection.guac_display.mozRequestFullScreen(); return ; }
			};

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
	if(server.token) {
		url+="token="+server.token+"&";
	} else {
		url+="server="+server.fqdn+"&";
		url+="port="+server.port+"&";
	}
	url+="mode="+this.session_management.session.mode+"&";
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
			connection.guac_canvas  = jQuery(connection.guac_client.getDisplay()).find("canvas").attr("style", null)[0];
			connection.guac_display = jQuery(document.createElement("div")).append(connection.guac_canvas)[0];

			/* Initialize inputs */
			connection.keyboard = new uovd.provider.rdp.html5.Keyboard(self, connection);
			connection.mouse = new uovd.provider.rdp.html5.Mouse(self, connection);

			/* Cursor support */
			connection.guac_client.oncursor = function(params) {
				var url = params["url"];
				var x = params["x"];
				var y = params["y"];
				jQuery(connection.guac_display).css("cursor", "url("+url+")"+x+" "+y+", auto");
			};

			/* Save server settings */
			self.connections.push(connection);

			/* set handler for clipboard channel */
			var clipboard_instructionHandler = new uovd.provider.rdp.html5.ClipboardHandler(self);

			/* set handler for printer channel */
			var print_instructionHandler = new uovd.provider.rdp.html5.PrintHandler(self);

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

	/* Display */
	var display = jQuery(document.createElement("div"));

	/* Load servers */
	var chainLoader = function(index) {
		/* Recursion control */
		if(!servers[index]) {
			/* Stop recursion : Success ! */

			if(self.connections.length == 0) {
				/* Stop recursion : No rdp servers */
				return;
			}

			/* set handler for seamrdp channel */
			var seamless_instructionHandler = new uovd.provider.rdp.html5.SeamlessHandler(self);

			/* set handler for clipboard channel */
			var clipboard_instructionHandler = new uovd.provider.rdp.html5.ClipboardHandler(self);

			/* set handler for printer channel */
			var print_instructionHandler = new uovd.provider.rdp.html5.PrintHandler(self);

			/* set applications_provider */
			var applications_provider = new uovd.provider.applications.Html5(self);

			/* Create a keyboard */
			var keyboard = new uovd.provider.rdp.html5.Keyboard(self, self.connections[0]);

			/* Share it among all connections */
			for(var i=0 ; i<self.connections.length ; ++i) {
				self.connections[i].keyboard = keyboard;
			}

			/* /!\ The mouse is not handled per connection but per window /!\ */

			/* Notify main panel insertion */
			self.session_management.fireEvent("ovd.rdpProvider.desktopPanel", self, {"type":"Desktop", "node":display});

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
		if(server.token) {
			url+="token="+server.token+"&";
		} else {
			url+="server="+server.fqdn+"&";
			url+="port="+server.port+"&";
		}
		url+="mode="+self.session_management.session.mode+"&";
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

				/* Save server settings */
				self.connections.push(connection);

				/* Display */
				connection.guac_display = display;

				/* Canvas */
				connection.guac_canvas = jQuery(connection.guac_client.getDisplay()).find("canvas").attr("style", null)[0];

				/* Hide main canvas */
				jQuery(connection.guac_canvas).hide();

				/* Add it to the display */
				display.append(connection.guac_canvas);

				/* Unwrap from jQuery */
				connection.guac_display = connection.guac_display[0];

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
