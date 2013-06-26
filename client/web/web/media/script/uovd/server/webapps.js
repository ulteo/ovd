/* WebApps server */

uovd.server.WebApps = function(session, xml) {
	var self = this; /* closure */
	this.initialize(session, xml);

	this.base_url = xml.attr("base-url");
	this.webapps_url = xml.attr("webapps-url");

	xml.find("application").each( function() {
		self.applications.push(new uovd.Application(self, jQuery(this)));
	});
};

uovd.server.WebApps.prototype = new uovd.server.Base();

uovd.server.WebApps.prototype.setStatus = function(status) {
	/* Server status message */
	var old_status = this.status;
	this.status = status

	if(old_status != this.status) {
		this.session.session_management.fireEvent("ovd.session.server.statusChanged", this, {"from":old_status,"to":this.status});
	}
};
