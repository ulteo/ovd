/* RDP server */

uovd.server.Rdp = function(session, xml) {
	var self = this; /* closure */
	this.initialize(session, xml);

	var token = xml.attr("token");
	var fqdn = xml.attr("fqdn");
	var port = xml.attr("port");

	if(token) {
		this.token = token;
	} else if(fqdn) {
		this.fqdn = fqdn
		if(port) {
			this.port = port;
		} else {
			/* Default port */
			this.port = 3389;
		}
	}

	xml.find("application").each( function() {
		self.applications.push(new uovd.Application(self, jQuery(this)));
	});
};

uovd.server.Rdp.prototype = new uovd.server.Base();
