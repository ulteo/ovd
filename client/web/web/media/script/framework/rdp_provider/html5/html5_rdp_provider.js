/* HTML5 RDP Provider */

function Html5RdpProvider() {
	this.initialize();
	this.connections = new Array();
}

Html5RdpProvider.prototype = new RdpProvider();

Html5RdpProvider.prototype.connectDesktop = function() {
	var self = this; /* closure */
	var server = this.session_management.session.servers[0];

	/* Add the servers status callback to global namespace */
	/* __MUST__ Be set before guac_client.connect */
	window.serverStatus = function(id, status) {
		self.session_management.session.servers[id].setStatus(status);
	}

	/* Load server */
	var url = "/ovd/guacamole/ovdlogin?"
	url+="id=0&";
	url+="server="+server.fqdn+"&";
	url+="username="+server.login+"&";
	url+="password="+server.password+"&";
	url+="width="+this.session_management.parameters["width"]+"&";
	url+="height="+this.session_management.parameters["height"]+"";

	jQuery.ajax({
		url: url,
		type: "GET",
		success: function(xml) {
			var connection = {};

			/* Connect */
			connection.guac_tunnel = new HTTPTunnel("/ovd/guacamole/tunnel", 0);
			connection.guac_client = new Guacamole.Client(connection.guac_tunnel);
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

			/* Notify main panel insertion */
			self.session_management.fireEvent("ovd.rdpProvider.desktopPanel", self, {"name":"Desktop_0", "node":connection.guac_display});
		},
		error: function( xhr, status ) {
			console.log("Error : "+status);
			delete window.serverStatus;
		}
	});
}

Html5RdpProvider.prototype.connectApplications = function() {
	var self = this; /* closure */
	var servers = this.session_management.session.servers;

	/* Add the servers status callback to global namespace */
	/* __MUST__ Be set before guac_client.connect */
	window.serverStatus = function(id, status) {
		self.session_management.session.servers[id].setStatus(status);
	}

	/* Load servers */
	var chainLoader = function(index) {
		/* Recursion control */
		if(!servers[index]) {
			/* Stop recursion : Success ! */

			/* set handler for seamrdp channel */
			var seamless_instructionHandler = new Html5SeamlessHandler(self);

			/* set application_provider */
			var application_provider = new Html5ApplicationProvider(self);

			return;
		};

		var server = servers[index];
		var url = "/ovd/guacamole/ovdlogin?"
		url+="id="+index+"&";
		url+="server="+server.fqdn+"&";
		url+="username="+server.login+"&";
		url+="password="+server.password+"&";
		url+="width="+this.session_management.parameters["width"]+"&";
		url+="height="+this.session_management.parameters["height"]+"";

		jQuery.ajax({
			url: url,
			type: "GET",
			success: function(xml) {
				var connection = {};

				/* Connect */
				connection.guac_tunnel = new HTTPTunnel("/ovd/guacamole/tunnel", index);
				connection.guac_client = new Guacamole.Client(connection.guac_tunnel);
				connection.guac_client.connect("id="+index);

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

				/* Hide main canvas */
				jQuery(connection.guac_canvas).width("1").height("1");

				/* Notify main panel insertion */
				self.session_management.fireEvent("ovd.rdpProvider.desktopPanel", self, {"name":"Desktop_"+index, "node":connection.guac_display});

				/* Call next chainLoader iteration */
				chainLoader(parseInt(index)+1);
			},
			error: function( xhr, status ) {
				/* Stop recursion : Error ! */
				console.log("Error : "+status);
				delete window.serverStatus;
			}
		});
	};

	/* Call the chainLoader to connect to each server */
	chainLoader(0);
}

Html5RdpProvider.prototype.disconnect_implementation = function() {
	for(var i = 0 ; i<this.connections.length ; ++i) {
		this.connections[i].guac_client.disconnect();
	}
}
