/* RDP server */

uovd.server.Rdp = function(session, xml) {
	var self = this; /* closure */
	this.initialize(session, xml);

	this.fqdn = xml.attr("fqdn");
	this.token = xml.attr("token");
	this.port = xml.attr("port");

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
};

uovd.server.Rdp.prototype = new uovd.server.Base();

uovd.server.Rdp.prototype.setStatus = function(status) {
	/* Server status message */
	var old_status = this.status;
	this.status = status

	if(old_status != this.status) {
		this.session.session_management.fireEvent("ovd.session.server.statusChanged", this, {"from":old_status,"to":this.status});
	}
};
