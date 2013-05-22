/* Java RDP Provider */

function JavaRdpProvider() {
	this.initialize();
	this.applet = null;
}
JavaRdpProvider.prototype = new RdpProvider();

JavaRdpProvider.prototype.connectCommon = function(callback) {
	/* This function's goal is to minimize the code by mutualizing
	   the common parts between applications and desktop modes
	*/
	var server = this.session_management.session.servers[0];
	var settings = this.session_management.session.settings;
	var parameters = this.session_management.parameters;

	var name = "ulteoapplet";
	var codebase = "applet/";
	var archive = "jpedal.jar,log4j-1.2.jar,ulteo-applet.jar";
	var cache_archive = "jpedal.jar,log4j-1.2.jar,ulteo-applet.jar";
	var cache_archive_ex = "jpedal.jar,log4j-1.2.jar,ulteo-applet.jar;preload"

	this.applet = jQuery(document.createElement("applet"));
	this.applet.attr("id", "ulteoapplet");
	this.applet.attr("width", parameters["width"]);
	this.applet.attr("height", parameters["height"]);
	this.applet.attr("name", name);
	this.applet.attr("codebase", codebase);
	this.applet.attr("archive", archive);
	this.applet.attr("cache_archive", cache_archive);
	this.applet.attr("cache_archive_ex", cache_archive_ex);
	this.applet.attr("mayscript", "true");

	this.applet.append(jQuery(document.createElement("param")).attr("name", "name").attr("value", name));
	this.applet.append(jQuery(document.createElement("param")).attr("name", "codebase").attr("value", codebase));
	this.applet.append(jQuery(document.createElement("param")).attr("name", "archive").attr("value", archive));
	this.applet.append(jQuery(document.createElement("param")).attr("name", "cache_archive").attr("value", cache_archive));
	this.applet.append(jQuery(document.createElement("param")).attr("name", "cache_archive_ex").attr("value", cache_archive_ex));

	this.applet.append(jQuery(document.createElement("param")).attr("name", "sessionmanager").attr("value", "127.0.0.1:443"));
	this.applet.append(jQuery(document.createElement("param")).attr("name", "server").attr("value", server.fqdn));
	this.applet.append(jQuery(document.createElement("param")).attr("name", "token").attr("value", server.token));
	this.applet.append(jQuery(document.createElement("param")).attr("name", "port").attr("value", server.port));
	this.applet.append(jQuery(document.createElement("param")).attr("name", "username").attr("value", server.login));
	this.applet.append(jQuery(document.createElement("param")).attr("name", "password").attr("value", server.password));
	this.applet.append(jQuery(document.createElement("param")).attr("name", "keymap").attr("value", parameters["keymap"]));
	this.applet.append(jQuery(document.createElement("param")).attr("name", "rdp_input_method").attr("value", parameters["rdp_input_method"]));
	this.applet.append(jQuery(document.createElement("param")).attr("name", "fullscreen").attr("value", parameters["fullscreen"]));

	/* Add the servers status callback to global namespace */
	window.serverStatus = function(id, status) {
		this.session_management.session.servers[id].setStatus(status);
	}

	/* Handle desktop|applications mode specificities */
	callback(this);

	/* Notify main panel insertion */
	this.session_management.fireEvent("ovd.rdpProvider.desktopPanel", this, {"name":"Desktop", "node":this.applet[0]});
}

JavaRdpProvider.prototype.connectDesktop = function() {
	var self = this; /* closure */
	this.connectCommon( function() {
		self.applet.attr("code", "org.ulteo.ovd.applet.Desktop");
		self.applet.append(jQuery(document.createElement("param")).attr("code", "org.ulteo.ovd.applet.Desktop"));
	});
}

JavaRdpProvider.prototype.connectApplications = function() {
	var self = this; /* closure */
	this.connectCommon( function() {
		/* Set entry point */
		self.applet.attr("code", "org.ulteo.ovd.applet.Applications");
		self.applet.append(jQuery(document.createElement("param")).attr("code", "org.ulteo.ovd.applet.Applications"));

		/* minimize applet size */
		/* css "display: none" doesn't works with applets */
		self.applet.width("1").height("1");

		/* set application_provider */
		var application_provider = new JavaApplicationProvider(self);

		/* wait for applet to be initialized */
		var waitApplet = function() {
			try {
				if( ! self.applet[0].isActive()) {
					throw "applet is not ready";
				}
			} catch(e) {
				setTimeout(waitApplet, 1000);
				return;
			}

			/* Connect to all servers and gather application list */
			for(var i=0 ; i<self.session_management.session.servers.length ; ++i) {
				var server = self.session_management.session.servers[i];
				var serialized;
				try {
					// XMLSerializer exists in current Mozilla browsers
					serializer = new XMLSerializer();
					serialized = serializer.serializeToString(server.xml);
				} catch (e) {
					// Internet Explorer has a different approach to serializing XML
					serialized = server.xml.xml;
				}

				self.applet[0].serverPrepare(i, serialized);

				if (server.token != null) {
					self.applet[0].serverConnect(i, server.fqdn, server.port, server.token, server.login, server.password);
				} else {
					self.applet[0].serverConnect(i, server.fqdn, server.port, server.login, server.password);
				}
			}
		}

		waitApplet();
	});
}
