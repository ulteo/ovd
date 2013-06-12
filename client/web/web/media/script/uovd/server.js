
uovd.Server = function(session, xml) {
	var self = this; /* closure */
	this.session = session;
	this.xml = xml[0];
	this.type = xml.attr("type");
	this.fqdn = xml.attr("fqdn");
	this.token = xml.attr("token");
	this.port = xml.attr("port");
	this.login = xml.attr("login");
	this.password = xml.attr("password");
	this.applications = new Array();
	this.status = "unknown";

	xml.find("application").each( function() {
		self.applications.push(new uovd.Application(self, jQuery(this)));
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

uovd.Server.prototype.setStatus = function(status) {
	/* Server status message */
	var old_status = this.status;
	this.status = status

	if(old_status != this.status) {
		this.session.session_management.fireEvent("ovd.session.server.statusChanged", this, {"from":old_status,"to":this.status});
	}
}
