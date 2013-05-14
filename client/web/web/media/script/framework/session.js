function Session(session_management) {
	this.session_management = session_management
	this.mode = null
	this.mode_gateway = null
	this.settings = new Array();
	this.servers = new Array();
	this.status = "unknown";
}

Session.prototype.update = function(xml) {
	var xml_root = jQuery(xml).find(":root");

	switch(xml_root.prop("nodeName")) {
		case "session" :
		/* Got a session */
		 return this.parseSession(xml_root);

		case "logout" :
		/* Got a logout ack */
		 return this.parseEnd(xml_root);

		case "response" :
		/* Got an error */
		return this.parseError(xml_root);
	}

	return "bad_xml";
}

Session.prototype.parseSession = function(xml) {
	var self = this; /* closure */
	try {
		if(xml.attr("status") != undefined) {
			/* Session status message */
			var old_status = this.status;
			this.status = xml.attr("status");

			if(old_status != this.status) {
				this.session_management.fireEvent("ovd.session.statusChanged", this, {"from":old_status,"to":this.status});
			}
		} else {
			/* New session */

			/* get mode and gateway settings */
			this.mode = xml.attr("mode");
			this.mode_gateway = xml.attr("mode_gateway") ? true : false ;

			/* get settings */
			xml.find("setting").each( function() {
				self.settings.push(new Setting(self, jQuery(this)));
			});

			xml.find("server").each( function() {
				self.servers.push(new Server(self, jQuery(this)));
			});
		}
	} catch(error) {
		console.log("Error : "+error);
		return "bad_xml";
	}
	return null;
}

Session.prototype.parseError = function(xml) {
	try {
		var code = xml.attr("code");
		this.session.session_management.fireEvent("ovd.log", this, {"message":"Session error "+code,"level":"error"});
	} catch(error) {
		console.log("Error : "+error);
		return "bad_xml";
	}
	return null;
}

Session.prototype.parseEnd = function(xml) {
	return null;
}

/* Data storage */

function Setting(session, xml) {
	this.session = session;
	this.name = xml.attr("name");
	this.value = xml.attr("value");
}

function Server(session, xml) {
	var self = this; /* closure */
	this.session = session;
	this.type = xml.attr("type");
	this.fqdn = xml.attr("fqdn");
	this.token = xml.attr("token");
	this.port = xml.attr("port");
	this.login = xml.attr("login");
	this.password = xml.attr("password");
	this.applications = new Array();
	this.status = "unknown";

	xml.find("application").each( function() {
		self.applications.push(new Application(self, jQuery(this)));
	});

	if(!this.port) {
		this.port = 3389;
	}

	if(this.token) {
		/* access from SSL gateway */
		this.fqdn = window.location.hostname;
		this.port = window.location.port !=  '' ? window.location.port : 443;
	}
}

Server.prototype.setStatus = function(status) {
	/* Server status message */
	var old_status = this.status;
	this.status = status

	if(old_status != this.status) {
		this.session.session_management.fireEvent("ovd.session.server.statusChanged", this, {"from":old_status,"to":this.status});
	}
}

function Application(server, xml) {
	var self = this; /* closure */
	this.server = server;
	this.id = xml.attr("id");
	this.name = xml.attr("name");
	this.mime = new Array();

	xml.find("mime").each( function() {
		self.mime.push(jQuery(this).attr("type"))
	});
}
