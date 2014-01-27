
uovd.Session = function(session_management) {
	this.session_management = session_management
	this.xml = null;
	this.mode = null
	this.mode_gateway = null
	this.settings = {};
	this.servers = new Array();
	this.status = "unknown";
	this.phase = uovd.SESSION_PHASE_UNKNOWN;
}

uovd.Session.prototype.update = function(xml) {
	if(!xml) {
		this.session_management.fireEvent("ovd.session.error", this, {"code":"bad_xml"});
		return "bad_xml";
	}

	var xml_root = jQuery(xml).find(":root");

	switch(xml_root.prop("nodeName")) {
		case "session" :
		/* Got a session */
		 return this.parseSession(xml_root);

		case "logout" :
		/* Got a logout ack */
		 return this.parseEnd(xml_root);

		case "response" :
		/* Got a start error */
		return this.parseResponse(xml_root);

		case "error" :
		/* Got a session_status error */
		return this.parseError(xml_root);
	}

	this.session_management.fireEvent("ovd.session.error", this, {"code":"bad_xml"});
	return "bad_xml";
}

uovd.Session.prototype.parseSession = function(xml) {
	var self = this; /* closure */
	try {
		if(xml.attr("status") != undefined) {
			/* Session status message */
			var old_status = this.status;
			this.status = xml.attr("status");

			var time_restriction = xml.attr("time_restriction");
			if (time_restriction != undefined) {
				this.session_management.fireEvent("ovd.session.timeRestriction", this, {"when":time_restriction});
			}
			
			if(old_status != this.status) {
				this.session_management.fireEvent("ovd.session.statusChanged", this, {"from":old_status,"to":this.status});
			}
		} else {
			/* New session */
			this.xml = xml[0];

			/* get mode and gateway settings */
			this.mode = xml.attr("mode");
			this.mode_gateway = xml.attr("mode_gateway") ? true : false ;

			/* get settings */
			xml.find("setting").each( function() {
				self.settings[jQuery(this).attr("name")] = jQuery(this).attr("value");
			});

			xml.find("server").each( function() {
				self.servers.push(new uovd.server.Rdp(self, jQuery(this)));
			});

			xml.find("webapp-server").each( function() {
				self.servers.push(new uovd.server.WebApps(self, jQuery(this)));
			});
		}
	} catch(error) {
		this.session_management.fireEvent("ovd.session.error", this, {"code":"bad_xml", "from":"start"});
		return "bad_xml";
	}
	return null;
}

uovd.Session.prototype.parseResponse = function(xml) {
	var code = "";
	try {
		code = xml.attr("code");
		this.session_management.fireEvent("ovd.session.error", this, {"code":code, "from":"start"});
	} catch(error) {
		this.session_management.fireEvent("ovd.session.error", this, {"code":"bad_xml", "from":"start"});
		return "bad_xml";
	}
	return code;
}

uovd.Session.prototype.parseError = function(xml) {
	var code = "";
	try {
		code = xml.attr("id");
		var message = xml.attr("message");
		this.session_management.fireEvent("ovd.session.error", this, {"code":code, "from":"session_status", "message":message});
	} catch(error) {
		this.session_management.fireEvent("ovd.session.error", this, {"code":"bad_xml", "from":"session_status"});
		return "bad_xml";
	}
	return code;
}

uovd.Session.prototype.parseEnd = function(xml) {
	return null;
}

uovd.Session.prototype.starting = function(from) {
	if(this.phase == uovd.SESSION_PHASE_STARTING ||
	   this.phase == uovd.SESSION_PHASE_STARTED ||
	   this.phase == uovd.SESSION_PHASE_DESTROYING ||
	   this.phase == uovd.SESSION_PHASE_DESTROYED ) { return ; }
	this.phase = uovd.SESSION_PHASE_STARTING;
	this.session_management.fireEvent("ovd.session."+this.phase, this, {"from":from});
}

uovd.Session.prototype.started = function(from) {
	if(this.phase == uovd.SESSION_PHASE_STARTED ||
	   this.phase == uovd.SESSION_PHASE_DESTROYING ||
	   this.phase == uovd.SESSION_PHASE_DESTROYED ) { return ; }
	this.starting();
	this.phase = uovd.SESSION_PHASE_STARTED;
	this.session_management.fireEvent("ovd.session."+this.phase, this, {"from":from});
}

uovd.Session.prototype.destroying = function(from) {
	if(this.phase == uovd.SESSION_PHASE_DESTROYING ||
	   this.phase == uovd.SESSION_PHASE_DESTROYED ) { return ; }
	this.started();
	this.phase = uovd.SESSION_PHASE_DESTROYING;
	this.session_management.fireEvent("ovd.session."+this.phase, this, {"from":from});
}

uovd.Session.prototype.destroyed = function(from) {
	if(this.phase == uovd.SESSION_PHASE_DESTROYED) { return ; }
	this.destroying();
	this.phase = uovd.SESSION_PHASE_DESTROYED;
	this.session_management.fireEvent("ovd.session."+this.phase, this, {"from":from});
}
